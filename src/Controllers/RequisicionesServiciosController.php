<?php

namespace App\Controllers;

use App\Models\RequisicionServicios;
use App\Repositories\EstrucPresupuestariaRepository;
use App\Repositories\RequisicionServiciosRepository;
use App\Repositories\ServicioRepository;
use PDOException;

class RequisicionesServiciosController extends HomeController
{
    private RequisicionServiciosRepository $repo;
    private ServicioRepository $servicioRepo;
    private EstrucPresupuestariaRepository $estructurasRepo;

    public function __construct(
        RequisicionServiciosRepository $repo,
        ServicioRepository $servicioRepo,
        EstrucPresupuestariaRepository $estructurasRepo
    ) {
        $this->repo = $repo;
        $this->servicioRepo = $servicioRepo;
        $this->estructurasRepo = $estructurasRepo;
    }
    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $mes    = $_GET['mes'] ?? '';

        try {
            $requisiciones = $this->repo->all($search, $mes);
        } catch (PDOException | \Exception $e) {
            $requisiciones = [];
            $error = "Error: " . $e->getMessage();
        }
        $this->renderView('compras/requisiciones_servicios/index', [
            'titulo'        => 'Requisiciones de Servicios',
            'requisiciones' => $requisiciones,
            'search'        => $search,
            'mes'           => $mes,
            'error'         => $error ?? null,
        ]);
    }

    public function form(): void
    {
        $id          = $_GET['id'] ?? null;
        $requisicion = null;

        try {
            if ($id) {
                $requisicion = $this->repo->findById((int)$id);
            }
            $serviciosCatalogo = $this->servicioRepo->all();
            $estructuras       = $this->estructurasRepo->all();
        } catch (PDOException | \Exception $e) {
            $error = "Error DB: " . $e->getMessage();
            $serviciosCatalogo = [];
            $estructuras = [];
        }
        $this->renderView('compras/requisiciones_servicios/form', [
            'titulo'            => $requisicion ? 'Editar Requisición de Servicios' : 'Nueva Requisición de Servicios',
            'requisicion'       => $requisicion,
            'serviciosCatalogo' => $serviciosCatalogo,
            'estructuras'       => $estructuras,
            'error'             => $error ?? null,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=requisiciones_servicios/index');
            exit;
        }

        try {
            $id           = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $serviciosInput = $_POST['servicios'] ?? [];
            $cantidadesInput = $_POST['cantidades'] ?? [];

            $servicios = [];
            foreach ($serviciosInput as $idx => $idSer) {
                if ($idSer && !empty($cantidadesInput[$idx])) {
                    $servicios[] = ['id_servicio' => (int)$idSer, 'cantidad' => (float)$cantidadesInput[$idx]];
                }
            }

            $rs = new RequisicionServicios(
                trim($_POST['fecha'] ?? date('Y-m-d')),
                trim($_POST['concepto'] ?? ''),
                (int)($_POST['id_estructura_presupuestaria'] ?? 0),
                $servicios,
                $id
            );
            $this->repo->save($rs);
            header('Location: ?route=requisiciones_servicios/index');
            exit;
        } catch (\Exception $e) {
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
                die("Error: " . $e->getMessage());
            }
        }
        header('Location: ?route=requisiciones_servicios/index');
        exit;
    }
}
