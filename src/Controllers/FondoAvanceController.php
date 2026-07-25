<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;
use App\Models\FondoAvance;
use App\Models\FondoAvanceReposicion;
use App\Models\FondoAvanceGasto;
use App\Repositories\FondoAvanceRepository;
use Exception;
use PDOException;

class FondoAvanceController extends BaseController
{
    private FondoAvanceRepository $repo;

    public function __construct()
    {
        $this->repo = new FondoAvanceRepository();
        Gate::authorize('tesoreria.fondo_avance.ver');
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';

        try {
            $fondos = $this->repo->all($search);
        } catch (PDOException | Exception $e) {
            $fondos = [];
            $error = "Error al obtener los fondos: " . $e->getMessage();
        }

        $this->renderView('tesoreria/fondo_avance/index', [
            'titulo' => 'Fondos en Avance (ONCOP)',
            'fondos' => $fondos,
            'search' => $search,
            'error' => $error ?? $_GET['error'] ?? null,
            'success' => $_GET['success'] ?? null,
        ]);
    }

    public function form(): void
    {
        Gate::authorize('tesoreria.fondo_avance.crear');
        $id = (int)($_GET['id'] ?? 0);
        $fondo = $id > 0 ? $this->repo->findById($id) : null;

        $this->renderView('tesoreria/fondo_avance/form', [
            'titulo' => $fondo ? 'Editar Fondo en Avance' : 'Nuevo Fondo en Avance',
            'fondo' => $fondo,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function guardar(): void
    {
        Gate::authorize('tesoreria.fondo_avance.crear');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Método no permitido');

        try {
            $id = (int)($_POST['id_fondo'] ?? 0);
            
            $item = new FondoAvance(
                $_POST['denominacion'],
                (float)$_POST['monto_maximo'],
                $_POST['responsable_cedula'],
                $_POST['fecha_creacion'] ?? date('Y-m-d'),
                $_POST['estado'] ?? 'ACTIVO',
                !empty($_POST['id_cuenta_contable']) ? (int)$_POST['id_cuenta_contable'] : null,
                $id > 0 ? $id : null
            );

            $nuevoId = $this->repo->save($item);

            $this->audit('fondo_avance', $id > 0 ? 'EDITAR' : 'CREAR', $nuevoId, null, $item->toArray());

            header('Location: ?route=fondo_avance/index&success=Fondo guardado correctamente.');
            exit;
        } catch (Exception $e) {
            header('Location: ?route=fondo_avance/form&id=' . ($id ?? '') . '&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function reposiciones(): void
    {
        Gate::authorize('tesoreria.fondo_avance.ver');
        $idFondo = (int)($_GET['id_fondo'] ?? 0);
        
        $fondo = $this->repo->findById($idFondo);
        if (!$fondo) {
            header('Location: ?route=fondo_avance/index&error=Fondo no encontrado');
            exit;
        }

        try {
            $reposiciones = $this->repo->getReposiciones($idFondo);
        } catch (Exception $e) {
            $reposiciones = [];
            $error = "Error: " . $e->getMessage();
        }

        $this->renderView('tesoreria/fondo_avance/reposiciones', [
            'titulo' => 'Reposiciones / Rendición - ' . $fondo->denominacion,
            'fondo' => $fondo,
            'reposiciones' => $reposiciones,
            'error' => $error ?? $_GET['error'] ?? null,
            'success' => $_GET['success'] ?? null,
        ]);
    }

    public function nuevaReposicion(): void
    {
        Gate::authorize('tesoreria.fondo_avance.crear');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Método no permitido');

        $idFondo = (int)$_POST['id_fondo'];

        try {
            $item = new FondoAvanceReposicion(
                $idFondo,
                date('Y-m-d'),
                0.0,
                'PENDIENTE'
            );
            $id = $this->repo->saveReposicion($item);

            $this->audit('fondo_avance_reposicion', 'CREAR', $id, null, $item->toArray());

            header('Location: ?route=fondo_avance/gastos&id_reposicion=' . $id . '&success=Nueva reposición iniciada.');
            exit;
        } catch (Exception $e) {
            header('Location: ?route=fondo_avance/reposiciones&id_fondo=' . $idFondo . '&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function gastos(): void
    {
        Gate::authorize('tesoreria.fondo_avance.editar');
        $idReposicion = (int)($_GET['id_reposicion'] ?? 0);
        
        $reposicion = $this->repo->findReposicionById($idReposicion);
        if (!$reposicion) {
            header('Location: ?route=fondo_avance/index&error=Reposición no encontrada');
            exit;
        }

        $fondo = $this->repo->findById($reposicion->idFondo);
        $gastos = $this->repo->getGastos($idReposicion);

        $this->renderView('tesoreria/fondo_avance/gastos', [
            'titulo' => 'Gastos de Rendición (Fondo: ' . $fondo->denominacion . ')',
            'reposicion' => $reposicion,
            'fondo' => $fondo,
            'gastos' => $gastos,
            'error' => $_GET['error'] ?? null,
            'success' => $_GET['success'] ?? null,
        ]);
    }

    public function guardarGasto(): void
    {
        Gate::authorize('tesoreria.fondo_avance.editar');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Método no permitido');

        $idReposicion = (int)$_POST['id_reposicion'];

        try {
            $item = new FondoAvanceGasto(
                $idReposicion,
                $_POST['fecha_gasto'],
                $_POST['concepto'],
                (float)$_POST['monto'],
                $_POST['factura_num'] ?: null,
                $_POST['proveedor_rif'] ?: null
            );
            
            $idGasto = $this->repo->saveGasto($item);
            
            $this->audit('fondo_avance_gasto', 'CREAR', $idGasto, null, $item->toArray());

            header('Location: ?route=fondo_avance/gastos&id_reposicion=' . $idReposicion . '&success=Gasto agregado.');
            exit;
        } catch (Exception $e) {
            header('Location: ?route=fondo_avance/gastos&id_reposicion=' . $idReposicion . '&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function eliminarGasto(): void
    {
        Gate::authorize('tesoreria.fondo_avance.editar');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Método no permitido');

        $idGasto = (int)$_POST['id_gasto'];
        $idReposicion = (int)$_POST['id_reposicion'];

        try {
            $this->repo->deleteGasto($idGasto);
            $this->audit('fondo_avance_gasto', 'ELIMINAR', $idGasto, null, null);

            header('Location: ?route=fondo_avance/gastos&id_reposicion=' . $idReposicion . '&success=Gasto eliminado.');
            exit;
        } catch (Exception $e) {
            header('Location: ?route=fondo_avance/gastos&id_reposicion=' . $idReposicion . '&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function enviarReposicion(): void
    {
        Gate::authorize('tesoreria.fondo_avance.editar');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Método no permitido');

        $idReposicion = (int)$_POST['id_reposicion'];

        try {
            $rep = $this->repo->findReposicionById($idReposicion);
            if ($rep && $rep->estado === 'PENDIENTE') {
                $rep->estado = 'APROBADA'; // En un MVP esto lo aprueba el mismo usuario
                $this->repo->saveReposicion($rep);
                
                // NOTA P4: Aquí se podría generar la Solicitud de Pago manual si se integra.
                $this->audit('fondo_avance_reposicion', 'APROBAR', $idReposicion, null, null);
                
                header('Location: ?route=fondo_avance/reposiciones&id_fondo=' . $rep->idFondo . '&success=Rendición aprobada. Ya se puede procesar su Solicitud de Pago manual.');
                exit;
            }
            throw new Exception("Estado inválido para aprobar.");
        } catch (Exception $e) {
            header('Location: ?route=fondo_avance/gastos&id_reposicion=' . $idReposicion . '&error=' . urlencode($e->getMessage()));
            exit;
        }
    }
}
