<?php

namespace App\Controllers;

use App\Models\SolicitudPago;
use App\Repositories\BancoRepository;
use App\Repositories\CuentaBancariaRepository;
use App\Repositories\MovimientoBancarioRepository;
use App\Repositories\SolicitudPagoRepository;
use Exception;
use PDOException;

class SolicitudesPagoController extends HomeController
{
    private SolicitudPagoRepository $repo;
    private CuentaBancariaRepository $ctaRepo;
    private MovimientoBancarioRepository $movRepo;
    private BancoRepository $bancoRepo;

    public function __construct(
        SolicitudPagoRepository $repo,
        CuentaBancariaRepository $ctaRepo,
        MovimientoBancarioRepository $movRepo,
        BancoRepository $bancoRepo
    ) {
        $this->repo      = $repo;
        $this->ctaRepo   = $ctaRepo;
        $this->movRepo   = $movRepo;
        $this->bancoRepo = $bancoRepo;
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $mes = $_GET['mes'] ?? '';

        try {
            $solicitudes = $this->repo->all($search, $mes);
        } catch (PDOException $e) {
            $solicitudes = [];
            $error = "Error al obtener solicitudes: " . $e->getMessage();
        }

        $this->renderView('cuentas_por_pagar/solicitudes_pago/index', [
            'titulo' => 'Aprobación de Documentos (Solicitud de Pago)',
            'solicitudes' => $solicitudes,
            'search' => $search,
            'mes' => $mes,
            'error' => $error ?? null,
        ]);
    }

    public function pagar(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            die("ID de solicitud requerido.");
        }

        $solicitud = $this->repo->find($id);
        if (!$solicitud || $solicitud->estado === 'Aprobada/Contabilizada') {
            die("Solicitud inválida o ya pagada.");
        }

        try {
            $bancos      = $this->bancoRepo->all();
            $cuentas     = $this->ctaRepo->all();
            $operaciones = $this->movRepo->getTiposOperacion();
        } catch (Exception $e) {
            die("Error al cargar datos de bancos/cuentas: " . $e->getMessage());
        }

        $this->renderView('cuentas_por_pagar/solicitudes_pago/pagar', [
            'titulo' => 'Emitir Orden de Pago y Cheque',
            'solicitud' => $solicitud,
            'bancos' => $bancos,
            'cuentas' => $cuentas,
            'operaciones' => $operaciones,
        ]);
    }

    public function guardarPago(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id_solicitud_pago'] ?? null;
            $data = [
                'id_cta_bancaria' => $_POST['id_cta_bancaria'] ?? null,
                'id_tipo_operacion_bancaria' => $_POST['id_tipo_operacion_bancaria'] ?? null,
                'referencia' => $_POST['referencia'] ?? '',
                'fecha_pago' => $_POST['fecha_pago'] ?? date('Y-m-d'),
            ];

            try {
                $this->repo->registrarPago((int)$id, $data);
                header('Location: ?route=solicitudes_pago/index&success=Pago registrado correctamente y presupuesto fase PAGADO actualizado.');
                exit;
            } catch (Exception $e) {
                die("Error al procesar el pago: " . $e->getMessage());
            }
        }
    }

    /** Formulario para crear una solicitud de pago manualmente */
    public function form(): void
    {
        $this->renderView('cuentas_por_pagar/solicitudes_pago/form', [
            'titulo' => 'Nueva Solicitud de Pago',
            'error'  => null,
        ]);
    }

    /** POST: guardar solicitud de pago manual */
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=solicitudes_pago/index');
            exit;
        }

        $fecha    = trim($_POST['fecha_solicitud_pago'] ?? date('Y-m-d'));
        $concepto = trim($_POST['concepto_solicitud_pago'] ?? '');
        $monto    = (float)str_replace(',', '.', $_POST['monto_pagar_solicitud_pago'] ?? '0');

        if ($concepto === '' || $monto <= 0) {
            $this->renderView('cuentas_por_pagar/solicitudes_pago/form', [
                'titulo' => 'Nueva Solicitud de Pago',
                'error'  => 'El concepto y el monto (mayor a 0) son obligatorios.',
            ]);

            return;
        }

        try {
            $sp = new SolicitudPago($fecha, $concepto, $monto, 'Pendiente', null);
            $this->repo->save($sp);
            header('Location: ?route=solicitudes_pago/index');
            exit;
        } catch (Exception $e) {
            $this->renderView('cuentas_por_pagar/solicitudes_pago/form', [
                'titulo' => 'Nueva Solicitud de Pago',
                'error'  => 'Error al guardar: ' . $e->getMessage(),
            ]);
        }
    }

    public function reversar(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            header('Location: ?route=solicitudes_pago/index&error=ID de solicitud requerido.');
            exit;
        }

        try {
            $this->repo->reversarPago($id);
            header('Location: ?route=solicitudes_pago/index&success=Pago reversado correctamente. Presupuesto y bancos actualizados.');
            exit;
        } catch (Exception $e) {
            header('Location: ?route=solicitudes_pago/index&error=Error al reversar el pago: ' . $e->getMessage());
            exit;
        }
    }

    public function desprogramar(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            header('Location: ?route=solicitudes_pago/index&error=ID de solicitud requerido.');
            exit;
        }

        $solicitud = $this->repo->find($id);
        if (!$solicitud) {
            header('Location: ?route=solicitudes_pago/index&error=Solicitud invalida.');
            exit;
        }

        if ($solicitud->estado === 'Aprobada/Contabilizada') {
            header('Location: ?route=solicitudes_pago/index&error=No se puede desprogramar una solicitud que ya esta pagada.');
            exit;
        }

        try {
            $this->repo->delete($id);
            header('Location: ?route=solicitudes_pago/index&success=' . urlencode('Solicitud de pago desprogramada y devuelta/anulada exitosamente.'));
            exit;
        } catch (Exception $e) {
            header('Location: ?route=solicitudes_pago/index&error=' . urlencode('Error al desprogramar: ' . $e->getMessage()));
            exit;
        }
    }
}
