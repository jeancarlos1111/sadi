<?php

namespace App\Controllers;

use App\Repositories\DocumentoRepository;
use Exception;
use PDOException;

class DocumentosPorPagarController extends HomeController
{
    private DocumentoRepository $repo;

    public function __construct(DocumentoRepository $repo)
    {
        $this->repo = $repo;
    }
    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $mes = $_GET['mes'] ?? '';

        try {
            $documentos = $this->repo->all($search, $mes);
        } catch (PDOException $e) {
            $documentos = [];
            $error = "Error al obtener documentos por pagar: " . $e->getMessage();
        }

        $this->renderView('cuentas_por_pagar/documentos/index', [
            'titulo' => 'Recepción de Documentos (CxP)',
            'documentos' => $documentos,
            'search' => $search,
            'mes' => $mes,
            'error' => $error ?? null,
        ]);
    }

    public function form(): void
    {
        try {
            $ordenesCompra = $this->repo->getOrdenesPendientesFacturar();
            $ordenesServicio = $this->repo->getOrdenesServicioPendientesFacturar();
        } catch (PDOException $e) {
            $ordenesCompra = [];
            $ordenesServicio = [];
            $error = "Error al obtener órdenes pendientes: " . $e->getMessage();
        }

        $this->renderView('cuentas_por_pagar/documentos/form', [
            'titulo' => 'Registro de Factura / Causación',
            'ordenesCompra' => $ordenesCompra,
            'ordenesServicio' => $ordenesServicio,
            'error' => $error ?? null,
        ]);
    }

    public function getDatosOrden(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $tipo = $_GET['tipo'] ?? 'compra'; // 'compra' o 'servicio'

        try {
            if ($tipo === 'compra') {
                $datos = $this->repo->getDatosNotaEntrega($id);
            } else {
                $datos = $this->repo->getDatosOrdenServicio($id);
            }

            header('Content-Type: application/json');
            echo json_encode($datos);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=documentos_por_pagar/index');
            exit;
        }

        try {
            $data = $_POST;
            // Asegurar que los campos numéricos sean correctos
            $data['monto_base_d'] = (float)($data['monto_base_d'] ?? 0);
            $data['monto_impuesto_d'] = (float)($data['monto_impuesto_d'] ?? 0);
            $data['porcentaje_retencion_iva'] = (float)($data['porcentaje_retencion_iva'] ?? 0);
            $data['porcentaje_retencion_islr'] = (float)($data['porcentaje_retencion_islr'] ?? 0);

            $this->repo->registrarFacturaYRetenciones($data);

            header('Location: ?route=documentos_por_pagar/index&success=Factura+registrada+correctamente');
            exit;
        } catch (Exception $e) {
            $this->renderView('cuentas_por_pagar/documentos/form', [
                'titulo' => 'Registro de Factura / Causación',
                'error' => "Error al guardar factura: " . $e->getMessage(),
                'ordenesCompra' => $this->repo->getOrdenesPendientesFacturar(),
                'ordenesServicio' => $this->repo->getOrdenesServicioPendientesFacturar(),
            ]);
        }
    }

    public function eliminar(): void
    {
        $id = $_POST['id'] ?? null;
        if ($id) {
            try {
                $this->repo->delete((int)$id);
            } catch (PDOException $e) {
                die("Error al eliminar: " . $e->getMessage());
            }
            header('Location: ?route=documentos_por_pagar/index');
            exit;
        }
    }
}
