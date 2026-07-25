<?php

declare(strict_types=1);

namespace App\Controllers;
use App\Models\ProcesoContratacion;
use App\Models\OfertaProveedor;
use App\Repositories\ProcesoContratacionRepository;
use App\Repositories\OfertaProveedorRepository;
use App\Repositories\ProveedorRepository;
use App\Repositories\OrdenCompraRepository;
use Exception;

class ProcesoContratacionController extends BaseController
{
    public function __construct(
        private readonly ProcesoContratacionRepository $procesoRepo,
        private readonly OfertaProveedorRepository $ofertaRepo,
        private readonly ProveedorRepository $proveedorRepo,
        private readonly OrdenCompraRepository $ordenRepo
    ) {
        // Verificar permisos - asumiendo que 'compras.proceso_contratacion' será el permiso. 
        $this->requirePermiso('compras.proceso_contratacion.ver');
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        if ($page < 1) $page = 1;
        $perPage = 10;
        
        $totalRecords = $this->procesoRepo->countAll($search);
        $totalPages = max(1, (int)ceil($totalRecords / $perPage));
        
        $procesos = $this->procesoRepo->all($search, $page, $perPage);
        
        // Comprobar si es una petición AJAX (Server-Driven UI)
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            $this->renderView('compras/proceso_contratacion/partials/table', [
                'procesos' => $procesos,
                'page' => $page,
                'totalPages' => $totalPages,
                'search' => $search
            ], true);
            return;
        }

