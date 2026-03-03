<?php

namespace App\Controllers;

use App\Models\Proveedor;
use App\Repositories\ProveedorRepository;
use App\Repositories\TipoOrganizacionRepository;
use PDOException;

class ProveedoresController extends HomeController
{
    private ProveedorRepository $repo;
    private TipoOrganizacionRepository $tipoOrgRepo;

    public function __construct(ProveedorRepository $repo, TipoOrganizacionRepository $tipoOrgRepo)
    {
        $this->repo = $repo;
        $this->tipoOrgRepo = $tipoOrgRepo;
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';

        try {
            $proveedores = $this->repo->all($search);
        } catch (PDOException | \Exception $e) {
            $proveedores = [];
            $error = "Error al obtener proveedores: " . $e->getMessage();
        }

        $this->renderView('proveedores/index', [
            'titulo' => 'Listado de Proveedores',
            'proveedores' => $proveedores,
            'search' => $search,
            'error' => $error ?? null,
        ]);
    }

    public function form(): void
    {
        $id = $_GET['id'] ?? null;
        $proveedor = null;
        $error = null;

        try {
            if ($id) {
                // Obteniendo directamente por ID
                $proveedor = $this->repo->findById((int)$id);
            }
            $tiposOrganizacion = $this->tipoOrgRepo->all();
        } catch (PDOException | \Exception $e) {
            $error = "Error DB: " . $e->getMessage();
            $tiposOrganizacion = [];
        }

        $this->renderView('proveedores/form', [
            'titulo' => $proveedor ? 'Editar Proveedor' : 'Nuevo Proveedor',
            'proveedor' => $proveedor,
            'tiposOrganizacion' => $tiposOrganizacion,
            'error' => $error,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=proveedores/index');
            exit;
        }

        try {
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $proveedor = new Proveedor(
                trim($_POST['rif'] ?? ''),
                trim($_POST['compania'] ?? ''),
                (int)($_POST['id_tipo_organizacion'] ?? 0),
                trim($_POST['direccion'] ?? ''),
                trim($_POST['telefono'] ?? ''),
                trim($_POST['nit'] ?? '') !== '' ? trim($_POST['nit']) : null,
                !empty($_POST['id_codigo_contable']) ? (int)$_POST['id_codigo_contable'] : null,
                $id
            );

            $this->repo->save($proveedor);
            header('Location: ?route=proveedores/index');
            exit;
        } catch (PDOException | \Exception $e) {
            // Un manejo de errores más sofisticado iría a session flash messages
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
                die("Error al eliminar: " . $e->getMessage());
            }
            header('Location: ?route=proveedores/index');
            exit;
        }
    }
}
