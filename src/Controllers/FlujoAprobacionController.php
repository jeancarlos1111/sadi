<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FlujoAprobacionService;
use App\Auth\Auth;
use Exception;

class FlujoAprobacionController extends BaseController
{
    private FlujoAprobacionService $flujoService;

    public function __construct()
    {
        $this->flujoService = new FlujoAprobacionService();
    }

    /**
     * Endpoint AJAX para cambiar el estado de un documento
     * POST: tipo_documento, id_documento, nuevo_estado, comentarios
     */
    public function cambiarEstado(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $tipoDocumento = $_POST['tipo_documento'] ?? '';
        $idDocumento = (int)($_POST['id_documento'] ?? 0);
        $nuevoEstado = $_POST['nuevo_estado'] ?? '';
        $comentarios = $_POST['comentarios'] ?? '';
        $usuario = Auth::user();

        if (!$usuario) {
            $this->jsonResponse(['success' => false, 'message' => 'No autorizado'], 401);
            return;
        }

        if (empty($tipoDocumento) || $idDocumento <= 0 || empty($nuevoEstado)) {
            $this->jsonResponse(['success' => false, 'message' => 'Faltan parámetros requeridos.'], 400);
            return;
        }

        try {
            $this->flujoService->cambiarEstado(
                $tipoDocumento,
                $idDocumento,
                $nuevoEstado,
                $comentarios,
                $usuario->id
            );

            // Emitir evento de auditoría en BaseController
            $this->audit(
                'historial_aprobacion', 
                'CAMBIO_ESTADO', 
                $idDocumento, 
                ['tipo' => $tipoDocumento], 
                ['nuevo_estado' => $nuevoEstado]
            );

            $this->jsonResponse([
                'success' => true,
                'message' => 'Estado actualizado exitosamente a ' . $nuevoEstado,
                'nuevo_estado' => $nuevoEstado
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Endpoint AJAX para obtener el historial
     * GET: tipo_documento, id_documento
     */
    public function historial(): void
    {
        $tipoDocumento = $_GET['tipo_documento'] ?? '';
        $idDocumento = (int)($_GET['id_documento'] ?? 0);

        if (empty($tipoDocumento) || $idDocumento <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Faltan parámetros requeridos.'], 400);
            return;
        }

        try {
            $historial = $this->flujoService->getHistorial($tipoDocumento, $idDocumento);
            $this->jsonResponse([
                'success' => true,
                'data' => $historial
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
