<?php

namespace App\Controllers;

// Keep this if the model is still used directly somewhere, though the repo should abstract it.
use App\Repositories\PresupuestoIngresoRepository;
use Exception; // Keep this for general exceptions
use PDOException; // Add this for database-specific exceptions

class PresupuestoIngresoController extends BaseController // Changed from BaseController to HomeController in the instruction, but the instruction's snippet for the class declaration is incomplete. Assuming BaseController for now, or if HomeController is a parent of BaseController, then it's fine. Let's stick to the instruction's explicit change for the class name.
{
    private PresupuestoIngresoRepository $repo;

    public function __construct(PresupuestoIngresoRepository $repo)
    {
        // The original constructor's session check
        if (!isset($_SESSION['usuario'])) {
            header('Location: ?route=auth/login');
            exit;
        }
        // Inject the repository
        $this->repo = $repo;
    }

    public function index(): void
    {
        try {
            $formulado = $this->repo->allFormulado(); // Use repository
        } catch (PDOException $e) {
            // Handle database error, e.g., log it and show a user-friendly message
            die("Error al cargar datos de formulación: " . $e->getMessage());
        }


        $this->renderView('presupuesto_ingreso/index', [
            'titulo' => 'Presupuesto de Ingresos',
            'formulado' => $formulado,
        ]);
    }

    public function formular(): void
    {
        try {
            $ramos = $this->repo->allRamos(); // Use repository
        } catch (PDOException $e) {
            die("Error al cargar ramos: " . $e->getMessage());
        }


        $this->renderView('presupuesto_ingreso/formular', [
            'titulo' => 'Formulación de Ingresos (Estimado)',
            'ramos' => $ramos,
        ]);
    }

    public function guardarFormulacion(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idRamo = (int)$_POST['id_ramo'];
            $monto = (float)str_replace(',', '.', $_POST['monto_estimado']); // Use original field name, assuming it's `monto_estimado`

            try {
                $this->repo->formular($idRamo, $monto); // Use repository
                header('Location: ?route=presupuestoIngreso/index&success=Estimación de Ingreso registrada correctamente.'); // Original success message
                exit;
            } catch (Exception $e) {
                die("Error al formular ingreso: " . $e->getMessage());
            }
        }
    }

    public function recaudar(): void
    {
        try {
            $formulado = $this->repo->allFormulado(); // Use repository
        } catch (PDOException $e) {
            die("Error al cargar datos de formulación para recaudación: " . $e->getMessage());
        }


        $this->renderView('presupuesto_ingreso/recaudar', [
            'titulo' => 'Liquidación y Recaudación Efectiva',
            'formulado' => $formulado,
        ]);
    }

    public function procesarRecaudacion(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id_presupuesto_ingreso'];
            $monto = (float)$_POST['monto_recaudado'];
            $referencia = trim($_POST['referencia'] ?? 'S/R'); // Use original default 'S/R'

            try {
                $this->repo->recaudar($id, $monto, $referencia); // Use repository
                header('Location: ?route=presupuestoIngreso/index&success=Recaudación registrada exitosamente y el asiento contable fue generado.'); // Original success message
                exit;
            } catch (Exception $e) {
                die("Error en recaudación: " . $e->getMessage());
            }
        }
    }
}
