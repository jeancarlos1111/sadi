<?php

namespace App\Controllers;

use App\Models\Ficha;
use App\Repositories\CargoRepository;
use App\Repositories\FichaRepository;
use App\Repositories\NominaRepository;
use App\Repositories\PersonalRepository;
use App\Repositories\PlanillaNominaRepository;
use Exception;
use PDO;

class NominaController extends BaseController
{
    private PlanillaNominaRepository $planillaRepo;
    private NominaRepository $nominaRepo;
    private PersonalRepository $personalRepo;
    private CargoRepository $cargoRepo;
    private FichaRepository $fichaRepo;

    public function __construct(
        PlanillaNominaRepository $planillaRepo,
        NominaRepository $nominaRepo,
        PersonalRepository $personalRepo,
        CargoRepository $cargoRepo,
        FichaRepository $fichaRepo
    ) {
        $this->planillaRepo = $planillaRepo;
        $this->nominaRepo   = $nominaRepo;
        $this->personalRepo = $personalRepo;
        $this->cargoRepo    = $cargoRepo;
        $this->fichaRepo    = $fichaRepo;

        if (!isset($_SESSION['usuario'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function index(): void
    {
        try {
            $planillas = $this->planillaRepo->all();
        } catch (\PDOException $e) {
            $planillas = [];
            $error = "Error al obtener histórico de planillas: " . $e->getMessage();
        }

        $this->renderView('nomina/index', [
            'titulo' => 'Histórico de Nóminas Procesadas',
            'planillas' => $planillas,
            'error' => $error ?? null,
        ]);
    }

    public function trabajadores(): void
    {
        $trabajadores = $this->personalRepo->all();

        $this->renderView('nomina/trabajadores', [
            'titulo' => 'Catálogo de Personal Activo',
            'trabajadores' => $trabajadores,
        ]);
    }

    public function crearTrabajador(): void
    {
        $cargos = $this->cargoRepo->all();
        $nominas = $this->nominaRepo->all();

        $this->renderView('nomina/crear_trabajador', [
            'titulo' => 'Registrar Nuevo Trabajador',
            'cargos' => $cargos,
            'nominas' => $nominas,
        ]);
    }

    public function guardarTrabajador(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Para simplificar, usamos el PDO del repo para la transacción del trabajador
                // pero lo ideal sería un WorkerService. Por ahora seguimos el patrón MVC-Repo.
                $db = $this->personalRepo->getPdo();
                $db->beginTransaction();

                // 1. Crear Personal
                $stmtP = $db->prepare("INSERT INTO personal (cedula, nombres, apellidos, fecha_nacimiento) VALUES (?, ?, ?, ?)");
                $stmtP->execute([$_POST['cedula'], $_POST['nombres'], $_POST['apellidos'], $_POST['fecha_nacimiento']]);
                $idPersonal = (int)$db->lastInsertId();

                // 2. Crear Ficha usando repo
                $ficha = new \App\Models\Ficha(
                    0,
                    $idPersonal,
                    (int)$_POST['cod_cargo'],
                    (int)$_POST['cod_nomina'],
                    $_POST['ingreso'],
                    (float)$_POST['sueldo_basico']
                );
                $this->fichaRepo->save($ficha);

                $db->commit();
                header('Location: ?route=nomina/trabajadores&success=Trabajador registrado exitosamente.');
                exit;
            } catch (Exception $e) {
                if (isset($db) && $db->inTransaction()) {
                    $db->rollBack();
                }
                die("Error al registrar trabajador: " . $e->getMessage());
            }
        }
    }

    public function editarTrabajador(): void
    {
        $codFicha = (int)($_GET['cod_ficha'] ?? 0);
        if (!$codFicha) {
            die('Ficha no especificada');
        }

        $db = $this->personalRepo->getPdo();
        $stmt = $db->prepare("
            SELECT f.*, p.cedula, p.nombres, p.apellidos 
            FROM ficha f 
            JOIN personal p ON f.personal_cod_personal = p.cod_personal 
            WHERE f.cod_ficha = ?
        ");
        $stmt->execute([$codFicha]);
        $trabajador = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trabajador) {
            die('Trabajador no encontrado o eliminado');
        }

        $cargos = $this->cargoRepo->all();
        $nominas = $this->nominaRepo->all();

        $this->renderView('nomina/editar_trabajador', [
            'titulo' => 'Editar Sueldo y Cargo del Trabajador',
            'trabajador' => $trabajador,
            'cargos' => $cargos,
            'nominas' => $nominas,
        ]);
    }

    public function actualizarTrabajador(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $codFicha = (int)($_POST['cod_ficha'] ?? 0);
            if (!$codFicha) {
                die('Ficha no especificada');
            }

            try {
                $ficha = $this->fichaRepo->find($codFicha);
                if ($ficha) {
                    $ficha->idCargo = (int)$_POST['cod_cargo'];
                    $ficha->idNomina = (int)$_POST['cod_nomina'];
                    $ficha->sueldoBasico = (float)$_POST['sueldo_basico'];
                    $this->fichaRepo->save($ficha);
                }

                header('Location: ?route=nomina/trabajadores&success=Ficha actualizada exitosamente.');
                exit;
            } catch (Exception $e) {
                die("Error al actualizar ficha: " . $e->getMessage());
            }
        }
    }

    // --- CRUD CARGOS --- //

    public function cargos(): void
    {
        $cargos = $this->cargoRepo->all();

        $this->renderView('nomina/cargos', [
            'titulo' => 'Administración de Cargos',
            'cargos' => $cargos,
        ]);
    }

    public function crearCargo(): void
    {
        $this->renderView('nomina/crear_cargo', [
            'titulo' => 'Registrar Nuevo Cargo',
        ]);
    }

    public function guardarCargo(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = $_POST['nombre'] ?? '';
            if (empty($nombre)) {
                die("Debe indicar el nombre del cargo");
            }

            try {
                $cargo = new \App\Models\Cargo(0, $nombre);
                $this->cargoRepo->save($cargo);
                header('Location: ?route=nomina/cargos&success=Cargo registrado exitosamente.');
                exit;
            } catch (Exception $e) {
                die("Error al registrar cargo: " . $e->getMessage());
            }
        }
    }

