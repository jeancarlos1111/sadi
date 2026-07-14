<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AsientoContableRepository;
use App\Repositories\CuentaContableRepository;
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
        if (!isset($_SESSION['usuario'])) {
            header('Location: ?route=auth/login');
            exit;
        }
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
        $this->renderView('contabilidad/asientos/form', [
            'titulo' => 'Emitir Comprobante de Diario',
            'error' => 'La creación transaccional de comprobantes de diario manuales e integrados se encuentra en desarrollo.',
        ]);
    }
}
