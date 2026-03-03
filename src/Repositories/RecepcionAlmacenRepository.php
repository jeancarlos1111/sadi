<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\AsientoContable;
use Exception;
use PDO;

class RecepcionAlmacenRepository extends Repository
{
    protected function getTable(): string
    {
        return 'inventario_insumos'; // Generic base
    }

    /**
     * Trae los detalles pendientes de una Orden de Compra para Recepción
     */
    public function getPendientesPorOrden(int $idOrden): array
    {
        $db = $this->getPdo();

        $sql = "
            SELECT 
                aodc.id_articulo,
                a.denominacion_a,
                tda.tipo_tda, -- 1=Bienes, 2=Insumos
                udm.denominacion_udm,
                aodc.cantidad_aodc as cantidad_pedida,
                (
                    SELECT COUNT(*) 
                    FROM inventario_bienes ib 
                    WHERE ib.id_orden_de_compra = aodc.id_orden_de_compra 
                    AND ib.id_articulo = aodc.id_articulo 
                    AND ib.eliminado = false
                ) +
                COALESCE((
                    SELECT SUM(ii.cantidad_ii)
                    FROM inventario_insumos ii
                    WHERE ii.id_orden_de_compra = aodc.id_orden_de_compra
                    AND ii.id_articulo = aodc.id_articulo
                    AND ii.eliminado = false
                ), 0) as cantidad_recibida,
                aodc.costo_aodc
            FROM articulo_orden_de_compra aodc
            JOIN articulo a ON aodc.id_articulo = a.id_articulo
            JOIN tipo_de_articulo tda ON a.id_tipo_de_articulo = tda.id_tipo_de_articulo
            JOIN unidades_de_medida udm ON a.id_unidades_de_medida = udm.id_unidades_de_medida
            WHERE aodc.id_orden_de_compra = :id_orden
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([':id_orden' => $idOrden]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * TRANSACTION: Recibir Artículos en Almacén e impactar Cuentas por Pagar (Causado)
     */
    public function recibirArticulos(int $idOrden, array $articulosRecibidos): bool
    {
        $db = $this->getPdo();
        $db->beginTransaction();

        try {
            // 1. Obtener datos de la Orden de Compra
            $stmtOc = $db->prepare("SELECT * FROM orden_de_compra WHERE id_orden_de_compra = ?");
            $stmtOc->execute([$idOrden]);
            $orden = $stmtOc->fetch(PDO::FETCH_ASSOC);

            if (!$orden) {
                throw new Exception("Orden de Compra no encontrada.");
            }

            // Mapeo para Causado Presupuestario
            $montoCausarPorPartida = [];
            $montoBaseTotalForm = 0;
            $montoIvaTotalForm = 0;

            foreach ($articulosRecibidos as $item) {
                $idArticulo = (int)$item['id_articulo'];
                $cantidad = (float)$item['cantidad'];

                if ($cantidad <= 0) {
                    continue;
                }

                // Buscar datos del articulo en la orden para saber precio y tipo
                $stmtDetalle = $db->prepare("
                    SELECT aodc.*, a.id_tipo_de_articulo, tda.tipo_tda, a.id_codigo_plan_unico
                    FROM articulo_orden_de_compra aodc
                    JOIN articulo a ON aodc.id_articulo = a.id_articulo
                    JOIN tipo_de_articulo tda ON a.id_tipo_de_articulo = tda.id_tipo_de_articulo
                    WHERE aodc.id_orden_de_compra = ? AND aodc.id_articulo = ?
                ");
                $stmtDetalle->execute([$idOrden, $idArticulo]);
                $detalle = $stmtDetalle->fetch(PDO::FETCH_ASSOC);

                if (!$detalle) {
                    throw new Exception("El artículo ID {$idArticulo} no pertenece a esta Orden de Compra.");
                }

                $tipoArticulo = (int)$detalle['tipo_tda'];
                $costoUnitario = (float)$detalle['costo_aodc'];

                // Calcular para Documento (CxP)
                $subtotal = $cantidad * $costoUnitario;
                $montoBaseTotalForm += $subtotal;

                $ivaRenglon = 0;
                if ($detalle['aplica_iva']) {
                    $ivaRenglon = $subtotal * ((float)$orden['porcentaje_iva_odc'] / 100);
                    $montoIvaTotalForm += $ivaRenglon;
                }

                // Acumular Presupuesto (Causado)
                if ($detalle['id_codigo_plan_unico']) {
                    $idPartida = $detalle['id_codigo_plan_unico'];
                    if (!isset($montoCausarPorPartida[$idPartida])) {
                        $montoCausarPorPartida[$idPartida] = 0;
                    }
                    $montoCausarPorPartida[$idPartida] += ($subtotal + $ivaRenglon);
                }


                // LOGICA INVENTARIO: ¿Es Bien (1) o Insumo (2)?
                if ($tipoArticulo === 1) { // Bienes Nacionales (Equipos, Mobiliario)
                    $stmtInsBien = $db->prepare("
                        INSERT INTO inventario_bienes 
                        (id_articulo, id_proveedor, fecha_compra_ib, id_orden_de_compra, costo_ib, id_estado_bienes, id_ubicacion_articulo, acronimo_id_ib, revisado)
                        VALUES (?, ?, ?, ?, ?, 1, 1, 'SADI-BIEN', 1)
                    ");

                    for ($i = 0; $i < $cantidad; $i++) {
                        $stmtInsBien->execute([
                            $idArticulo,
                            $orden['id_proveedor'],
                            date('Y-m-d'),
                            $idOrden,
                            $costoUnitario,
                        ]);
                    }

                } else { // Insumos / Material de Oficina

                    // Buscar stock actual
                    $stmtStock = $db->prepare("SELECT cantidad_ii FROM inventario_insumos WHERE id_articulo = ? ORDER BY id_inventario_insumos DESC LIMIT 1");
                    $stmtStock->execute([$idArticulo]);
                    $stockActual = (float)$stmtStock->fetchColumn();

                    $nuevoStock = $stockActual + $cantidad;

                    $stmtInsInsumo = $db->prepare("
                        INSERT INTO inventario_insumos 
                        (id_articulo, fecha_modificacion_ii, cantidad_ii, minimo_ii, id_orden_de_compra)
                        VALUES (?, ?, ?, 10, ?)
                    ");
                    $stmtInsInsumo->execute([
                        $idArticulo,
                        date('Y-m-d'),
                        $nuevoStock,
                        $idOrden,
                    ]);
                }
            } // endforeach

            // 2. Generar Documento en Cuentas por Pagar (Causado Base)
            if ($montoBaseTotalForm > 0) {
                $stmtDoc = $db->prepare("
                    INSERT INTO documento 
                    (id_orden_de_compra, id_proveedor, id_tipo_documento, fecha_emision_d, monto_base_d, monto_impuesto_d, monto_total_d, observacion_d)
                    VALUES (?, ?, 2, ?, ?, ?, ?, ?) -- Tipo 2 = Nota de Entrega (esperando Factura)
                ");
                $montoTotalDoc = $montoBaseTotalForm + $montoIvaTotalForm;
                $stmtDoc->execute([
                    $idOrden,
                    $orden['id_proveedor'],
                    date('Y-m-d'),
                    $montoBaseTotalForm,
                    $montoIvaTotalForm,
                    $montoTotalDoc,
                    "Recepción automatica de Almacén para Orden N°" . $idOrden,
                ]);

                // 3. Afectar Presupuesto Fase CAUSADO e Integración Contable
                $stmtPptoCausado = $db->prepare("
                    UPDATE presupuesto_gastos 
                    SET monto_causado = monto_causado + ?
                    WHERE id_codigo_plan_unico = ?
                ");

                $convertidorRepo = new ConvertidorCuentaRepository();

                foreach ($montoCausarPorPartida as $idPartida => $montoFase) {
                    $stmtPptoCausado->execute([$montoFase, $idPartida]);

                    // Buscar cuenta asociada para el Causado (Ej: Activo Inventario o Gasto Operativo)
                    $idCuentaDebe = $convertidorRepo->getCuentaContableId($idPartida, 'CAUSADO');
                    if ($idCuentaDebe) {
                        $asientoDetalles[] = ['id_cuenta' => $idCuentaDebe, 'tipo' => 'D', 'monto' => $montoFase];
                        $asientoDetalles[] = ['id_cuenta' => 3, 'tipo' => 'H', 'monto' => $montoFase];
                    }
                }

                if (!empty($asientoDetalles)) {
                    AsientoContable::registrarDesdeTransaccion(
                        date('Y-m-d'),
                        "Recepción de Almacén (Causado) - Orden N° {$idOrden}",
                        $asientoDetalles
                    );
                }
            }

            $db->commit();

            return true;

        } catch (Exception $e) {
            $db->rollBack();

            throw $e;
        }
    }
}
