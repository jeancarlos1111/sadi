<?php

namespace App\Controllers;

use App\Models\AccionCentralizada;
use App\Repositories\AccionCentralizadaRepository;
use PDOException;

class AccionesCentralizadasController extends HomeController
{
    private AccionCentralizadaRepository $repo;

    public function __construct(AccionCentralizadaRepository $repo)
    {
        $this->repo = $repo;
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
        $this->renderView('acciones_centralizadas/index', [
            'titulo' => 'Catálogo de Acciones Centralizadas',
            'items'  => $items,
            'search' => $search,
            'error'  => $error ?? null,
        ]);
    }

    public function form(): void
    {
        $id = $_GET['id'] ?? null;
        $item = null;

        try {
            if ($id) {
                $item = $this->repo->findById((int)$id);
            }
        } catch (PDOException | \Exception $e) {
            $error = "Error DB: " . $e->getMessage();
        }
        $this->renderView('acciones_centralizadas/form', [
            'titulo' => $item ? 'Editar Acción Centralizada / Metas' : 'Nueva Acción Centralizada / Metas',
            'item'   => $item,
            'error'  => $error ?? null,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=acciones_centralizadas/index');
            exit;
        }

        try {
            $id   = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $item = new AccionCentralizada(
                trim($_POST['codigo_accion_centralizada'] ?? ''),
                trim($_POST['denominacion']    ?? ''),
                trim($_POST['unidad_medida']   ?? ''),
                trim($_POST['anio_inicio']     ?? ''),
                trim($_POST['anio_culm']       ?? ''),
                (float)($_POST['cant_programada_trim_i']   ?? 0),
                (float)($_POST['cant_ejecutada_trim_i']    ?? 0),
                (float)($_POST['cant_programada_trim_ii']  ?? 0),
                (float)($_POST['cant_ejecutada_trim_ii']   ?? 0),
                (float)($_POST['cant_programada_trim_iii'] ?? 0),
                (float)($_POST['cant_ejecutada_trim_iii']  ?? 0),
                (float)($_POST['cant_programada_trim_iv']  ?? 0),
                (float)($_POST['cant_ejecutada_trim_iv']   ?? 0),
                $id
            );
            $this->repo->save($item);
            header('Location: ?route=acciones_centralizadas/index');
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
        header('Location: ?route=acciones_centralizadas/index');
        exit;
    }
}
