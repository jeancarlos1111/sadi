<?php

namespace App\Controllers;

use App\Models\ComprobantePresupuestario;
use App\Models\MovimientoPresupuestario;
use App\Repositories\ComprobantePresupuestarioRepository;
use App\Repositories\MovimientoPresupuestarioRepository;
use App\Repositories\EstrucPresupuestariaRepository;
use App\Repositories\PlanUnicoCuentasRepository;
use App\Repositories\BeneficiarioRepository;
use App\Database\Connection;
use Exception;

/**
 * Gestiona los Comprobantes de Gasto, Créditos Adicionales y Traspasos
 * Operaciones disponibles: CG (Gasto), CA (Crédito Adicional), TR (Traspaso/Reducción)
 */
class ComprobantePresupuestoController extends HomeController
{
    // Operaciones que AUMENTAN el saldo disponible
    private const OPS_AUMENTAN = ['AAP', 'CA'];

    // Operaciones que REDUCEN el saldo disponible
    private const OPS_REDUCEN = ['CG', 'TR'];

    /** Mapa legible de operaciones */
    private const TIPOS = [
        'CG' => 'Comprobante de Gasto',
        'CA' => 'Crédito Adicional',
        'TR' => 'Traspaso / Reducción de Crédito',
    ];

    private ComprobantePresupuestarioRepository $comprobanteRepo;
    private MovimientoPresupuestarioRepository $movimientoRepo;
    private EstrucPresupuestariaRepository $estructurasRepo;
    private PlanUnicoCuentasRepository $planUnicoRepo;
    private BeneficiarioRepository $beneficiarioRepo;

    public function __construct()
    {
        parent::__construct();
        $this->comprobanteRepo  = new ComprobantePresupuestarioRepository();
        $this->movimientoRepo   = new MovimientoPresupuestarioRepository();
        $this->estructurasRepo  = new EstrucPresupuestariaRepository();
        $this->planUnicoRepo    = new PlanUnicoCuentasRepository();
        $this->beneficiarioRepo = new BeneficiarioRepository();
    }

    /** Listado de todos los comprobantes (excluyendo AAP) */
    public function index(): void
    {
        $search     = trim($_GET['search'] ?? '');
        $tipo_filtro = trim($_GET['tipo'] ?? '');

        $comprobantes = $this->comprobanteRepo->allExcept(['AAP'], $search, $tipo_filtro);

        $error   = $_SESSION['error']   ?? null;
        $success = $_SESSION['success'] ?? null;
        unset($_SESSION['error'], $_SESSION['success']);

        $this->renderView('comprobante_presupuesto/index', [
            'titulo'       => 'Comprobantes Presupuestarios',
            'items'        => $comprobantes,
            'search'       => $search,
            'tipo_filtro'  => $tipo_filtro,
            'tipos'        => self::TIPOS,
            'error'        => $error,
            'success'      => $success,
        ]);
    }

    /** Formulario de nuevo / editar comprobante */
    public function form(): void
    {
        $id    = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $tipo  = strtoupper($_GET['tipo'] ?? 'CG'); // default: Gasto
        $item  = $id ? $this->comprobanteRepo->findById($id) : null;
        $lineas = $id ? $this->movimientoRepo->findByComprobanteId($id) : [];

        $estructuras  = $this->estructurasRepo->all();
        $planCuentas  = $this->planUnicoRepo->all();
        $beneficiarios = $this->beneficiarioRepo->all();

        $this->renderView('comprobante_presupuesto/form', [
            'titulo'       => ($id ? 'Editar' : 'Nuevo') . ' ' . (self::TIPOS[$tipo] ?? 'Comprobante'),
            'item'         => $item,
            'lineas'       => $lineas,
            'tipo'         => $tipo,
            'tipos'        => self::TIPOS,
            'estructuras'  => $estructuras,
            'planCuentas'  => $planCuentas,
            'beneficiarios'=> $beneficiarios,
            'error'        => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['error']);
    }

    /** Guarda/actualiza el comprobante y sus movimientos */
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?route=comprobante_presupuesto/index");
            exit;
        }

