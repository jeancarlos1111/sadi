<?php

namespace App\Controllers;

use App\Repositories\InventarioInsumoRepository;
use App\Repositories\OrdenCompraRepository;
use App\Repositories\RecepcionAlmacenRepository;
use Exception;
use PDOException;

class InventarioController extends HomeController
{
    private InventarioInsumoRepository $insumoRepo;
    private OrdenCompraRepository $ordenRepo;
    private RecepcionAlmacenRepository $recepcionRepo;

    public function __construct(
        InventarioInsumoRepository $insumoRepo,
        OrdenCompraRepository $ordenRepo,
        RecepcionAlmacenRepository $recepcionRepo
    ) {
        $this->insumoRepo = $insumoRepo;
        $this->ordenRepo = $ordenRepo;
        $this->recepcionRepo = $recepcionRepo;

        if (!isset($_SESSION['usuario'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';

        try {
            $insumos = $this->insumoRepo->all($search);
        } catch (PDOException | \Exception $e) {
            $insumos = [];
            $error = "Error al obtener el inventario: " . $e->getMessage();
        }

        $this->renderView('inventario/existencias/index', [
            'titulo' => 'Control de Inventario (Almacén)',
            'insumos' => $insumos,
            'search' => $search,
            'error' => $error ?? null,
        ]);
    }

    public function entradas(): void
    {
        $idOrden = $_GET['id_orden'] ?? null;
        $ordenes = $this->ordenRepo->all(); // Obtener todas para selector
        $detalles = [];
        $ordenSeleccionada = null;

        if ($idOrden) {
            try {
                // Filtrar del array la orden seleccionada para mostrar cabecera
                foreach ($ordenes as $o) {
                    if ($o['entity']->id == $idOrden) {
                        $ordenSeleccionada = $o;

                        break;
                    }
                }

                // Traer artículos de la orden agrupando lo ya recibido
                $detalles = $this->recepcionRepo->getPendientesPorOrden((int)$idOrden);
            } catch (Exception $e) {
                $error = "Error al obtener la orden: " . $e->getMessage();
            }
        }

        $this->renderView('inventario/movimientos/entradas', [
            'titulo' => 'Recepción de Almacén (Compras)',
            'ordenes' => $ordenes,
            'ordenSeleccionada' => $ordenSeleccionada,
            'detalles' => $detalles,
            'error' => $error ?? null,
            'success' => $_GET['success'] ?? null,
        ]);
    }

    public function procesarRecepcion(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idOrden = (int)($_POST['id_orden'] ?? 0);

            $articulosRecibidos = [];
            if (isset($_POST['id_articulo']) && is_array($_POST['id_articulo'])) {
                foreach ($_POST['id_articulo'] as $index => $idArt) {
                    $cant = (float)($_POST['cantidad_recibir'][$index] ?? 0);
                    if ($cant > 0) {
                        $articulosRecibidos[] = [
                            'id_articulo' => $idArt,
                            'cantidad' => $cant,
                        ];
                    }
                }
            }

            if (empty($articulosRecibidos)) {
                header('Location: ?route=inventario/entradas&id_orden=' . $idOrden . '&error=Debe especificar al menos una cantidad a recibir superior a cero.');
                exit;
            }

            try {
                $this->recepcionRepo->recibirArticulos($idOrden, $articulosRecibidos);
                header('Location: ?route=inventario/entradas&success=Recepción procesada correctamente. Se han actualizado las existencias, generado el documento Causado en CxP e incrementado el Presupuesto Causado.');
                exit;
            } catch (Exception $e) {
                header('Location: ?route=inventario/entradas&id_orden=' . $idOrden . '&error=' . urlencode($e->getMessage()));
                exit;
            }
        }
    }

    public function despachos(): void
    {
        $this->renderView('inventario/movimientos/despachos', [
            'titulo' => 'En construcción - Salidas y Despachos',
            'error' => 'El módulo de despachos procesa la entrega de insumos a los departamentos solicitantes. En desarrollo.',
        ]);
    }
}
