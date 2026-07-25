<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AsignacionBien;
use App\Repositories\AsignacionBienRepository;
use Exception;

class AsignacionBienesController extends BaseController
{
    public function __construct(
        private readonly AsignacionBienRepository $asignacionRepo
    ) {
        $this->requirePermiso('inventario.asignacion.ver');
    }

    public function index(): void
    {
        // TODO: Listar asignaciones
        $this->renderView('inventario/asignaciones/index', [
            'titulo' => 'Asignaciones de Bienes',
            'asignaciones' => []
        ]);
    }

    public function form(): void
    {
        $this->renderView('inventario/asignaciones/form', [
            'titulo' => 'Nueva Asignación de Bien'
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Método no permitido');

        try {
            $asignacion = new AsignacionBien(
                0,
                $_POST['numero_asignacion'] ?? 'AS-' . time(),
                (int)$_POST['id_articulo'],
                $_POST['cedula_responsable'],
                (int)$_POST['id_unidad_administrativa'],
                $_POST['fecha_asignacion'] ?? date('Y-m-d')
            );

            $id = $this->asignacionRepo->save($asignacion);
            
            $this->audit('asignacion_bien', 'INSERT', $id, null, $asignacion->toArray());

            header('Location: ?route=asignacion_bienes/index&success=Bien asignado');
            exit;
        } catch (Exception $e) {
            header('Location: ?route=asignacion_bienes/form&error=' . urlencode($e->getMessage()));
            exit;
        }
    }
}
