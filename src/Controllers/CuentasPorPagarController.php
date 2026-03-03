<?php

namespace App\Controllers;

use App\Repositories\DocumentoRepository;
use Exception;

class CuentasPorPagarController extends HomeController
{
    private DocumentoRepository $repo;

    public function __construct(DocumentoRepository $repo)
    {
        $this->repo = $repo;
    }

    public function recepcionFacturas(): void
    {
        $idOrdenOc = $_GET['id_orden'] ?? null;
        $idOrdenOs = $_GET['id_orden_servicio'] ?? null;

        try {
            // Órdenes recibidas en almacén pero sin facturar
            $ordenesPendientes = $this->repo->getOrdenesPendientesFacturar();
            // Órdenes de servicio contabilizadas pero sin facturar
            $ordenesServicioPendientes = $this->repo->getOrdenesServicioPendientesFacturar();
        } catch (Exception $e) {
            $ordenesPendientes = [];
            $ordenesServicioPendientes = [];
            $error = "Error cargando órdenes: " . $e->getMessage();
        }

        $notaEntrega = null;
        if ($idOrdenOc) {
            try {
                $notaEntrega = $this->repo->getDatosNotaEntrega((int)$idOrdenOc);
                if (!$notaEntrega) {
                    $error = "No se encontró la Nota de Entrega validada por Almacén para esta Orden, o ya fue facturada.";
                }
            } catch (Exception $e) {
                $error = "Error al obtener factura precargada (OC): " . $e->getMessage();
            }
        } elseif ($idOrdenOs) {
            try {
                $notaEntrega = $this->repo->getDatosOrdenServicio((int)$idOrdenOs);
                if (!$notaEntrega) {
                    $error = "No se encontró la Orden de Servicio, no está contabilizada, o ya fue facturada.";
                }
            } catch (Exception $e) {
                $error = "Error al obtener factura precargada (OS): " . $e->getMessage();
            }
        }

        $this->renderView('cxp/recepcion_factura', [
            'titulo' => 'Recepción de Facturas y Retenciones (CxP)',
            'ordenesPendientes' => $ordenesPendientes,
            'ordenesServicioPendientes' => $ordenesServicioPendientes,
            'notaEntrega' => $notaEntrega,
            'error' => $error ?? $_GET['error'] ?? null,
            'success' => $_GET['success'] ?? null,
        ]);
    }

    public function guardarFactura(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idOc = !empty($_POST['id_orden_de_compra']) ? (int)$_POST['id_orden_de_compra'] : null;
            $idOs = !empty($_POST['id_orden_de_servicio']) ? (int)$_POST['id_orden_de_servicio'] : null;

            // Validaciones básicas
            if (empty($_POST['nro_documento_d']) || empty($_POST['nro_control_d'])) {
                $qs = $idOc ? "id_orden={$idOc}" : "id_orden_servicio={$idOs}";
                header('Location: ?route=cuentas_por_pagar/recepcion_facturas&' . $qs . '&error=El número de factura y control son obligatorios.');
                exit;
            }

            try {
                $this->repo->registrarFacturaYRetenciones($_POST);
                header('Location: ?route=cuentas_por_pagar/recepcion_facturas&success=Factura y Comprobantes de Retención generados correctamente. Solicitud de Pago enviada a Tesorería.');
                exit;
            } catch (Exception $e) {
                $qs = $idOc ? "id_orden={$idOc}" : "id_orden_servicio={$idOs}";
                header('Location: ?route=cuentas_por_pagar/recepcion_facturas&' . $qs . '&error=' . urlencode($e->getMessage()));
                exit;
            }
        }
    }
}