        $id_comprobante_existente = (int)($_POST['id_comprobante'] ?? 0) ?: null;
        $acronimo      = strtoupper(trim($_POST['acronimo_c'] ?? 'CG'));
        $fecha         = $_POST['fecha_c'] ?? date('Y-m-d');
        $denominacion  = trim($_POST['denominacion_c'] ?? '');
        $referencia    = trim($_POST['referencia_c'] ?? '');
        $beneficiario  = trim($_POST['beneficiario_cedula'] ?? '');

        // Datos de las líneas de detalles
        $id_estructuras   = $_POST['id_estruc'] ?? [];
        $id_cuentas       = $_POST['id_cuenta'] ?? [];
        $montos           = $_POST['monto'] ?? [];
        $descripciones    = $_POST['descripcion_linea'] ?? [];

        if (empty($denominacion) || count($id_estructuras) === 0) {
            $_SESSION['error'] = 'La denominación y al menos una línea de detalle son obligatorias.';
            $redirect = $id_comprobante_existente
                ? "?route=comprobante_presupuesto/form&id=$id_comprobante_existente"
                : "?route=comprobante_presupuesto/form&tipo=$acronimo";
            header("Location: $redirect");
            exit;
        }

        $db = Connection::getInstance();

        try {
            $db->beginTransaction();

            // Si es nuevo, generar número de correlativo
            if (!$id_comprobante_existente) {
                $nextId = $this->comprobanteRepo->getNextId();
                $numero = $acronimo . '-' . date('Y') . '-' . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
            } else {
                $existing = $this->comprobanteRepo->findById($id_comprobante_existente);
                $numero   = $existing->numero_c ?? '';
            }

            $comprobante = new ComprobantePresupuestario(
                $acronimo,
                $numero,
                $fecha,
                $denominacion,
                $referencia,
                $beneficiario ?: null,
                'APROBADO',
                $id_comprobante_existente
            );
            $id_comprobante = $this->comprobanteRepo->save($comprobante);

            // Si estamos editando, eliminamos lógicamente las líneas viejas
            if ($id_comprobante_existente) {
                $this->movimientoRepo->deleteByComprobanteId($id_comprobante_existente);
            }

            // Guardar nuevas líneas
            $n = count($id_estructuras);
            for ($i = 0; $i < $n; $i++) {
                $id_ep  = (int)($id_estructuras[$i] ?? 0);
                $id_cpu = (int)($id_cuentas[$i] ?? 0);
                $monto  = (float)($montos[$i] ?? 0);
                $desc   = trim($descripciones[$i] ?? '');

                if (!$id_ep || !$id_cpu || $monto <= 0) {
                    continue; // saltar líneas vacías
                }

                $movimiento = new MovimientoPresupuestario(
                    $id_comprobante,
                    $id_ep,
                    $id_cpu,
                    $acronimo,
                    $monto,
                    $desc ?: null
                );
                $this->movimientoRepo->save($movimiento);
            }

            $db->commit();
            $_SESSION['success'] = "Comprobante $numero guardado correctamente.";
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'Error al guardar: ' . $e->getMessage();
        }

        header("Location: ?route=comprobante_presupuesto/index");
        exit;
    }

    /** Eliminación lógica del comprobante y sus movimientos */
    public function eliminar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?route=comprobante_presupuesto/index");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db = Connection::getInstance();
            try {
                $db->beginTransaction();
                $this->movimientoRepo->deleteByComprobanteId($id);
                $this->comprobanteRepo->delete($id);
                $db->commit();
                $_SESSION['success'] = "Comprobante eliminado correctamente.";
            } catch (Exception $e) {
                $db->rollBack();
                $_SESSION['error'] = 'Error al eliminar: ' . $e->getMessage();
            }
        }

        header("Location: ?route=comprobante_presupuesto/index");
        exit;
    }

    /** Consulta de disponibilidad presupuestaria por estructura y cuenta */
    public function disponibilidad(): void
    {
        $id_estruc  = (int)($_GET['id_estruc'] ?? 0);
        $id_cuenta  = (int)($_GET['id_cuenta'] ?? 0);

        $disponible = 0.0;
        if ($id_estruc && $id_cuenta) {
            $disponible = $this->movimientoRepo->getDisponible($id_estruc, $id_cuenta);
        }

        header('Content-Type: application/json');
        echo json_encode(['disponible' => $disponible]);
        exit;
    }
}