    public function editarCargo(): void
    {
        $codCargo = (int)($_GET['cod_cargo'] ?? 0);
        if (!$codCargo) {
            die('Cargo no especificado');
        }

        $cargo = $this->cargoRepo->find($codCargo);
        if (!$cargo) {
            die('Cargo no encontrado');
        }

        $this->renderView('nomina/editar_cargo', [
            'titulo' => 'Editar Cargo',
            'cargo' => $cargo,
        ]);
    }

    public function actualizarCargo(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $codCargo = (int)($_POST['cod_cargo'] ?? 0);
            $nombre = $_POST['nombre'] ?? '';
            if (!$codCargo || empty($nombre)) {
                die('Faltan datos');
            }

            try {
                $cargo = $this->cargoRepo->find($codCargo);
                if ($cargo) {
                    $cargo->nombre = $nombre;
                    $this->cargoRepo->save($cargo);
                }
                header('Location: ?route=nomina/cargos&success=Cargo actualizado exitosamente.');
                exit;
            } catch (Exception $e) {
                die("Error al actualizar cargo: " . $e->getMessage());
            }
        }
    }

    // --- FIN CRUD CARGOS --- //

    public function emitir(): void
    {
        try {
            $nominasActivas = $this->nominaRepo->all();
        } catch (\Exception $e) {
            $nominasActivas = [];
            $error = "No existen nóminas base configuradas.";
        }

        $this->renderView('nomina/emitir', [
            'titulo' => 'Generación de Planilla de Nómina (Lote)',
            'nominasActivas' => $nominasActivas,
            'error' => $error ?? null,
        ]);
    }

    public function procesar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idNomina = (int)($_POST['id_nomina'] ?? 0);
            $fechaEmision = $_POST['fecha_emision'] ?? date('Y-m-d');
            $periodo = $_POST['periodo'] ?? '';

            if (!$idNomina || empty($periodo)) {
                die("Debe seleccionar una nómina e ingresar un período válido.");
            }

            try {
                $this->planillaRepo->generar($idNomina, $periodo, $fechaEmision);

                header('Location: ?route=nomina/index&success=Nómina generada exitosamente, presupuesto afectado y solicitud de pago creada.');
                exit;
            } catch (Exception $e) {
                die("Error Crítico al Procesar Nómina: " . $e->getMessage());
            }
        }
    }
}
