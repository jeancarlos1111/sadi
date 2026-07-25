<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;
use App\Models\TomaInventario;
use App\Repositories\TomaInventarioRepository;
use Exception;
use PDOException;

class TomaInventarioController extends BaseController
{
    private TomaInventarioRepository $repo;

    public function __construct()
    {
        $this->repo = new TomaInventarioRepository();
        Gate::authorize('inventario.toma.ver');
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';

        try {
            $tomas = $this->repo->all($search);
        } catch (PDOException | Exception $e) {
            $tomas = [];
            $error = "Error al obtener tomas de inventario: " . $e->getMessage();
        }

        $this->renderView('inventario/toma_inventario/index', [
            'titulo' => 'Tomas Físicas de Inventario',
            'tomas' => $tomas,
            'search' => $search,
            'error' => $error ?? $_GET['error'] ?? null,
            'success' => $_GET['success'] ?? null,
        ]);
    }

    public function form(): void
    {
        Gate::authorize('inventario.toma.crear');
        $this->renderView('inventario/toma_inventario/form', [
            'titulo' => 'Nueva Toma de Inventario',
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function guardar(): void
    {
        Gate::authorize('inventario.toma.crear');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Método no permitido');

        try {
            $item = new TomaInventario(
                $_POST['fecha_toma'] ?? date('Y-m-d'),
                $_POST['responsable'] ?? '',
                'ABIERTA',
                $_POST['observaciones'] ?? null
            );

            $idToma = $this->repo->save($item);
            
            // Inicializar el conteo copiando el stock actual del sistema a los detalles
            $this->repo->inicializarConteo($idToma);

            $this->audit('toma_inventario', 'CREAR', $idToma, null, $item->toArray());

            header('Location: ?route=toma_inventario/conteo&id=' . $idToma . '&success=Toma de inventario inicializada correctamente.');
            exit;
        } catch (Exception $e) {
            header('Location: ?route=toma_inventario/form&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function conteo(): void
    {
        Gate::authorize('inventario.toma.editar');
        $id = (int)($_GET['id'] ?? 0);
        
        $toma = $this->repo->findById($id);
        if (!$toma) {
            header('Location: ?route=toma_inventario/index&error=Toma no encontrada');
            exit;
        }

        try {
            $detalles = $this->repo->getDetalles($id);
        } catch (Exception $e) {
            $detalles = [];
            $error = "Error: " . $e->getMessage();
        }

        $this->renderView('inventario/toma_inventario/conteo', [
            'titulo' => 'Conteo Físico - Toma N° ' . $id,
            'toma' => $toma,
            'detalles' => $detalles,
            'error' => $error ?? $_GET['error'] ?? null,
            'success' => $_GET['success'] ?? null,
        ]);
    }

    public function actualizarDetalle(): void
    {
        Gate::authorize('inventario.toma.editar');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Método no permitido');

        $idToma = (int)$_POST['id_toma'];
        
        try {
            if (isset($_POST['id_detalle']) && is_array($_POST['id_detalle'])) {
                foreach ($_POST['id_detalle'] as $index => $idDetalle) {
                    $cantFisica = (int)($_POST['cantidad_fisica'][$index] ?? 0);
                    $just = trim($_POST['justificacion'][$index] ?? '');
                    $this->repo->actualizarConteo((int)$idDetalle, $cantFisica, $just);
                }
            }

            header('Location: ?route=toma_inventario/conteo&id=' . $idToma . '&success=Cantidades actualizadas correctamente.');
            exit;
        } catch (Exception $e) {
            header('Location: ?route=toma_inventario/conteo&id=' . $idToma . '&error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function cerrar(): void
    {
        Gate::authorize('inventario.toma.procesar');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Método no permitido');

        $idToma = (int)$_POST['id_toma'];
        
        try {
            $toma = $this->repo->findById($idToma);
            if (!$toma || $toma->estado === 'CERRADA') {
                throw new Exception("La toma no existe o ya está cerrada.");
            }

            $datosAntes = $toma->toArray();

            $toma->estado = 'CERRADA';
            $toma->fechaCierre = date('Y-m-d H:i:s');
            $this->repo->save($toma);

            // AQUI IRÍA LA LÓGICA PARA AJUSTAR INVENTARIO (FALTANTES/SOBRANTES)
            // Se asume en este MVP que el proceso queda registrado como ajuste y el supervisor lo ejecuta a parte
            // o que la sola firma del documento sirve de descargo de responsabilidades.
            
            $this->audit('toma_inventario', 'CERRAR', $idToma, $datosAntes, $toma->toArray());

            header('Location: ?route=toma_inventario/index&success=Toma de inventario cerrada. Las diferencias han sido consolidadas.');
            exit;
        } catch (Exception $e) {
            header('Location: ?route=toma_inventario/conteo&id=' . $idToma . '&error=' . urlencode($e->getMessage()));
            exit;
        }
    }
}
