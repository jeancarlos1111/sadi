<?php

namespace App\Controllers;

use App\Models\RequisicionBienes;
use App\Repositories\ArticuloRepository;
use App\Repositories\RequisicionBienesRepository;
use PDOException;

class RequisicionesBienesController extends HomeController
{
    private RequisicionBienesRepository $repo;
    private ArticuloRepository $articuloRepo;

    public function __construct(RequisicionBienesRepository $repo, ArticuloRepository $articuloRepo)
    {
        $this->repo = $repo;
        $this->articuloRepo = $articuloRepo;
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $mes = $_GET['mes'] ?? '';

        try {
            $requisiciones = $this->repo->all($search, $mes);
        } catch (PDOException | \Exception $e) {
            $requisiciones = [];
            $error = "Error al obtener las requisiciones: " . $e->getMessage();
        }

        $this->renderView('compras/requisiciones_bienes/index', [
            'titulo' => 'Listado de Requisiciones de Bienes',
            'requisiciones' => $requisiciones,
            'search' => $search,
            'mes' => $mes,
            'error' => $error ?? null,
        ]);
    }

    public function form(): void
    {
        $id = $_GET['id'] ?? null;
        $requisicion = null;
        $error = null;

        try {
            if ($id) {
                $requisicion = $this->repo->findById((int)$id);
            }
            $articulosCatalogo = $this->articuloRepo->all(); // Provide items to select
        } catch (PDOException | \Exception $e) {
            $error = "Error referencial BD: " . $e->getMessage();
            $articulosCatalogo = [];
        }

        $this->renderView('compras/requisiciones_bienes/form', [
            'titulo' => $requisicion ? 'Editar Requisición de Bienes' : 'Nueva Requisición de Bienes',
            'requisicion' => $requisicion,
            'articulosCatalogo' => $articulosCatalogo,
            'error' => $error,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=requisiciones_bienes/index');
            exit;
        }

        try {
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;

            // Extract the articles from the dynamic HTML form (arrays of id_articulo and cantidad)
            $articulosInput = $_POST['articulos'] ?? [];
            $cantidadesInput = $_POST['cantidades'] ?? [];

            $articulos = [];
            foreach ($articulosInput as $idx => $idArt) {
                if ($idArt && !empty($cantidadesInput[$idx])) {
                    $articulos[] = [
                        'id_articulo' => (int)$idArt,
                        'cantidad' => (float)$cantidadesInput[$idx],
                    ];
                }
            }

            $requisicion = new RequisicionBienes(
                trim($_POST['fecha'] ?? date('Y-m-d')),
                trim($_POST['concepto'] ?? ''),
                (int)($_POST['id_estructura_presupuestaria'] ?? 0),
                $articulos,
                $id
            );

            $this->repo->save($requisicion);
            header('Location: ?route=requisiciones_bienes/index');
            exit;
        } catch (\Exception $e) {
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
            header('Location: ?route=requisiciones_bienes/index');
            exit;
        }
    }
}
