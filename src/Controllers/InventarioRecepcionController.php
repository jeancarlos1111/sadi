<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ActaRecepcion;
use App\Models\ActaRecepcionDetalle;
use App\Repositories\ActaRecepcionRepository;
use Exception;

class InventarioRecepcionController extends BaseController
{
    public function __construct(
        private readonly ActaRecepcionRepository $recepcionRepo
    ) {
        $this->requirePermiso('inventario.recepcion.ver');
    }

    public function index(): void
    {
        // TODO: Listar actas
        $this->renderView('inventario/actas_recepcion/index', [
            'titulo' => 'Actas de Recepción',
            'actas' => [] // Mock para la vista
        ]);
    }

    public function form(): void
    {
        $this->renderView('inventario/actas_recepcion/form', [
            'titulo' => 'Nueva Acta de Recepción'
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Método no permitido');

        try {
            $acta = new ActaRecepcion(
                0,
                $_POST['numero_acta'] ?? 'AR-' . time(),
                $_POST['fecha_recepcion'] ?? date('Y-m-d'),
                (int)$_POST['id_orden_de_compra'],
                (int)$_SESSION['usuario_id'],
                true,
                $_POST['observaciones'] ?? null
            );

            // Supongamos que recibimos los articulos via array
            $detalles = [];
            if (!empty($_POST['articulos']) && is_array($_POST['articulos'])) {
                foreach ($_POST['articulos'] as $art) {
                    $detalles[] = new ActaRecepcionDetalle(
                        0, 0, (int)$art['id_articulo'], (float)$art['cantidad_recibida'], 'NUEVO'
                    );
                }
            }

            $idActa = $this->recepcionRepo->saveConDetalles($acta, $detalles);
            
            $this->audit('acta_recepcion', 'INSERT', $idActa, null, $acta->toArray());

            header('Location: ?route=inventario_recepcion/index&success=Acta guardada');
            exit;
        } catch (Exception $e) {
            header('Location: ?route=inventario_recepcion/form&error=' . urlencode($e->getMessage()));
            exit;
        }
    }
}
