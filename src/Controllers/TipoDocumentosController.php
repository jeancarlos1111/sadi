<?php

namespace App\Controllers;

use App\Models\TipoDocumento;
use App\Repositories\TipoDocumentoRepository;

class TipoDocumentosController extends HomeController
{
    private TipoDocumentoRepository $repo;

    public function __construct(TipoDocumentoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(): void
    {
        $items = $this->repo->all();
        $this->renderView('cxp/tipo_documentos/index', [
            'titulo' => 'Tipos de Documento (CxP)',
            'items'  => $items,
        ]);
    }

    public function form(): void
    {
        $id   = $_GET['id'] ?? null;
        $item = $id ? $this->repo->findById((int)$id) : null;

        $this->renderView('cxp/tipo_documentos/form', [
            'titulo' => $item ? 'Editar Tipo de Documento' : 'Nuevo Tipo de Documento',
            'item'   => $item,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=tipo_documentos/index');
            exit;
        }

        try {
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $item = new TipoDocumento(
                trim($_POST['denominacion_tipo_documento'] ?? ''),
                !empty($_POST['afecta_presupuesto_tipo_documento']),
                trim($_POST['siglas_tipo_documento'] ?? '') ?: null,
                $id
            );
            $this->repo->save($item);
            header('Location: ?route=tipo_documentos/index&success=Tipo+de+documento+guardado');
        } catch (\Exception $e) {
            die("Error al guardar: " . $e->getMessage());
        }
        exit;
    }

    public function eliminar(): void
    {
        $id = $_POST['id'] ?? null;
        if ($id) {
            try {
                $this->repo->delete((int)$id);
                header('Location: ?route=tipo_documentos/index&success=Tipo+de+documento+eliminado');
            } catch (\Exception $e) {
                die("Error al eliminar: " . $e->getMessage());
            }
        } else {
            header('Location: ?route=tipo_documentos/index');
        }
        exit;
    }
}
