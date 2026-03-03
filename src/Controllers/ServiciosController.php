<?php

namespace App\Controllers;

use App\Models\Servicio;
use App\Repositories\PlanUnicoCuentasRepository;
use App\Repositories\ServicioRepository;
use App\Repositories\TipoServicioRepository;
use PDOException;

class ServiciosController extends HomeController
{
    private ServicioRepository $repo;
    private TipoServicioRepository $tipoServicioRepo;
    private PlanUnicoCuentasRepository $planCuentasRepo;

    public function __construct(
        ServicioRepository $repo,
        TipoServicioRepository $tipoServicioRepo,
        PlanUnicoCuentasRepository $planCuentasRepo
    ) {
        $this->repo = $repo;
        $this->tipoServicioRepo = $tipoServicioRepo;
        $this->planCuentasRepo = $planCuentasRepo;
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';

        try {
            $items = $this->repo->all($search);
        } catch (PDOException | \Exception $e) {
            $items = [];
            $error = "Error: " . $e->getMessage();
        }
        $this->renderView('servicios/index', [
            'titulo' => 'Catálogo de Servicios',
            'items'  => $items,
            'search' => $search,
            'error'  => $error ?? null,
        ]);
    }

    public function form(): void
    {
        $id   = $_GET['id'] ?? null;
        $item = null;

        try {
            if ($id) {
                // Fetch directly instead of iterating all
                $item = $this->repo->findById((int)$id);
            }
            $tiposServicio = $this->tipoServicioRepo->all();
            $cuentas       = $this->planCuentasRepo->all();
        } catch (PDOException | \Exception $e) {
            $error = "Error DB: " . $e->getMessage();
            $tiposServicio = [];
            $cuentas = [];
        }
        $this->renderView('servicios/form', [
            'titulo'        => $item ? 'Editar Servicio' : 'Nuevo Servicio',
            'item'          => $item,
            'tiposServicio' => $tiposServicio,
            'cuentas'       => $cuentas,
            'error'         => $error ?? null,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=servicios/index');
            exit;
        }

        try {
            $id   = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $idPartida = !empty($_POST['id_codigo_plan_unico']) ? (int)$_POST['id_codigo_plan_unico'] : null;
            $item = new Servicio(
                trim($_POST['denominacion']    ?? ''),
                trim($_POST['descripcion']     ?? '') ?: null,
                (int)($_POST['id_tipo_servicio'] ?? 0),
                !empty($_POST['aplicar_iva']),
                $idPartida,
                $id
            );
            $this->repo->save($item);
            header('Location: ?route=servicios/index');
            exit;
        } catch (PDOException | \Exception $e) {
            die("Error al guardar: " . $e->getMessage());
        }
    }

    public function eliminar(): void
    {
        $id = $_POST['id'] ?? null;
        if ($id) {
            try {
                $this->repo->delete((int)$id);
            } catch (PDOException | \Exception $e) {
                die("Error: " . $e->getMessage());
            }
        }
        header('Location: ?route=servicios/index');
        exit;
    }
}
