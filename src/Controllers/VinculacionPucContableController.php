<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;

use App\Database\Connection;
use App\Models\VinculacionPucContable;
use App\Repositories\VinculacionPucContableRepository;
use Exception;

class VinculacionPucContableController extends BaseController
{
    private VinculacionPucContableRepository $repo;

    public function __construct(VinculacionPucContableRepository $repo)
    {
        $this->repo = $repo;
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function index(): void
    {
        Gate::authorize('presupuesto.vinculacion_puc.ver');
        try {
            $page = (int)($_GET['page'] ?? 1);
            $paginator = $this->repo->paginate('', $page, 15);
            $mapeos = $paginator['data'];
        } catch (Exception $e) {
            $mapeos = [];
            $error = "Error al obtener matriz de conversión: " . $e->getMessage();
        }

        $this->renderView('contabilidad/vinculacion_index', [
            'titulo' => 'Convertidor General (Matriz de Cuentas)',
            'mapeos' => $mapeos,
            'error' => $error ?? null,
                    'paginator' => $paginator,
        ]);
    }

    public function vincular(): void
    {
        $db = Connection::getInstance();
        $partidas = $db->query("SELECT * FROM plan_unico_cuentas WHERE eliminado = false ORDER BY codigo_plan_unico ASC")->fetchAll();
        $cuentas = $db->query("SELECT * FROM cuenta_contable WHERE eliminado = false ORDER BY codigo_cuenta ASC")->fetchAll();

        // Tipos de operaciones transaccionales configurables
        $operaciones = [
            'CAUSADO' => 'Causado (Recepción CxP)',
            'PAGADO' => 'Pagado (Tesorería)',
            'PAGADO_BANCO' => 'Cuenta Salida de Fondos (Pasivo/Banco)',
            'NOMINA_ASIGNACION' => 'Nómina Asignación (Gasto)',
            'NOMINA_DEDUCCION'  => 'Nómina Deducción (Pasivo a Retenciones)',
        ];

        $this->renderView('contabilidad/vinculacion_vincular', [
            'titulo' => 'Vincular Partida con Cuenta Contable',
            'partidas' => $partidas,
            'cuentas' => $cuentas,
            'operaciones' => $operaciones,
        ]);
    }

    public function guardarVinculo(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $item = new VinculacionPucContable(
                    (int)$_POST['id_codigo_plan_unico'],
                    (int)$_POST['id_cuenta_contable'],
                    $_POST['tipo_operacion'],
                    $_POST['descripcion'] ?? ''
                );
                $nuevoId = $this->repo->save($item);

                $modeloDespues = $this->repo->findById($nuevoId);
                $this->audit('vinculacion_puc_contable', 'CREAR', $nuevoId, null, $modeloDespues ? $modeloDespues->toArray() : null);

                header('Location: ?route=vinculacion_puc_contable/index&success=Vínculo contable agregado correctamente.');
                exit;
            } catch (Exception $e) {
                die("Error al vincular: " . $e->getMessage());
            }
        }
    }

    public function eliminar(): void
    {
        Gate::authorize('presupuesto.vinculacion_puc.eliminar');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = (int)($_POST['id_vinculacion'] ?? 0);
                if ($id) {
                    $modeloAnterior = $this->repo->findById($id);
                    $datosAntes = $modeloAnterior ? $modeloAnterior->toArray() : null;
                    $this->repo->delete($id);
                    $this->audit('vinculacion_puc_contable', 'ELIMINAR', $id, $datosAntes, null);
                }
                header('Location: ?route=vinculacion_puc_contable/index&success=El vínculo fue deshabilitado exitosamente.');
                exit;
            } catch (Exception $e) {
                die("Error al eliminar: " . $e->getMessage());
            }
        }
    }
}
