<?php

namespace App\Controllers;

use App\Models\Beneficiario;
use App\Repositories\BeneficiarioRepository;
use PDOException;

class BeneficiariosController extends HomeController
{
    private BeneficiarioRepository $repo;

    public function __construct(BeneficiarioRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';

        try {
            $beneficiarios = $this->repo->all($search);
        } catch (PDOException | \Exception $e) {
            $beneficiarios = [];
            $error = "Error al obtener beneficiarios: " . $e->getMessage();
        }
        $this->renderView('beneficiarios/index', [
            'titulo'        => 'Listado de Beneficiarios',
            'beneficiarios' => $beneficiarios,
            'search'        => $search,
            'error'         => $error ?? null,
        ]);
    }

    public function form(): void
    {
        $id          = $_GET['id'] ?? null;
        $beneficiario = null;
        $error       = null;

        try {
            if ($id) {
                $beneficiario = $this->repo->findById((int)$id);
            }
        } catch (PDOException | \Exception $e) {
            $error = "Error DB: " . $e->getMessage();
        }
        $this->renderView('beneficiarios/form', [
            'titulo'      => $beneficiario ? 'Editar Beneficiario' : 'Nuevo Beneficiario',
            'beneficiario' => $beneficiario,
            'error'       => $error,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=beneficiarios/index');
            exit;
        }

        try {
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $b  = new Beneficiario(
                trim($_POST['cedula']    ?? ''),
                trim($_POST['nombres']   ?? ''),
                trim($_POST['apellidos'] ?? ''),
                trim($_POST['direccion'] ?? ''),
                trim($_POST['telefono']  ?? ''),
                trim($_POST['email']     ?? '') ?: null,
                !empty($_POST['id_codigo_contable']) ? (int)$_POST['id_codigo_contable'] : null,
                $id
            );
            $this->repo->save($b);
            header('Location: ?route=beneficiarios/index');
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
                die("Error al eliminar: " . $e->getMessage());
            }
        }
        header('Location: ?route=beneficiarios/index');
        exit;
    }
}
