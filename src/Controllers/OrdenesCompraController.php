<?php

namespace App\Controllers;

use App\Repositories\ArticuloRepository;
use App\Repositories\OrdenCompraRepository;
use App\Repositories\ProveedorRepository;
use PDOException;

class OrdenesCompraController extends BaseController
{
    private OrdenCompraRepository $repo;
    private ProveedorRepository $proveedorRepo;
    private ArticuloRepository $articuloRepo;

    public function __construct(
        OrdenCompraRepository $repo,
        ProveedorRepository $proveedorRepo,
        ArticuloRepository $articuloRepo
    ) {
        $this->repo = $repo;
        $this->proveedorRepo = $proveedorRepo;
        $this->articuloRepo = $articuloRepo;
    }
    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $mes = $_GET['mes'] ?? '';

        try {
            $ordenes = $this->repo->all($search, $mes);
        } catch (PDOException | \Exception $e) {
            $ordenes = [];
            $error = "Error al obtener las órdenes de compra: " . $e->getMessage();
        }

        $this->renderView('compras/ordenes_compra/index', [
            'titulo' => 'Listado de Órdenes de Compra',
            'ordenes' => $ordenes,
            'search' => $search,
            'mes' => $mes,
            'error' => $error ?? null,
        ]);
    }

    public function form(): void
    {
        $proveedores = $this->proveedorRepo->all();
        $articulos = $this->articuloRepo->all();

        $this->renderView('compras/ordenes_compra/form', [
            'titulo' => 'Emitir Nueva Orden de Compra',
            'proveedores' => $proveedores,
            'articulos' => $articulos,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $cabecera = [
                    'fecha' => $_POST['fecha_odc'] ?? date('Y-m-d'),
                    'concepto' => $_POST['concepto_odc'] ?? '',
                    'id_proveedor' => (int)($_POST['id_proveedor'] ?? 0),
                    'porcentaje_iva_odc' => (float)($_POST['porcentaje_iva_odc'] ?? 16),
                    'monto_base_odc' => (float)($_POST['monto_base_odc'] ?? 0),
                    'monto_iva_odc' => (float)($_POST['monto_iva_odc'] ?? 0),
                    'monto_total_odc' => (float)($_POST['monto_total_odc'] ?? 0),
                ];

                $detalles = [];
                if (isset($_POST['id_articulo']) && is_array($_POST['id_articulo'])) {
                    foreach ($_POST['id_articulo'] as $index => $idArticulo) {
                        $detalles[] = [
                            'id_articulo' => (int)$idArticulo,
                            'cantidad_aodc' => (float)($_POST['cantidad_aodc'][$index] ?? 1),
                            'costo_aodc' => (float)($_POST['costo_aodc'][$index] ?? 0),
                            'aplica_iva' => isset($_POST['aplica_iva'][$index]) && $_POST['aplica_iva'][$index] === '1',
                        ];
                    }
                }

                if (empty($detalles)) {
                    throw new \Exception("Debe agregar al menos un artículo a la Orden de Compra.");
                }

                $idOrden = $this->repo->crearConTransaccion($cabecera, $detalles);
                header('Location: ?route=ordenes_compra/index&success=Orden ' . $idOrden . ' creada y Presupuesto comprometido.');
                exit;

            } catch (\Exception $e) {
                $proveedores = $this->proveedorRepo->all();
                $articulos = $this->articuloRepo->all();
                $this->renderView('compras/ordenes_compra/form', [
                    'titulo' => 'Emitir Nueva Orden de Compra',
                    'proveedores' => $proveedores,
                    'articulos' => $articulos,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function contabilizar(): void
    {
        $id = $_POST['id'] ?? null;
        if ($id) {
            try {
                $this->repo->contabilizar((int)$id);
                header('Location: ?route=ordenes_compra/index&success=Orden+contabilizada+correctamente+y+presupuesto+afectado.');
                exit;
            } catch (\Exception $e) {
                header('Location: ?route=ordenes_compra/index&error=' . urlencode('Error al contabilizar: ' . $e->getMessage()));
                exit;
            }
        }
        header('Location: ?route=ordenes_compra/index');
        exit;
    }

    public function reversar(): void
    {
        $id = $_POST['id'] ?? null;
        if ($id) {
            try {
                $this->repo->reversar((int)$id);
                header('Location: ?route=ordenes_compra/index&success=Orden+reversada+correctamente+y+presupuesto+liberado.');
                exit;
            } catch (\Exception $e) {
                header('Location: ?route=ordenes_compra/index&error=' . urlencode('Error al reversar: ' . $e->getMessage()));
                exit;
            }
        }
        header('Location: ?route=ordenes_compra/index');
        exit;
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
        header('Location: ?route=ordenes_compra/index');
        exit;
    }
}
