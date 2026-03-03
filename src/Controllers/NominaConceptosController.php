<?php

namespace App\Controllers;

use App\Models\ConceptoNomina;
use App\Repositories\ConceptoNominaRepository;
use App\Services\FormulaEvaluator;
use Exception;

class NominaConceptosController extends BaseController
{
    private ConceptoNominaRepository $repo;

    public function __construct(ConceptoNominaRepository $repo)
    {
        $this->repo = $repo;

        if (!isset($_SESSION['usuario'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function index(): void
    {
        $conceptos = $this->repo->all();
        $this->renderView('nomina/conceptos/index', [
            'titulo'   => 'Conceptos de Nómina (Asignaciones y Deducciones)',
            'conceptos' => $conceptos,
        ]);
    }

    public function form(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $concepto = null;
        $error = null;

        if ($id) {
            $concepto = $this->repo->find($id);
            if (!$concepto) {
                $error = "Concepto no encontrado.";
            }
        }

        $this->renderView('nomina/conceptos/form', [
            'titulo'   => $concepto ? 'Editar Concepto de Nómina' : 'Nuevo Concepto de Nómina',
            'concepto' => $concepto,
            'error'    => $error,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=nomina/conceptos');
            exit;
        }

        $id          = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $codigo      = trim($_POST['codigo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $tipo        = $_POST['tipo_concepto'] ?? 'A';
        $formulaValor = (float)($_POST['formula_valor'] ?? 0);
        $esPorcentaje = (bool)($_POST['es_porcentaje'] ?? 0);
        $formulaExpr  = trim($_POST['formula_expr'] ?? '');

        // Validar fórmula si fue definida
        if ($formulaExpr !== '') {
            $err = FormulaEvaluator::validate($formulaExpr);
            if ($err !== null) {
                $concepto = $id ? $this->repo->find($id) : null;
                $this->renderView('nomina/conceptos/form', [
                    'titulo'   => $id ? 'Editar Concepto de Nómina' : 'Nuevo Concepto de Nómina',
                    'concepto' => $concepto,
                    'error'    => "Fórmula inválida: {$err}",
                ]);

                return;
            }
        }

        $concepto = new ConceptoNomina(
            $codigo,
            $descripcion,
            $tipo,
            $formulaValor,
            $esPorcentaje,
            $formulaExpr !== '' ? $formulaExpr : null,
            $id
        );

        try {
            $this->repo->save($concepto);
            header('Location: ?route=nomina/conceptos');
            exit;
        } catch (Exception $e) {
            $this->renderView('nomina/conceptos/form', [
                'titulo'   => $id ? 'Editar Concepto de Nómina' : 'Nuevo Concepto de Nómina',
                'concepto' => $concepto,
                'error'    => "Error al guardar: " . $e->getMessage(),
            ]);
        }
    }

    public function eliminar(): void
    {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $this->repo->delete((int)$id);
        }
        header('Location: ?route=nomina/conceptos');
        exit;
    }
}
