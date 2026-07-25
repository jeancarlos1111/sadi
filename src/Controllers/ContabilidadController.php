<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Auth\Gate;

use App\Repositories\AsientoContableRepository;
use App\Repositories\CuentaContableRepository;
use Exception;
use PDOException;

class ContabilidadController extends BaseController
{
    private CuentaContableRepository $cuentaRepo;
    private AsientoContableRepository $asientoRepo;

    public function __construct(
        CuentaContableRepository $cuentaRepo,
        AsientoContableRepository $asientoRepo
    ) {
        $this->cuentaRepo = $cuentaRepo;
        $this->asientoRepo = $asientoRepo;
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    private function redirect(string $url, string $message = '', string $type = 'success'): void
    {
        if ($message) {
            $_SESSION[$type] = $message;
        }
        header("Location: $url");
        exit;
    }

    public function planCuentas(): void
    {
        $search = $_GET['search'] ?? '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

        try {
            $paginator = $this->cuentaRepo->paginate($search, $page, 20);
            $cuentas = $paginator['data'];
            $pagination = $paginator;
        } catch (PDOException | \Exception $e) {
            $cuentas = [];
            $pagination = null;
            $error = "Error al obtener el plan de cuentas: " . $e->getMessage();
        }

        $this->renderView('contabilidad/cuentas/index', [
            'titulo' => 'Plan de Cuentas Contable',
            'cuentas' => $cuentas,
            'paginator' => $pagination,
            'search' => $search,
            'error' => $error ?? null,
        ]);
    }

    public function asientos(): void
    {
        $search = $_GET['search'] ?? '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

        try {
            $paginator = $this->asientoRepo->paginate($search, $page, 20);
            $asientos = $paginator['data'];
            $pagination = $paginator;
        } catch (PDOException $e) {
            $asientos = [];
            $pagination = null;
            $error = "Error al obtener comprobantes de diario: " . $e->getMessage();
        }

        $this->renderView('contabilidad/asientos/index', [
            'titulo' => 'Comprobantes de Diario (Asientos)',
            'asientos' => $asientos,
            'paginator' => $pagination,
            'search' => $search,
            'error' => $error ?? null,
        ]);
    }

    public function form(): void
    {
        Gate::authorize('contabilidad.asientos.crear');
        
        try {
            $cuentas = $this->cuentaRepo->all();
        } catch (Exception $e) {
            $cuentas = [];
        }

        $this->renderView('contabilidad/asientos/form', [
            'titulo' => 'Emitir Comprobante de Diario (Manual)',
            'cuentas' => $cuentas
        ]);
    }

    public function guardar(): void
    {
        Gate::authorize('contabilidad.asientos.crear');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?route=contabilidad/asientos', 'Método no permitido', 'error');
        }

        $fecha = $_POST['fecha_comprobante'] ?? date('Y-m-d');
        $concepto = trim($_POST['concepto'] ?? '');
        $cuentas = $_POST['cuenta'] ?? [];
        $tipos = $_POST['tipo'] ?? [];
        $montos = $_POST['monto'] ?? [];

        if (empty($concepto) || empty($cuentas)) {
            $this->redirect('?route=contabilidad/form', 'Faltan datos obligatorios o el asiento está vacío', 'error');
        }

        $movimientos = [];
        for ($i = 0; $i < count($cuentas); $i++) {
            $movimientos[] = [
                'id_cuenta' => (int)$cuentas[$i],
                'tipo' => $tipos[$i] === 'D' ? 'D' : 'H',
                'monto' => (float)$montos[$i]
            ];
        }

        try {
            $idComprobante = $this->asientoRepo->registrarDesdeTransaccion($fecha, $concepto, $movimientos);

            $this->audit('comprobante_diario', 'CREAR', $idComprobante, null, [
                'concepto' => $concepto, 
                'movimientos' => $movimientos
            ]);
            $this->redirect('?route=contabilidad/asientos', 'Comprobante guardado exitosamente', 'success');
        } catch (Exception $e) {
            $this->redirect('?route=contabilidad/form', 'Error al guardar asiento: ' . $e->getMessage(), 'error');
        }
    }

    public function anular(): void
    {
        Gate::authorize('contabilidad.asientos.eliminar');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?route=contabilidad/asientos', 'Método no permitido', 'error');
        }

        $id = (int)($_POST['id'] ?? 0);
        $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

        if ($id <= 0) {
            $this->redirect('?route=contabilidad/asientos', 'ID inválido', 'error');
        }

        try {
            $this->asientoRepo->anular($id, $usuarioId);
            $this->audit('comprobante_diario', 'ANULAR', $id, null, ['id_comprobante' => $id]);
            $this->redirect('?route=contabilidad/asientos', 'Comprobante anulado exitosamente', 'success');
        } catch (Exception $e) {
            $this->redirect('?route=contabilidad/asientos', 'Error al anular: ' . $e->getMessage(), 'error');
        }
    }
}
