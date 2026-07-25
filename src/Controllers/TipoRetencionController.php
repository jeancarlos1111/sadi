<?php

declare(strict_types=1);

namespace App\Controllers;
use App\Models\TipoRetencion;
use App\Repositories\TipoRetencionRepository;
use Exception;

class TipoRetencionController extends BaseController
{
    public function __construct(
        private readonly TipoRetencionRepository $tipoRetencionRepo
    ) {
        // Permiso genérico para cuentas por pagar (retenciones)
        $this->requirePermiso('cxp.retenciones.ver');
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $tipos = $this->tipoRetencionRepo->all($search);

        $this->renderView('cuentas_por_pagar/tipo_retencion/index', [
            'titulo' => 'Configuración de Retenciones (SENIAT)',
            'tipos' => $tipos,
            'search' => $search
        ]);
    }

    public function form(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $tipo = $id > 0 ? $this->tipoRetencionRepo->findById($id) : null;
        
        $this->renderView('cuentas_por_pagar/tipo_retencion/form', [
            'titulo' => $tipo ? 'Editar Tipo de Retención' : 'Nuevo Tipo de Retención',
            'tipo' => $tipo
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die('Método no permitido');
        }

        try {
            $id = (int)($_POST['id_tipo_retencion'] ?? 0);
            
            $tipo = new TipoRetencion(
                $id,
                trim($_POST['codigo']),
                trim($_POST['denominacion']),
                (float)$_POST['porcentaje'],
                (float)($_POST['sustraendo'] ?? 0),
                $_POST['aplica_a'],
                isset($_POST['activo']) && $_POST['activo'] == '1'
            );

            // Audit
            $estadoAnterior = $id > 0 ? $this->tipoRetencionRepo->findById($id)?->toArray() : null;

            $newId = $this->tipoRetencionRepo->save($tipo);
            
            $this->audit(
                'tipo_retencion',
                $id > 0 ? 'UPDATE' : 'INSERT',
                $newId,
                $estadoAnterior,
                $tipo->toArray()
            );

            header('Location: ?route=tipo_retencion/index&success=Tipo de retención guardado exitosamente.');
            exit;
        } catch (Exception $e) {
            die("Error al guardar: " . $e->getMessage());
        }
    }

    public function toggle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die('Método no permitido');
        }

        $id = (int)($_POST['id'] ?? 0);
        $tipo = $this->tipoRetencionRepo->findById($id);

        if ($tipo) {
            $this->tipoRetencionRepo->toggleActivo($id);
            $this->audit('tipo_retencion', 'TOGGLE_ACTIVO', $id, ['activo' => $tipo->activo], ['activo' => !$tipo->activo]);
        }

        header('Location: ?route=tipo_retencion/index&success=Estado actualizado.');
        exit;
    }
}
