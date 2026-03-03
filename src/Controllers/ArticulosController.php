<?php

namespace App\Controllers;

use App\Models\Articulo;
use App\Repositories\ArticuloRepository;
use App\Repositories\TipoArticuloRepository;
use App\Repositories\UnidadMedidaRepository;
use PDOException;

class ArticulosController extends HomeController
{
    private ArticuloRepository $repo;
    private TipoArticuloRepository $tipoArticuloRepo;
    private UnidadMedidaRepository $unidadMedidaRepo;

    public function __construct(
        ArticuloRepository $repo,
        TipoArticuloRepository $tipoArticuloRepo,
        UnidadMedidaRepository $unidadMedidaRepo
    ) {
        $this->repo = $repo;
        $this->tipoArticuloRepo = $tipoArticuloRepo;
        $this->unidadMedidaRepo = $unidadMedidaRepo;
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';

        try {
            $articulos = $this->repo->all($search);
        } catch (PDOException | \Exception $e) {
            $articulos = [];
            $error = "Error al obtener la lista de artículos: " . $e->getMessage();
        }

        $this->renderView('compras/articulos/index', [
            'titulo' => 'Listado de Artículos (Catálogo)',
            'articulos' => $articulos,
            'search' => $search,
            'error' => $error ?? null,
        ]);
    }

    public function form(): void
    {
        $id = $_GET['id'] ?? null;
        $articulo = null;
        $error = null;

        try {
            if ($id) {
                // Fetch directly instead of iterating all
                $articulo = $this->repo->findById((int)$id);
            }
            $tiposArticulo = $this->tipoArticuloRepo->all();
            $unidadesMedida = $this->unidadMedidaRepo->all();
        } catch (PDOException | \Exception $e) {
            $error = "Error referencial BD: " . $e->getMessage();
            $tiposArticulo = [];
            $unidadesMedida = [];
        }

        $this->renderView('compras/articulos/form', [
            'titulo' => $articulo ? 'Editar Artículo' : 'Nuevo Artículo',
            'articulo' => $articulo,
            'tiposArticulo' => $tiposArticulo,
            'unidadesMedida' => $unidadesMedida,
            'error' => $error,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=articulos/index');
            exit;
        }

        try {
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $articulo = new Articulo(
                trim($_POST['denominacion'] ?? ''),
                trim($_POST['observacion'] ?? ''),
                (int)($_POST['id_tipo_de_articulo'] ?? 0),
                (int)($_POST['id_unidades_de_medida'] ?? 0),
                !empty($_POST['id_codigo_plan_unico']) ? (int)$_POST['id_codigo_plan_unico'] : null,
                isset($_POST['aplicar_iva']) && $_POST['aplicar_iva'] === '1',
                $id
            );

            $this->repo->save($articulo);
            header('Location: ?route=articulos/index');
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
            header('Location: ?route=articulos/index');
            exit;
        }
    }
}