        $this->renderView('compras/proceso_contratacion/index', [
            'titulo' => 'Procesos de Contratación (LCP)',
            'procesos' => $procesos,
            'page' => $page,
            'totalPages' => $totalPages,
            'search' => $search
        ]);
    }

    public function form(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $proceso = $id > 0 ? $this->procesoRepo->findById($id) : null;
        
        $this->renderView('compras/proceso_contratacion/form', [
            'titulo' => $proceso ? 'Editar Proceso de Contratación' : 'Nuevo Proceso de Contratación',
            'proceso' => $proceso,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die('Método no permitido');
        }

        try {
            $id = (int)($_POST['id_proceso'] ?? 0);
            
            // Validaciones de negocio LCP
            $modalidad = $_POST['modalidad'] ?? '';
            $justificacion = $_POST['justificacion_legal'] ?? null;
            if ($modalidad === ProcesoContratacion::CONTRATACION_DIRECTA && empty($justificacion)) {
                throw new Exception("La Contratación Directa requiere obligatoriamente una justificación legal.");
            }

            $proceso = new ProcesoContratacion(
                $id,
                $_POST['numero_expediente'],
                $_POST['descripcion'],
                $modalidad,
                (float)$_POST['monto_estimado'],
                null, // idOrdenCompra - Se vincula al adjudicar
                $justificacion,
                isset($_POST['crs_aplicable']) && $_POST['crs_aplicable'] == '1',
                $_POST['numero_crs'] ?? null,
                'ABIERTO', // estatus
                $_POST['fecha_apertura'] ?? date('Y-m-d'),
                $_POST['fecha_cierre'] ?? null,
                (int)$_SESSION['usuario_id']
            );

            // Audit
            $estadoAnterior = $id > 0 ? $this->procesoRepo->findById($id)?->toArray() : null;

            $newId = $this->procesoRepo->save($proceso);
            
            $this->audit(
                'proceso_contratacion',
                $id > 0 ? 'UPDATE' : 'INSERT',
                $newId,
                $estadoAnterior,
                $proceso->toArray()
            );

            header('Location: ?route=proceso_contratacion/index&success=Proceso guardado exitosamente.');
            exit;
        } catch (Exception $e) {
            die("Error al guardar: " . $e->getMessage());
        }
    }

    public function ver(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $proceso = $this->procesoRepo->findById($id);
        
        if (!$proceso) {
            die('Proceso no encontrado.');
        }

        $ofertas = $this->ofertaRepo->getByProceso($id);
        $proveedores = $this->proveedorRepo->allActivos();

        $this->renderView('compras/proceso_contratacion/ver', [
            'titulo' => 'Expediente de Contratación: ' . $proceso->numeroExpediente,
            'proceso' => $proceso,
            'ofertas' => $ofertas,
            'proveedores' => $proveedores
        ]);
    }

    public function agregarOferta(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die('Método no permitido');
        }

        try {
            $idProceso = (int)($_POST['id_proceso'] ?? 0);
            
            $oferta = new OfertaProveedor(
                0,
                $idProceso,
                (int)$_POST['id_proveedor'],
                $_POST['fecha_presentacion'] ?? date('Y-m-d'),
                (float)$_POST['monto_ofertado'],
                $_POST['descripcion_oferta'] ?? null,
                isset($_POST['cumple_tecnicamente']) && $_POST['cumple_tecnicamente'] == '1',
                false, // esGanador
                $_POST['observaciones'] ?? null
            );

            $idOferta = $this->ofertaRepo->save($oferta);

            $this->audit(
                'oferta_proveedor',
                'INSERT',
                $idOferta,
                null,
                $oferta->toArray()
            );

            // Si el proceso estaba en ABIERTO, pasarlo a EVALUACION
            $proceso = $this->procesoRepo->findById($idProceso);
            if ($proceso && $proceso->estatus === 'ABIERTO') {
                $procesoActualizado = new ProcesoContratacion(
                    $proceso->id,
                    $proceso->numeroExpediente,
                    $proceso->descripcion,
                    $proceso->modalidad,
                    $proceso->montoEstimado,
                    $proceso->idOrdenCompra,
                    $proceso->justificacionLegal,
                    $proceso->crsAplicable,
                    $proceso->numeroCrs,
                    'EVALUACION', // Nuevo estatus
                    $proceso->fechaApertura,
                    $proceso->fechaCierre,
                    $proceso->idUsuarioCreador,
                    $proceso->eliminado
                );
                $this->procesoRepo->save($procesoActualizado);
            }

            header("Location: ?route=proceso_contratacion/ver&id=$idProceso&success=Oferta registrada exitosamente.");
            exit;
        } catch (Exception $e) {
            die("Error al guardar oferta: " . $e->getMessage());
        }
    }

    public function adjudicar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die('Método no permitido');
        }

        try {
            $idProceso = (int)($_POST['id_proceso'] ?? 0);
            $idOferta = (int)($_POST['id_oferta'] ?? 0);

            $proceso = $this->procesoRepo->findById($idProceso);
            $oferta = $this->ofertaRepo->findById($idOferta);

            if (!$proceso || !$oferta) {
                throw new Exception("Proceso u Oferta no encontrados.");
            }

            // Validar que el proveedor tenga RNC vigente (Regla LCP)
            // Se hace obteniendo el proveedor
            $proveedor = $this->proveedorRepo->find($oferta->idProveedor);
            if (!$proveedor) {
                throw new Exception("Proveedor no encontrado.");
            }

            if (empty($proveedor['fecha_vencimiento_rnc']) || strtotime($proveedor['fecha_vencimiento_rnc']) < time()) {
                throw new Exception("El proveedor no tiene un RNC vigente. No puede ser adjudicado según la LCP.");
            }

            // Adjudicar
            $this->procesoRepo->adjudicar($idProceso, $idOferta);
            
            $this->audit('proceso_contratacion', 'ADJUDICAR', $idProceso, ['estatus' => $proceso->estatus], ['estatus' => 'ADJUDICADO', 'oferta_ganadora' => $idOferta]);

            header("Location: ?route=proceso_contratacion/ver&id=$idProceso&success=Proceso adjudicado exitosamente.");
            exit;
        } catch (Exception $e) {
            $id = (int)($_POST['id_proceso'] ?? 0);
            header("Location: ?route=proceso_contratacion/ver&id=$id&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    public function eliminar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die('Método no permitido');
        }

        $id = (int)($_POST['id'] ?? 0);
        $proceso = $this->procesoRepo->findById($id);

        if ($proceso) {
            $this->procesoRepo->delete($id);
            $this->audit('proceso_contratacion', 'DELETE', $id, $proceso->toArray(), ['eliminado' => true]);
        }

        header('Location: ?route=proceso_contratacion/index&success=Proceso eliminado.');
        exit;
    }
}
