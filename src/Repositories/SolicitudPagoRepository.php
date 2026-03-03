<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\SolicitudPago;
use Exception;
use PDO;

class SolicitudPagoRepository extends Repository
{
    protected function getTable(): string
    {
        return 'solicitud_pago';
    }

    /**
     * @return array
     */
    public function all(string $search = '', string $mes = ''): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                SP.id_solicitud_pago, SP.fecha_solicitud_pago, SP.concepto_solicitud_pago,
                SP.monto_pagar_solicitud_pago, SP.id_documento, SP.contabilizada
            FROM solicitud_pago AS SP
            WHERE SP.eliminado = false
        ";

        if ($mes !== '' && $mes >= '01' && $mes <= '12') {
            $sql .= " AND strftime('%m', SP.fecha_solicitud_pago) = :mes";
        }

        if ($search !== '') {
            $sql .= " AND (
                SP.concepto_solicitud_pago LIKE :search
                OR CAST(SP.id_solicitud_pago AS TEXT) LIKE :search
            )";
        }
        $sql .= " ORDER BY SP.fecha_solicitud_pago DESC, SP.id_solicitud_pago ASC";

        $stmt = $db->prepare($sql);
        if ($mes !== '' && $mes >= '01' && $mes <= '12') {
            $stmt->bindValue(':mes', $mes);
        }
        if ($search !== '') {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $estado = ($row['contabilizada'] ?? false) ? 'Aprobada/Contabilizada' : 'Pendiente';

            $sp = new SolicitudPago(
                $row['fecha_solicitud_pago'],
                $row['concepto_solicitud_pago'],
                (float)($row['monto_pagar_solicitud_pago'] ?? 0),
                $estado,
                $row['id_documento'] ? (int)$row['id_documento'] : null,
                (int)$row['id_solicitud_pago']
            );
            $results[] = [
                'entity' => $sp,
            ];
        }

        return $results;
    }

    public function find(int $id): ?SolicitudPago
    {
        $row = $this->query()->where('id_solicitud_pago', '=', $id)->where('eliminado', '=', 0)->first();
        if (!$row) {
            return null;
        }

        $estado = ($row['contabilizada'] ?? false) ? 'Aprobada/Contabilizada' : 'Pendiente';

        return new SolicitudPago(
            $row['fecha_solicitud_pago'],
            $row['concepto_solicitud_pago'],
            (float)($row['monto_pagar_solicitud_pago'] ?? 0),
            $estado,
            $row['id_documento'] ? (int)$row['id_documento'] : null,
            (int)$row['id_solicitud_pago']
        );
    }

    public function save(SolicitudPago $item): bool
    {
        $data = [
            'fecha_solicitud_pago'    => $item->fecha,
            'concepto_solicitud_pago' => $item->concepto,
            'monto_pagar_solicitud_pago' => $item->montoPagar,
            'id_documento'            => $item->idDocumento,
        ];

        if ($item->id) {
            return $this->query()->where('id_solicitud_pago', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);
        if ($id) {
            $item->/* private(set) */ id = (int)$id;

            return true;
        }

        return false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_solicitud_pago', '=', $id)->update(['eliminado' => true]);
    }

    /**
     * TRANSACTION: Registrar Pago y Afectar Presupuesto (PAGADO)
     */
    public function registrarPago(int $idReq, array $pagoData): bool
    {
        $db = $this->getPdo();
        $db->beginTransaction();

        try {
            $req = $this->find($idReq);
            if (!$req || $req->estado === 'Aprobada/Contabilizada') {
                throw new Exception("La solicitud no existe o ya fue pagada.");
            }

            // 1. Registrar movimiento bancario vinculado
            $stmtMov = $db->prepare("
                INSERT INTO movimiento_bancario 
                (id_cta_bancaria, id_tipo_operacion_bancaria, monto, fecha, referencia, id_solicitud_pago)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtMov->execute([
                $pagoData['id_cta_bancaria'],
                $pagoData['id_tipo_operacion_bancaria'],
                $req->montoPagar,
                $pagoData['fecha_pago'] ?? date('Y-m-d'),
                $pagoData['referencia'],
                $idReq,
            ]);

            // 2. Marcar Solicitud como Contabilizada (Pagada)
            $db->prepare("UPDATE solicitud_pago SET contabilizada = true WHERE id_solicitud_pago = ?")->execute([$idReq]);

            // 3. Afectar Presupuesto (PAGADO) e Iniciar Contabilización
            $asientoDetalles = [];
            if ($req->idDocumento) {
                $stmtDoc = $db->prepare("SELECT id_orden_de_compra, id_orden_de_servicio FROM documento WHERE id_documento = ?");
                $stmtDoc->execute([$req->idDocumento]);
                $docRow = $stmtDoc->fetch(PDO::FETCH_ASSOC);

                if ($docRow) {
                    $idOrden = $docRow['id_orden_de_compra'] ?: $docRow['id_orden_de_servicio'];
                    $esOs = (bool)$docRow['id_orden_de_servicio'];

                    if ($idOrden) {
                        $montoPagadoPorPartida = [];
                        if (!$esOs) {
                            $stmtC = $db->prepare("
                                SELECT a.id_codigo_plan_unico, SUM(aodc.costo_aodc * aodc.cantidad_aodc) as subtotal_base, aodc.aplica_iva 
                                FROM articulo_orden_de_compra aodc
                                JOIN articulo a ON aodc.id_articulo = a.id_articulo
                                WHERE aodc.id_orden_de_compra = ?
                                GROUP BY a.id_codigo_plan_unico, aodc.aplica_iva
                            ");
                        } else {
                            $stmtC = $db->prepare("
                                SELECT s.id_codigo_plan_unico, (sods.costo_sods * sods.cantidad_sods) as subtotal_base, sods.aplica_iva,
                                (SELECT porcentaje_iva_os FROM orden_de_servicio WHERE id_orden_de_servicio = ?) as pct_iva
                                FROM servicio_orden_de_servicio sods
                                JOIN servicio s ON sods.id_servicio = s.id_servicio
                                WHERE sods.id_orden_de_servicio = ?
                            ");
                        }

                        $args = $esOs ? [$idOrden, $idOrden] : [$idOrden];
                        $stmtC->execute($args);

                        $convRepo = new ConvertidorCuentaRepository();

                        foreach ($stmtC->fetchAll(PDO::FETCH_ASSOC) as $row) {
                            $idPartida = $row['id_codigo_plan_unico'];
                            $base = (float)$row['subtotal_base'];
                            $porcIva = $row['aplica_iva'] ? ($esOs ? (float)$row['pct_iva'] : 16.0) : 0.0;
                            $montoFase = $base + ($base * ($porcIva / 100));

                            // UPDATE Presupuesto (PAGADO)
                            $db->prepare("UPDATE presupuesto_gastos SET monto_pagado = monto_pagado + ? WHERE id_codigo_plan_unico = ?")
                               ->execute([$montoFase, $idPartida]);

                            // Prepara Asiento
                            $idCuentaDebe = $convRepo->getCuentaContableId($idPartida, 'PAGADO');
                            $idCuentaHaber = $convRepo->getCuentaContableId($idPartida, 'PAGADO_BANCO');

                            $asientoDetalles[] = ['id_cuenta' => $idCuentaDebe ?: 3, 'tipo' => 'D', 'monto' => $montoFase];
                            $asientoDetalles[] = ['id_cuenta' => $idCuentaHaber ?: 2, 'tipo' => 'H', 'monto' => $montoFase];
                        }
                    }
                }
            }

            // 4. Integrar con Contabilidad
            if (empty($asientoDetalles)) {
                $asientoDetalles = [
                   ['id_cuenta' => 3, 'tipo' => 'D', 'monto' => $req->montoPagar],
                   ['id_cuenta' => 2, 'tipo' => 'H', 'monto' => $req->montoPagar],
                ];
            }

            $repoContable = new AsientoContableRepository();
            $repoContable->registrarDesdeTransaccion(
                $pagoData['fecha_pago'] ?? date('Y-m-d'),
                "Pago Solicitud #{$idReq} - Ref: {$pagoData['referencia']}",
                $asientoDetalles,
                $idReq
            );

            $db->commit();

            return true;

        } catch (Exception $e) {
            $db->rollBack();

            throw $e;
        }
    }

    /**
     * REVERSIÓN: Deshacer pago, eliminar registros contables/bancarios y restaurar presupuesto.
     */
    public function reversarPago(int $idSolicitud): bool
    {
        $db = $this->getPdo();
        $db->beginTransaction();

        try {
            $req = $this->find($idSolicitud);
            if (!$req || $req->estado !== 'Aprobada/Contabilizada') {
                throw new Exception("La solicitud no existe o no está pagada.");
            }

            // 1. Restaurar Presupuesto (PAGADO -> CAUSADO)
            if ($req->idDocumento) {
                $stmtDoc = $db->prepare("SELECT id_orden_de_compra, id_orden_de_servicio FROM documento WHERE id_documento = ?");
                $stmtDoc->execute([$req->idDocumento]);
                $docRow = $stmtDoc->fetch(PDO::FETCH_ASSOC);

                if ($docRow) {
                    $idOrden = $docRow['id_orden_de_compra'] ?: $docRow['id_orden_de_servicio'];
                    $esOs = (bool)$docRow['id_orden_de_servicio'];

                    if ($idOrden) {
                        if (!$esOs) {
                            $stmtC = $db->prepare("
                                SELECT a.id_codigo_plan_unico, SUM(aodc.costo_aodc * aodc.cantidad_aodc) as subtotal_base, aodc.aplica_iva 
                                FROM articulo_orden_de_compra aodc
                                JOIN articulo a ON aodc.id_articulo = a.id_articulo
                                WHERE aodc.id_orden_de_compra = ?
                                GROUP BY a.id_codigo_plan_unico, aodc.aplica_iva
                            ");
                        } else {
                            $stmtC = $db->prepare("
                                SELECT s.id_codigo_plan_unico, (sods.costo_sods * sods.cantidad_sods) as subtotal_base, sods.aplica_iva,
                                (SELECT porcentaje_iva_os FROM orden_de_servicio WHERE id_orden_de_servicio = ?) as pct_iva
                                FROM servicio_orden_de_servicio sods
                                JOIN servicio s ON sods.id_servicio = s.id_servicio
                                WHERE sods.id_orden_de_servicio = ?
                            ");
                        }

                        $args = $esOs ? [$idOrden, $idOrden] : [$idOrden];
                        $stmtC->execute($args);

                        foreach ($stmtC->fetchAll(PDO::FETCH_ASSOC) as $row) {
                            $idPartida = $row['id_codigo_plan_unico'];
                            $base = (float)$row['subtotal_base'];
                            $porcIva = $row['aplica_iva'] ? ($esOs ? (float)$row['pct_iva'] : 16.0) : 0.0;
                            $montoFase = $base + ($base * ($porcIva / 100));

                            $db->prepare("UPDATE presupuesto_gastos SET monto_pagado = monto_pagado - ? WHERE id_codigo_plan_unico = ?")
                               ->execute([$montoFase, $idPartida]);
                        }
                    }
                }
            }

            // 2. Soft-delete de movimientos vinculados
            $db->prepare("UPDATE movimiento_bancario SET eliminado = 1 WHERE id_solicitud_pago = ?")->execute([$idSolicitud]);
            $db->prepare("UPDATE comprobante_diario SET eliminado = 1 WHERE id_solicitud_pago = ?")->execute([$idSolicitud]);
            $db->prepare("UPDATE movimiento_contable SET eliminado = 1 WHERE id_comprobante_diario IN (SELECT id_comprobante_diario FROM comprobante_diario WHERE id_solicitud_pago = ?)")->execute([$idSolicitud]);

            // 3. Resetear estado de la solicitud
            $db->prepare("UPDATE solicitud_pago SET contabilizada = false WHERE id_solicitud_pago = ?")->execute([$idSolicitud]);

            $db->commit();

            return true;
        } catch (Exception $e) {
            $db->rollBack();

            throw $e;
        }
    }

    public function getPendientesPago(): array
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("
            SELECT * FROM solicitud_pago
            WHERE contabilizada = 0 AND eliminado = 0
            ORDER BY fecha_solicitud_pago ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
