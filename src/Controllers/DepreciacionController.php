<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;
use App\Models\AsientoContable;
use App\Repositories\InventarioBienesRepository;
use Exception;
use PDO;

class DepreciacionController extends BaseController
{
    private InventarioBienesRepository $bienesRepo;

    public function __construct()
    {
        $this->bienesRepo = new InventarioBienesRepository();
        Gate::authorize('inventario.depreciacion.ver');
    }

    public function index(): void
    {
        $mes = (int)($_GET['mes'] ?? date('m'));
        $anio = (int)($_GET['anio'] ?? date('Y'));

        $db = $this->bienesRepo->getPdo();

        // Check if depreciacion was already run for this period
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM depreciacion_mensual WHERE mes = ? AND anio = ? AND eliminado = false");
        $stmtCheck->execute([$mes, $anio]);
        $yaProcesado = (int)$stmtCheck->fetchColumn() > 0;

        // Get assets eligible for depreciation
        // Elegible: has vida_util_meses > 0, valor_residual >= 0, and not already depreciated below residual value.
        // We will fetch assets and calculate preview
        $bienes = $this->bienesRepo->all();
        $preview = [];

        foreach ($bienes as $bien) {
            $vidaUtil = (int)$bien['vida_util_meses'];
            $valorResidual = (float)$bien['valor_residual'];
            $costoAdquisicion = (float)$bien['costo_ib'];

            if ($vidaUtil <= 0) {
                continue; // Cannot depreciate
            }

            // Get total depreciated so far
            $stmtDepre = $db->prepare("SELECT SUM(monto_depreciado) FROM depreciacion_mensual WHERE id_inventario_bienes = ? AND eliminado = false");
            $stmtDepre->execute([$bien['id_inventario_bienes']]);
            $depreciadoAcumulado = (float)$stmtDepre->fetchColumn();

            $valorActualLibros = $costoAdquisicion - $depreciadoAcumulado;

            if ($valorActualLibros <= $valorResidual) {
                continue; // Fully depreciated
            }

            // Línea Recta: (Costo - Valor Residual) / Vida Útil en meses
            $cuotaMensual = ($costoAdquisicion - $valorResidual) / $vidaUtil;

            // Make sure we don't depreciate below residual
            if (($valorActualLibros - $cuotaMensual) < $valorResidual) {
                $cuotaMensual = $valorActualLibros - $valorResidual;
            }

            $preview[] = [
                'id_inventario_bienes' => $bien['id_inventario_bienes'],
                'codigo' => $bien['codigo_a'] . '-' . $bien['id_inventario_bienes'],
                'denominacion' => $bien['denominacion_a'],
                'costo' => $costoAdquisicion,
                'vida_util' => $vidaUtil,
                'residual' => $valorResidual,
                'acumulado' => $depreciadoAcumulado,
                'valor_actual' => $valorActualLibros,
                'cuota' => $cuotaMensual,
                'nuevo_valor' => $valorActualLibros - $cuotaMensual
            ];
        }

        $this->renderView('inventario/depreciacion/index', [
            'titulo' => 'Depreciación Mensual de Activos Fijos (Línea Recta)',
            'mes' => $mes,
            'anio' => $anio,
            'yaProcesado' => $yaProcesado,
            'preview' => $preview,
            'error' => $_GET['error'] ?? null,
            'success' => $_GET['success'] ?? null,
        ]);
    }

    public function procesar(): void
    {
        Gate::authorize('inventario.depreciacion.procesar');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Método no permitido');

        $mes = (int)$_POST['mes'];
        $anio = (int)$_POST['anio'];
        $db = $this->bienesRepo->getPdo();

        try {
            $db->beginTransaction();

            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM depreciacion_mensual WHERE mes = ? AND anio = ? AND eliminado = false");
            $stmtCheck->execute([$mes, $anio]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                throw new Exception("El período $mes/$anio ya fue procesado.");
            }

            $bienes = $this->bienesRepo->all();
            $totalDepreciado = 0;

            $stmtInsert = $db->prepare("
                INSERT INTO depreciacion_mensual (id_inventario_bienes, mes, anio, monto_depreciado, valor_en_libros)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($bienes as $bien) {
                $vidaUtil = (int)$bien['vida_util_meses'];
                $valorResidual = (float)$bien['valor_residual'];
                $costoAdquisicion = (float)$bien['costo_ib'];

                if ($vidaUtil <= 0) continue;

                $stmtDepre = $db->prepare("SELECT SUM(monto_depreciado) FROM depreciacion_mensual WHERE id_inventario_bienes = ? AND eliminado = false");
                $stmtDepre->execute([$bien['id_inventario_bienes']]);
                $depreciadoAcumulado = (float)$stmtDepre->fetchColumn();

                $valorActualLibros = $costoAdquisicion - $depreciadoAcumulado;
                if ($valorActualLibros <= $valorResidual) continue;

                $cuotaMensual = ($costoAdquisicion - $valorResidual) / $vidaUtil;
                if (($valorActualLibros - $cuotaMensual) < $valorResidual) {
                    $cuotaMensual = $valorActualLibros - $valorResidual;
                }

                $nuevoValorLibros = $valorActualLibros - $cuotaMensual;

                $stmtInsert->execute([
                    $bien['id_inventario_bienes'],
                    $mes,
                    $anio,
                    $cuotaMensual,
                    $nuevoValorLibros
                ]);
                
                $totalDepreciado += $cuotaMensual;
            }

            // Asiento Contable
            if ($totalDepreciado > 0) {
                $asientoDetalles = [
                    ['id_cuenta_contable' => 11, 'tipo' => 'D', 'monto' => $totalDepreciado], // Gasto de Depreciación (ejemplo)
                    ['id_cuenta_contable' => 12, 'tipo' => 'H', 'monto' => $totalDepreciado], // Depreciación Acumulada
                ];
                AsientoContable::registrarDesdeTransaccion(
                    date('Y-m-d'),
                    "Depreciación de Activos Fijos - Período $mes/$anio",
                    $asientoDetalles
                );
            }

            $this->audit('depreciacion_mensual', 'PROCESAR', 0, null, ['mes' => $mes, 'anio' => $anio, 'total' => $totalDepreciado]);

            $db->commit();
            header("Location: ?route=depreciacion/index&mes=$mes&anio=$anio&success=Proceso de depreciación ejecutado correctamente.");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            header("Location: ?route=depreciacion/index&mes=$mes&anio=$anio&error=" . urlencode($e->getMessage()));
            exit;
        }
    }
}
