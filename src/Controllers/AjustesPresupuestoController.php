<?php

namespace App\Controllers;

use App\Repositories\ReformulacionRepository;
use App\Repositories\EstrucPresupuestariaRepository;
use App\Repositories\ComprobantePresupuestarioRepository;
use App\Repositories\MovimientoPresupuestarioRepository;
use App\Models\ComprobantePresupuestario;
use App\Models\MovimientoPresupuestario;
use App\Database\Connection;
use Exception;

/**
 * Gestiona el Ajuste del Presupuesto a la Reformulación.
 * Carga la comparativa Formulación vs Reformulación y genera
 * comprobantes de Aumento (AU) o Disminución (DI) por diferencia.
 * Basado en Form_AJUSTAR_PRESUPUESTO_REFORMULACION de SIGAFS.
 */
class AjustesPresupuestoController extends HomeController
{
    private ReformulacionRepository $reformRepo;
    private EstrucPresupuestariaRepository $estructurasRepo;
    private ComprobantePresupuestarioRepository $comprobanteRepo;
    private MovimientoPresupuestarioRepository $movimientoRepo;

    public function __construct()
    {
        parent::__construct();
        $this->reformRepo      = new ReformulacionRepository();
        $this->estructurasRepo = new EstrucPresupuestariaRepository();
        $this->comprobanteRepo = new ComprobantePresupuestarioRepository();
        $this->movimientoRepo  = new MovimientoPresupuestarioRepository();
    }

    /** Muestra la comparativa Formulación vs Reformulación con diferencias */
    public function index(): void
    {
        $id_estruc = isset($_GET['id_estruc']) && $_GET['id_estruc'] !== '' ? (int)$_GET['id_estruc'] : null;

        $comparativa = $this->reformRepo->getComparativa($id_estruc);
        $estructuras = $this->estructurasRepo->all();

        // Totales generales
        $totales = [
            'formulado'    => array_sum(array_column($comparativa, 'total_formulado')),
            'reformulado'  => array_sum(array_column($comparativa, 'total_reformulado')),
            'diferencia'   => array_sum(array_column($comparativa, 'diferencia')),
        ];

        $error   = $_SESSION['error']   ?? null;
        $success = $_SESSION['success'] ?? null;
        unset($_SESSION['error'], $_SESSION['success']);

        $this->renderView('presupuesto/ajustes/index', [
            'titulo'      => 'Ajuste de Presupuesto a Reformulación',
            'comparativa' => $comparativa,
            'totales'     => $totales,
            'estructuras' => $estructuras,
            'id_estruc'   => $id_estruc,
            'error'       => $error,
            'success'     => $success,
        ]);
    }

    /** Guarda/actualiza los montos reformulados por partida */
    public function guardarReformulacion(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?route=ajustes_presupuesto/index");
            exit;
        }

        $id_estruc   = (int)($_POST['id_estruc'] ?? 0);
        $id_cuentas  = $_POST['id_cuenta'] ?? [];
        $montos      = $_POST['monto_reformulado'] ?? [];
        $observacion = trim($_POST['observacion'] ?? '');

        try {
            $n = count($id_cuentas);
            for ($i = 0; $i < $n; $i++) {
                $id_cpu = (int)($id_cuentas[$i] ?? 0);
                $monto  = (float)($montos[$i] ?? 0);
                if ($id_cpu > 0 && $id_estruc > 0) {
                    $this->reformRepo->upsert($id_estruc, $id_cpu, $monto, $observacion ?: null);
                }
            }
            $_SESSION['success'] = "Montos de reformulación guardados. Ahora puede 'Generar Comprobantes de Ajuste'.";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al guardar la reformulación: ' . $e->getMessage();
        }

        header("Location: ?route=ajustes_presupuesto/index&id_estruc=$id_estruc");
        exit;
    }

    /**
     * Genera los comprobantes de ajuste (SPGAU y SPGRED/DI) por las diferencias
     * Fiel a Form_AJUSTAR_PRESUPUESTO_REFORMULACION__GenerarComprobantes() de SIGAFS.
     */
    public function generarComprobantes(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?route=ajustes_presupuesto/index");
            exit;
        }

        $id_estruc = isset($_POST['id_estruc']) && $_POST['id_estruc'] !== '' ? (int)$_POST['id_estruc'] : null;
        $comparativa = $this->reformRepo->getComparativa($id_estruc);

        // Separar diferencias positivas (AU) de las negativas (DI)
        $aumentos    = array_filter($comparativa, fn($r) => (float)$r['diferencia'] > 0);
        $disminucion = array_filter($comparativa, fn($r) => (float)$r['diferencia'] < 0);

        if (empty($aumentos) && empty($disminucion)) {
            $_SESSION['error'] = 'No hay diferencias entre Formulación y Reformulación. Nada que ajustar.';
            header("Location: ?route=ajustes_presupuesto/index" . ($id_estruc ? "&id_estruc=$id_estruc" : ''));
            exit;
        }

        $db = Connection::getInstance();

        try {
            $db->beginTransaction();
            $generados = [];

            // --- Comprobante SPGAU (Aumento / Crédito Adicional por Reformulación) ---
            if (!empty($aumentos)) {
                $nextId = $this->comprobanteRepo->getNextId();
                $numAU  = 'SPGAU-' . date('Y') . '-' . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
                $compAU = new ComprobantePresupuestario('AU', $numAU, date('Y-m-d'), 'CRÉDITO ADICIONAL PARA AJUSTAR PRESUPUESTO REFORMULADO');
                $idCompAU = $this->comprobanteRepo->save($compAU);

                foreach ($aumentos as $r) {
                    $mov = new MovimientoPresupuestario(
                        $idCompAU,
                        (int)$r['id_estruc_presupuestaria'],
                        (int)$r['id_codigo_plan_unico'],
                        'CA',  // Crédito Adicional (aumento)
                        (float)$r['diferencia'],
                        'CRÉDITO ADICIONAL PARA AJUSTAR PRESUPUESTO REFORMULADO'
                    );
                    $this->movimientoRepo->save($mov);
                }
                $generados[] = $numAU;
            }

            // --- Comprobante SPGDI (Disminución / Reducción por Reformulación) ---
            if (!empty($disminucion)) {
                $nextId = $this->comprobanteRepo->getNextId();
                $numDI  = 'SPGDI-' . date('Y') . '-' . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
                $compDI = new ComprobantePresupuestario('DI', $numDI, date('Y-m-d'), 'CRÉDITO REDUCCIÓN PARA AJUSTAR PRESUPUESTO REFORMULADO');
                $idCompDI = $this->comprobanteRepo->save($compDI);

                foreach ($disminucion as $r) {
                    $mov = new MovimientoPresupuestario(
                        $idCompDI,
                        (int)$r['id_estruc_presupuestaria'],
                        (int)$r['id_codigo_plan_unico'],
                        'TR',  // Traspaso/Reducción (disminución)
                        abs((float)$r['diferencia']),
                        'CRÉDITO REDUCCIÓN PARA AJUSTAR PRESUPUESTO REFORMULADO'
                    );
                    $this->movimientoRepo->save($mov);
                }
                $generados[] = $numDI;
            }

            $db->commit();
            $_SESSION['success'] = 'Comprobantes generados: ' . implode(', ', $generados) . '. El presupuesto ha sido ajustado a la reformulación.';
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'Error al generar comprobantes: ' . $e->getMessage();
        }

        header("Location: ?route=ajustes_presupuesto/index" . ($id_estruc ? "&id_estruc=$id_estruc" : ''));
        exit;
    }
}
