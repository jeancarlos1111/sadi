<?php

namespace App\Controllers;

use App\Repositories\OrdenServicioRepository;
use App\Repositories\ProveedorRepository;
use App\Repositories\ServicioRepository;
use PDOException;

class OrdenesServicioController extends HomeController
{
    private OrdenServicioRepository $repo;
    private ServicioRepository $servicioRepo;
    private ProveedorRepository $proveedorRepo;

    public function __construct(
        OrdenServicioRepository $repo,
        ServicioRepository $servicioRepo,
        ProveedorRepository $proveedorRepo
    ) {
        $this->repo = $repo;
        $this->servicioRepo = $servicioRepo;
        $this->proveedorRepo = $proveedorRepo;
    }
    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $mes    = $_GET['mes'] ?? '';

        try {
            $ordenes = $this->repo->all($search, $mes);
        } catch (PDOException | \Exception $e) {
            $ordenes = [];
            $error = "Error: " . $e->getMessage();
        }
        $this->renderView('compras/ordenes_servicio/index', [
            'titulo'  => 'Órdenes de Servicio',
            'ordenes' => $ordenes,
            'search'  => $search,
            'mes'     => $mes,
            'error'   => $error ?? null,
        ]);
    }

    public function form(): void
    {
        $id = $_GET['id'] ?? null;
        $orden = null;

        try {
            if ($id) {
                $orden = $this->repo->findById((int)$id);
            }
            $serviciosCatalogo = $this->servicioRepo->all();
            $proveedores       = $this->proveedorRepo->all();
        } catch (PDOException | \Exception $e) {
            $error = "Error DB: " . $e->getMessage();
            $serviciosCatalogo = [];
            $proveedores = [];
        }
        $this->renderView('compras/ordenes_servicio/form', [
            'titulo'            => $orden ? 'Editar Orden de Servicio' : 'Nueva Orden de Servicio',
            'orden'             => $orden,
            'serviciosCatalogo' => $serviciosCatalogo,
            'proveedores'       => $proveedores,
            'error'             => $error ?? null,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=ordenes_servicio/index');
            exit;
        }

        try {
            $cabecera = [
                'fecha'         => $_POST['fecha_os'] ?? date('Y-m-d'),
                'concepto'      => trim($_POST['concepto_os'] ?? ''),
                'id_proveedor'  => (int)($_POST['id_proveedor'] ?? 0),
                'porcentaje_iva' => (float)($_POST['porcentaje_iva_os'] ?? 16),
                'monto_base'    => (float)($_POST['monto_base_os'] ?? 0),
                'monto_iva'     => (float)($_POST['monto_iva_os'] ?? 0),
                'monto_total'   => (float)($_POST['monto_total_os'] ?? 0),
            ];
            $detalles = [];
            foreach ($_POST['id_servicio'] ?? [] as $idx => $idSer) {
                if ($idSer) {
                    $detalles[] = [
                        'id_servicio' => (int)$idSer,
                        'cantidad'    => (float)($_POST['cantidad_sods'][$idx] ?? 1),
                        'costo'       => (float)($_POST['costo_sods'][$idx] ?? 0),
                        'aplica_iva'  => !empty($_POST['aplica_iva'][$idx]),
                    ];
                }
            }
            if (empty($detalles)) {
                throw new \Exception("Debe agregar al menos un servicio a la Orden.");
            }
            $idOS = $this->repo->crear($cabecera, $detalles);
            header('Location: ?route=ordenes_servicio/index&success=Orden+' . $idOS . '+creada.');
            exit;
        } catch (\Exception $e) {
            die("Error al guardar: " . $e->getMessage());
        }
    }

    public function contabilizar(): void
    {
        $id = $_POST['id'] ?? null;
        if ($id) {
            try {
                $this->repo->contabilizar((int)$id);
                header('Location: ?route=ordenes_servicio/index&success=Orden+de+servicio+contabilizada+y+presupuesto+afectado.');
                exit;
            } catch (\Exception $e) {
                header('Location: ?route=ordenes_servicio/index&error=' . urlencode('Error al contabilizar: ' . $e->getMessage()));
                exit;
            }
        }
        header('Location: ?route=ordenes_servicio/index');
        exit;
    }

    public function reversar(): void
    {
        $id = $_POST['id'] ?? null;
        if ($id) {
            try {
                $this->repo->reversar((int)$id);
                header('Location: ?route=ordenes_servicio/index&success=Orden+de+servicio+reversada+y+presupuesto+liberado.');
                exit;
            } catch (\Exception $e) {
                header('Location: ?route=ordenes_servicio/index&error=' . urlencode('Error al reversar: ' . $e->getMessage()));
                exit;
            }
        }
        header('Location: ?route=ordenes_servicio/index');
        exit;
    }

    public function eliminar(): void
    {
        $id = $_POST['id'] ?? null;
        if ($id) {
            try {
                $this->repo->delete((int)$id);
            } catch (PDOException | \Exception $e) {
                die("Error: " . $e->getMessage());
            }
        }
        header('Location: ?route=ordenes_servicio/index');
        exit;
    }
}
