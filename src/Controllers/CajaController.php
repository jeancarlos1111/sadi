<?php

namespace App\Controllers;

use App\Models\CajaChica;
use App\Repositories\CajaChicaRepository;
use Exception;

class CajaController extends BaseController
{
    private CajaChicaRepository $repo;

    public function __construct(CajaChicaRepository $repo)
    {
        $this->repo = $repo;

        if (!isset($_SESSION['usuario'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    /** Listado de cajas chicas */
    public function index(): void
    {
        $cajas = $this->repo->all();
        $this->renderView('caja/index', [
            'titulo' => 'Módulo de Caja Chica',
            'cajas'  => $cajas,
        ]);
    }

    /** Formulario crear/editar caja */
    public function form(): void
    {
        $id   = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $caja = $id ? $this->repo->find($id) : null;
        $this->renderView('caja/form', [
            'titulo' => $caja ? 'Editar Caja Chica' : 'Nueva Caja Chica',
            'caja'   => $caja,
            'error'  => null,
        ]);
    }

    /** POST: guardar caja chica */
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=caja/index');
            exit;
        }
        $id           = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $denominacion = trim($_POST['denominacion'] ?? '');
        $responsable  = trim($_POST['responsable'] ?? '');
        $monto        = (float)str_replace(',', '.', $_POST['monto_asignado'] ?? '0');
        $fecha        = trim($_POST['fecha_apertura'] ?? date('Y-m-d'));

        if ($denominacion === '' || $responsable === '' || $monto <= 0) {
            $this->renderView('caja/form', ['titulo' => 'Caja Chica', 'caja' => null, 'error' => 'Todos los campos son obligatorios y el monto debe ser mayor a 0.']);

            return;
        }

        try {
            if ($id) {
                $caja = $this->repo->find($id);
                if ($caja) {
                    $caja->denominacion  = $denominacion;
                    $caja->responsable   = $responsable;
                    $caja->montoAsignado = $monto;
                    $caja->fechaApertura = $fecha;
                    $this->repo->save($caja);
                }
            } else {
                $caja = new CajaChica($denominacion, $responsable, $monto, $monto, $fecha);
                $this->repo->save($caja);
            }
            header('Location: ?route=caja/index');
            exit;
        } catch (Exception $e) {
            $this->renderView('caja/form', ['titulo' => 'Caja Chica', 'caja' => null, 'error' => 'Error: ' . $e->getMessage()]);
        }
    }

    /** Ver detalle y movimientos de una caja */
    public function detalle(): void
    {
        $id   = (int)($_GET['id'] ?? 0);
        $caja = $this->repo->find($id);
        if (!$caja) {
            header('Location: ?route=caja/index');
            exit;
        }
        $movimientos = $this->repo->getMovimientos($id);
        $this->renderView('caja/detalle', [
            'titulo'      => 'Detalle: ' . $caja->denominacion,
            'caja'        => $caja,
            'movimientos' => $movimientos,
            'success'     => $_GET['ok'] ?? null,
            'error'       => $_GET['error'] ?? null,
        ]);
    }

    /** POST: registrar gasto */
    public function registrarGasto(): void
    {
        $id        = (int)($_POST['id_caja_chica'] ?? 0);
        $concepto  = trim($_POST['concepto'] ?? '');
        $monto     = (float)str_replace(',', '.', $_POST['monto'] ?? '0');
        $fecha     = trim($_POST['fecha'] ?? date('Y-m-d'));
        $comprob   = trim($_POST['comprobante'] ?? '');

        if (!$id || $concepto === '' || $monto <= 0) {
            header("Location: ?route=caja/detalle&id=$id&error=" . urlencode('Concepto y monto son obligatorios.'));
            exit;
        }

        // Verificar saldo suficiente
        $caja = $this->repo->find($id);
        if (!$caja || $caja->montoDisponible < $monto) {
            header("Location: ?route=caja/detalle&id=$id&error=" . urlencode('Saldo insuficiente en la caja chica.'));
            exit;
        }

        try {
            $this->repo->registrarMovimiento($id, 'GASTO', $concepto, $monto, $fecha, $comprob);
            header("Location: ?route=caja/detalle&id=$id&ok=" . urlencode("Gasto de Bs " . number_format($monto, 2, ',', '.') . " registrado."));
            exit;
        } catch (Exception $e) {
            header("Location: ?route=caja/detalle&id=$id&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /** POST: reponer caja chica */
    public function reponer(): void
    {
        $id      = (int)($_POST['id_caja_chica'] ?? 0);
        $monto   = (float)str_replace(',', '.', $_POST['monto_reposicion'] ?? '0');
        $fecha   = trim($_POST['fecha_reposicion'] ?? date('Y-m-d'));
        $comprob = trim($_POST['referencia_reposicion'] ?? '');

        if (!$id || $monto <= 0) {
            header("Location: ?route=caja/detalle&id=$id&error=" . urlencode('Monto de reposición inválido.'));
            exit;
        }

        try {
            $this->repo->registrarMovimiento($id, 'REPOSICION', 'Reposición de Caja Chica', $monto, $fecha, $comprob);
            header("Location: ?route=caja/detalle&id=$id&ok=" . urlencode("Reposición de Bs " . number_format($monto, 2, ',', '.') . " registrada."));
            exit;
        } catch (Exception $e) {
            header("Location: ?route=caja/detalle&id=$id&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /** POST: eliminar caja chica */
    public function eliminar(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $this->repo->delete($id);
        }
        header('Location: ?route=caja/index');
        exit;
    }
}
