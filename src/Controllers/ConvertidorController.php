<?php

namespace App\Controllers;

use App\Database\Connection;
use App\Models\ConvertidorCuenta;
use App\Repositories\ConvertidorCuentaRepository;
use Exception;

class ConvertidorController extends BaseController
{
    private ConvertidorCuentaRepository $repo;

    public function __construct(ConvertidorCuentaRepository $repo)
    {
        $this->repo = $repo;
        if (!isset($_SESSION['usuario'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function index(): void
    {
        try {
            $mapeos = $this->repo->all();
        } catch (Exception $e) {
            $mapeos = [];
            $error = "Error al obtener matriz de conversión: " . $e->getMessage();
        }

        $this->renderView('contabilidad/convertidor_index', [
            'titulo' => 'Convertidor General (Matriz de Cuentas)',
            'mapeos' => $mapeos,
            'error' => $error ?? null,
        ]);
    }

    public function vincular(): void
    {
        $db = Connection::getInstance();
        $partidas = $db->query("SELECT * FROM plan_unico_cuentas WHERE eliminado = 0 ORDER BY codigo_plan_unico ASC")->fetchAll();
        $cuentas = $db->query("SELECT * FROM cuenta_contable WHERE eliminado = 0 ORDER BY codigo_cuenta ASC")->fetchAll();

        // Tipos de operaciones transaccionales configurables
        $operaciones = [
            'CAUSADO' => 'Causado (Recepción CxP)',
            'PAGADO' => 'Pagado (Tesorería)',
            'PAGADO_BANCO' => 'Cuenta Salida de Fondos (Pasivo/Banco)',
            'NOMINA_ASIGNACION' => 'Nómina Asignación (Gasto)',
            'NOMINA_DEDUCCION'  => 'Nómina Deducción (Pasivo a Retenciones)',
        ];

        $this->renderView('contabilidad/convertidor_vincular', [
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
                $item = new ConvertidorCuenta(
                    (int)$_POST['id_codigo_plan_unico'],
                    (int)$_POST['id_cuenta'],
                    $_POST['tipo_operacion'],
                    $_POST['descripcion'] ?? ''
                );
                $this->repo->save($item);

                header('Location: ?route=convertidor/index&success=Vínculo contable agregado correctamente.');
                exit;
            } catch (Exception $e) {
                die("Error al vincular: " . $e->getMessage());
            }
        }
    }

    public function eliminar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = (int)($_POST['id_convertidor'] ?? 0);
                if ($id) {
                    $this->repo->delete($id);
                }
                header('Location: ?route=convertidor/index&success=El vínculo fue deshabilitado exitosamente.');
                exit;
            } catch (Exception $e) {
                die("Error al eliminar: " . $e->getMessage());
            }
        }
    }
}
