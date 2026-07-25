<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;
use App\Models\InventarioBien;
use App\Repositories\InventarioBienesRepository;
use Exception;
use PDOException;

class InventarioBienesController extends BaseController
{
    private InventarioBienesRepository $repo;

    public function __construct()
    {
        $this->repo = new InventarioBienesRepository();
        Gate::authorize('inventario.bienes.ver');
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';

        try {
            $bienes = $this->repo->all($search);
        } catch (PDOException | Exception $e) {
            $bienes = [];
            $error = "Error al obtener bienes: " . $e->getMessage();
        }

        $this->renderView('inventario/bienes/index', [
            'titulo' => 'Catálogo de Bienes Patrimoniales',
            'bienes' => $bienes,
            'search' => $search,
            'error' => $error ?? $_GET['error'] ?? null,
            'success' => $_GET['success'] ?? null,
        ]);
    }

    public function form(): void
    {
        Gate::authorize('inventario.bienes.editar');
        $id = (int)($_GET['id'] ?? 0);
        
        $item = $this->repo->findById($id);
        if (!$item) {
            header('Location: ?route=inventario_bienes/index&error=Bien no encontrado');
            exit;
        }

        $this->renderView('inventario/bienes/form', [
            'titulo' => 'Editar Bien Patrimonial',
            'item' => $item,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function guardar(): void
    {
        Gate::authorize('inventario.bienes.editar');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Método no permitido');

        try {
            $id = (int)$_POST['id_inventario_bienes'];
            $item = $this->repo->findById($id);

            if (!$item) {
                throw new Exception("Bien no encontrado");
            }

            $datosAntes = $item->toArray();

            $item->vidaUtilMeses = (int)($_POST['vida_util_meses'] ?? 0);
            $item->valorResidual = (float)($_POST['valor_residual'] ?? 0);
            $item->idEstadoBienes = (int)($_POST['id_estado_bienes'] ?? $item->idEstadoBienes);
            $item->idUbicacionArticulo = (int)($_POST['id_ubicacion_articulo'] ?? $item->idUbicacionArticulo);

            $this->repo->save($item);

            $this->audit('inventario_bienes', 'EDITAR', $id, $datosAntes, $item->toArray());

            header('Location: ?route=inventario_bienes/index&success=Bien actualizado correctamente.');
            exit;
        } catch (Exception $e) {
            header('Location: ?route=inventario_bienes/form&id=' . ($_POST['id_inventario_bienes'] ?? '') . '&error=' . urlencode($e->getMessage()));
            exit;
        }
    }
}
