<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\IndicadorProyecto;
use App\Models\Proyecto;
use App\Repositories\ProyectoRepository;
use PDOException;

class ProyectosController extends HomeController
{
    private ProyectoRepository $repo;
    private \App\Repositories\UnidadAdministrativaRepository $uaRepo;
    private \App\Repositories\IndicadorProyectoRepository $indRepo;

    public function __construct(
        ProyectoRepository $repo,
        \App\Repositories\UnidadAdministrativaRepository $uaRepo,
        \App\Repositories\IndicadorProyectoRepository $indRepo
    ) {
        $this->repo = $repo;
        $this->uaRepo = $uaRepo;
        $this->indRepo = $indRepo;
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';

        try {
            $page = (int)($_GET['page'] ?? 1);
            $paginator = $this->repo->paginate($search, $page, 15);
            $items = $paginator['data'];
        } catch (PDOException | \Exception $e) {
            $items = [];
            $error = "Error: " . $e->getMessage();
        }
        $this->renderView('proyectos/index', [
            'titulo' => 'Catálogo de Proyectos',
            'items'  => $items,
            'search' => $search,
            'error'  => $error ?? null,
                    'paginator' => $paginator,
        ]);
    }

    public function form(): void
    {
        $id = $_GET['id'] ?? null;
        $item = null;

        $indicadores = [];

        try {
            if ($id) {
                $item = $this->repo->findById((int)$id);
                if ($item) {
                    $indicadores = $this->indRepo->findByProyectoId($item->id_proyecto);
                }
            }
            $unidades = $this->uaRepo->all();
        } catch (PDOException | \Exception $e) {
            $error = "Error DB: " . $e->getMessage();
            $unidades = [];
        }
        $this->renderView('proyectos/form', [
            'titulo' => $item ? 'Editar Proyecto / Metas' : 'Nuevo Proyecto / Metas',
            'item'   => $item,
            'indicadores' => $indicadores,
            'unidades' => $unidades,
            'error'  => $error ?? null,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=proyectos/index');
            exit;
        }

        try {
            $id   = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $item = new Proyecto(
                trim($_POST['codigo_proyecto'] ?? ''),
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
                !empty($_POST['indicador_eficacia']) ? trim($_POST['indicador_eficacia']) : null,
                !empty($_POST['indicador_eficiencia']) ? trim($_POST['indicador_eficiencia']) : null,
                !empty($_POST['indicador_calidad']) ? trim($_POST['indicador_calidad']) : null,
                !empty($_POST['indicador_impacto']) ? trim($_POST['indicador_impacto']) : null,
                !empty($_POST['medio_verificacion']) ? trim($_POST['medio_verificacion']) : null,
                !empty($_POST['id_unidad_administrativa']) ? (int)$_POST['id_unidad_administrativa'] : null,
                $id
            );
            $proyectoId = $this->repo->save($item);

            // Guardar indicadores
            if (!empty($_POST['ind_eficacia']) && is_array($_POST['ind_eficacia'])) {
                // Para simplificar, borramos los anteriores y los volvemos a insertar
                // Aunque idealmente se podría hacer un update si se pasa un id
                // Haremos delete e insert para ser prácticos
                $db = $this->repo->getPdo();
                $stmt = $db->prepare("DELETE FROM indicador_proyecto WHERE id_proyecto = ?");
                $stmt->execute([$proyectoId]);

                foreach ($_POST['ind_eficacia'] as $index => $eficacia) {
                    $ind = new IndicadorProyecto(
                        $proyectoId,
                        !empty($eficacia) ? trim($eficacia) : null,
                        !empty($_POST['ind_eficiencia'][$index]) ? trim($_POST['ind_eficiencia'][$index]) : null,
                        !empty($_POST['ind_calidad'][$index]) ? trim($_POST['ind_calidad'][$index]) : null,
                        !empty($_POST['ind_impacto'][$index]) ? trim($_POST['ind_impacto'][$index]) : null,
                        !empty($_POST['ind_medio'][$index]) ? trim($_POST['ind_medio'][$index]) : null
                    );
                    $this->indRepo->save($ind);
                }
            }

            header('Location: ?route=proyectos/index');
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
        header('Location: ?route=proyectos/index');
        exit;
    }
}
