<?php

namespace App\Controllers;

use App\Models\ComprobantePresupuestario;
use App\Models\MovimientoPresupuestario;
use App\Repositories\ComprobantePresupuestarioRepository;
use App\Repositories\MovimientoPresupuestarioRepository;
use App\Repositories\EstrucPresupuestariaRepository;
use App\Database\Connection;
use Exception;

class AperturaCuentasController extends HomeController
{
    private ComprobantePresupuestarioRepository $comprobanteRepo;
    private MovimientoPresupuestarioRepository $movimientoRepo;
    private EstrucPresupuestariaRepository $estructurasRepo;

    public function __construct()
    {
        parent::__construct();
        $this->comprobanteRepo = new ComprobantePresupuestarioRepository();
        $this->movimientoRepo = new MovimientoPresupuestarioRepository();
        $this->estructurasRepo = new EstrucPresupuestariaRepository();
    }

    public function index(): void
    {
        $estructuras = $this->estructurasRepo->all();
        $id_estruc_presupuestaria = isset($_GET['id_estruc_presupuestaria']) ? (int)$_GET['id_estruc_presupuestaria'] : null;

        $cuentasPorAperturar = [];
        if ($id_estruc_presupuestaria) {
            $cuentasPorAperturar = $this->movimientoRepo->getCuentasAperturablesPorEstructura($id_estruc_presupuestaria);
        }

        $error   = $_SESSION['error']   ?? null;
        $success = $_SESSION['success'] ?? null;
        unset($_SESSION['error'], $_SESSION['success']);

        $this->renderView('apertura_cuentas/index', [
            'titulo'               => 'Apertura y Formulación de Cuentas Presupuestarias',
            'estructuras'          => $estructuras,
            'id_estruc_seleccionada' => $id_estruc_presupuestaria,
            'cuentas'              => $cuentasPorAperturar,
            'error'                => $error,
            'success'              => $success,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?route=apertura_cuentas/index");
            exit;
        }

        $id_estruc_presupuestaria = (int)($_POST['id_estruc_presupuestaria'] ?? 0);
        $fecha                    = $_POST['fecha']          ?? date('Y-m-d');
        $denominacion             = trim($_POST['denominacion_c'] ?? 'APERTURA DE CUENTAS');
        $referencia               = trim($_POST['referencia_c']   ?? '');
        $montos                   = $_POST['montos'] ?? [];  // Array id_codigo_plan_unico => monto

        if (!$id_estruc_presupuestaria || empty($montos)) {
            $_SESSION['error'] = 'Debe seleccionar una estructura y al menos una cuenta para aperturar.';
            header("Location: ?route=apertura_cuentas/index&id_estruc_presupuestaria=$id_estruc_presupuestaria");
            exit;
        }

        $db = Connection::getInstance();

        try {
            $db->beginTransaction();

            $nextId          = $this->comprobanteRepo->getNextId();
            $numeroComprobante = 'AAP-' . date('Y') . '-' . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);

            // 1. Guardar Cabecera (Comprobante)
            $comprobante = new ComprobantePresupuestario(
                'AAP',
                $numeroComprobante,
                $fecha,
                $denominacion,
                $referencia
            );
            $id_comprobante = $this->comprobanteRepo->save($comprobante);

            // 2. Guardar Detalles (Movimientos con monto inicial real)
            $cuentasInsertadas = 0;
            foreach ($montos as $id_codigo_plan_unico => $monto) {
                $movimiento = new MovimientoPresupuestario(
                    $id_comprobante,
                    $id_estruc_presupuestaria,
                    (int)$id_codigo_plan_unico,
                    'AAP',
                    (float)$monto,
                    'Apertura/Formulación Inicial'
                );
                $this->movimientoRepo->save($movimiento);
                $cuentasInsertadas++;
            }

            if ($cuentasInsertadas === 0) {
                throw new Exception("No se insertó ninguna cuenta válida.");
            }

            $db->commit();
            $_SESSION['success'] = "Comprobante $numeroComprobante generado con $cuentasInsertadas partida(s) aperturada(s).";
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'Error al generar la apertura: ' . $e->getMessage();
        }

        header("Location: ?route=apertura_cuentas/index&id_estruc_presupuestaria=$id_estruc_presupuestaria");
        exit;
    }
}
