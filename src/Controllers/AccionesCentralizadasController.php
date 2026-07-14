<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AccionCentralizada;
use App\Models\IndicadorAccionCentralizada;
use App\Repositories\AccionCentralizadaRepository;
use PDOException;

class AccionesCentralizadasController extends HomeController
{
    private AccionCentralizadaRepository $repo;
    private \App\Repositories\UnidadAdministrativaRepository $uaRepo;
    private \App\Repositories\IndicadorAccionCentralizadaRepository $indRepo;

    public function __construct(
        AccionCentralizadaRepository $repo,
        \App\Repositories\UnidadAdministrativaRepository $uaRepo,
        \App\Repositories\IndicadorAccionCentralizadaRepository $indRepo
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
        $this->renderView('acciones_centralizadas/index', [
            'titulo' => 'Catálogo de Acciones Centralizadas',
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
                    $indicadores = $this->indRepo->findByAccionCentralizadaId($item->id_accion_centralizada);
                }
            }
            $unidades = $this->uaRepo->all();
        } catch (PDOException | \Exception $e) {
            $error = "Error DB: " . $e->getMessage();
            $unidades = [];
        }
        $this->renderView('acciones_centralizadas/form', [
            'titulo' => $item ? 'Editar Acción Centralizada' : 'Nueva Acción Centralizada',
            'item'   => $item,
            'indicadores' => $indicadores,
            'unidades' => $unidades,
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
                !empty($_POST['indicador_eficacia']) ? trim($_POST['indicador_eficacia']) : null,
                !empty($_POST['indicador_eficiencia']) ? trim($_POST['indicador_eficiencia']) : null,
                !empty($_POST['indicador_calidad']) ? trim($_POST['indicador_calidad']) : null,
                !empty($_POST['indicador_impacto']) ? trim($_POST['indicador_impacto']) : null,
                !empty($_POST['medio_verificacion']) ? trim($_POST['medio_verificacion']) : null,
                !empty($_POST['id_unidad_administrativa']) ? (int)$_POST['id_unidad_administrativa'] : null,
                $id
            );
            $acId = $this->repo->save($item);

            // Guardar indicadores
            if (!empty($_POST['ind_eficacia']) && is_array($_POST['ind_eficacia'])) {
                $db = $this->repo->getPdo();
                $stmt = $db->prepare("DELETE FROM indicador_accion_centralizada WHERE id_accion_centralizada = ?");
                $stmt->execute([$acId]);

                foreach ($_POST['ind_eficacia'] as $index => $eficacia) {
                    $ind = new IndicadorAccionCentralizada(
                        $acId,
                        !empty($eficacia) ? trim($eficacia) : null,
                        !empty($_POST['ind_eficiencia'][$index]) ? trim($_POST['ind_eficiencia'][$index]) : null,
                        !empty($_POST['ind_calidad'][$index]) ? trim($_POST['ind_calidad'][$index]) : null,
                        !empty($_POST['ind_impacto'][$index]) ? trim($_POST['ind_impacto'][$index]) : null,
                        !empty($_POST['ind_medio'][$index]) ? trim($_POST['ind_medio'][$index]) : null
                    );
                    $this->indRepo->save($ind);
                }
            }

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
