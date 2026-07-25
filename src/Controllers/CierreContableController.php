<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CierreContableService;
use App\Auth\Auth;
use Exception;

class CierreContableController extends BaseController
{
    private CierreContableService $cierreService;

    public function __construct()
    {
        $this->cierreService = new CierreContableService();
    }

    public function index(): void
    {
        // Require permissions, etc.
        // if (!Auth::hasPermission('contabilidad.cierre')) {
        //    $this->redirect('/dashboard', 'No tienes permisos para esta acción', 'error');
        // }

        $anio = (string)date('Y'); // Default a año actual o permitir seleccionar de la URL (ej. ?anio=2026)
        if (isset($_GET['anio']) && is_numeric($_GET['anio'])) {
            $anio = (string)$_GET['anio'];
        }

        try {
            $resumen = $this->cierreService->obtenerResumenCierre($anio);
            $estaCerrado = $this->cierreService->estaCerrado($anio);

            $this->render('contabilidad/cierre', [
                'titulo' => "Cierre Contable - {$anio}",
                'resumen' => $resumen,
                'estaCerrado' => $estaCerrado,
                'anio' => $anio
            ]);
        } catch (Exception $e) {
            $this->redirect('?route=cierre_contable', 'Error al cargar el resumen: ' . $e->getMessage(), 'error');
        }
    }

    public function procesar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?route=cierre_contable', 'Método no permitido', 'error');
        }

        $anio = (string)($_POST['anio'] ?? date('Y'));
        $usuario = Auth::user();

        try {
            $this->cierreService->ejecutarCierre($anio, $usuario->id);
            $this->audit('cierre_ejercicio', 'CIERRE', 0, [], ['anio' => $anio, 'estado' => 'CERRADO']);

            $this->redirect('?route=cierre_contable&anio=' . $anio, "El ejercicio {$anio} ha sido cerrado exitosamente.", 'success');
        } catch (Exception $e) {
            $this->redirect('?route=cierre_contable&anio=' . $anio, 'Error al ejecutar cierre: ' . $e->getMessage(), 'error');
        }
    }

    public function reversar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?route=cierre_contable', 'Método no permitido', 'error');
        }

        $anio = (string)($_POST['anio'] ?? date('Y'));
        $usuario = Auth::user();

        try {
            $this->cierreService->reversarCierre($anio, $usuario->id);
            $this->audit('cierre_ejercicio', 'REVERSO', 0, ['estado' => 'CERRADO'], ['anio' => $anio, 'estado' => 'REVERSADO']);

            $this->redirect('?route=cierre_contable&anio=' . $anio, "El cierre del ejercicio {$anio} ha sido reversado (SOLO PRUEBAS).", 'success');
        } catch (Exception $e) {
            $this->redirect('?route=cierre_contable&anio=' . $anio, 'Error al reversar cierre: ' . $e->getMessage(), 'error');
        }
    }
}
