<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;

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

        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function index(): void
    {
        Gate::authorize('nomina.planillas.ver');
        $page = (int)($_GET['page'] ?? 1);
        if ($page < 1) $page = 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        $totalRecords = $this->planillaRepo->countAll();
        $totalPages = max(1, (int)ceil($totalRecords / $perPage));

        $planillas = $this->planillaRepo->all($perPage, $offset);

        $this->renderView('nomina/index', [
            'titulo' => 'Histórico de Planillas de Nómina',
            'planillas' => $planillas,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    public function trabajadores(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        if ($page < 1) $page = 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        $totalRecords = $this->personalRepo->countAll();
        $totalPages = max(1, (int)ceil($totalRecords / $perPage));

        $trabajadores = $this->personalRepo->all('', $perPage, $offset);

        $this->renderView('nomina/trabajadores', [
            'titulo' => 'Catálogo de Personal Activo',
            'trabajadores' => $trabajadores,
            'page' => $page,
            'totalPages' => $totalPages,
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
                $stmtP = $db->prepare("INSERT INTO personal (cedula, nombres, apellidos, fecha_nacimiento, rif, telefono, direccion, correo, estado_civil, cargas_familiares, nivel_instruccion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtP->execute([
                    $_POST['cedula'], 
                    $_POST['nombres'], 
                    $_POST['apellidos'], 
                    $_POST['fecha_nacimiento'],
                    $_POST['rif'] ?? null,
                    $_POST['telefono'] ?? null,
                    $_POST['direccion'] ?? null,
                    $_POST['correo'] ?? null,
                    $_POST['estado_civil'] ?? 'SOLTERO',
                    isset($_POST['cargas_familiares']) ? (int)$_POST['cargas_familiares'] : 0,
                    $_POST['nivel_instruccion'] ?? null
                ]);
                $idPersonal = (int)$db->lastInsertId();

                // 2. Crear Ficha usando repo
                $ficha = new \App\Models\Ficha(
                    0,
                    $idPersonal,
                    (int)$_POST['cod_cargo'],
                    (int)$_POST['cod_nomina'],
                    $_POST['ingreso'],
                    (float)$_POST['sueldo_basico'],
                    30, // diasUtilidades default
                    15, // diasBonoVacacional default
                    (float)($_POST['porcentaje_islr'] ?? 0.0),
                    false, // eliminado
                    $_POST['tipo_relacion_laboral'] ?? 'FIJO',
                    $_POST['banco'] ?? null,
                    $_POST['numero_cuenta'] ?? null,
                    $_POST['tipo_cuenta'] ?? 'CORRIENTE'
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
            SELECT f.*, p.cedula, p.nombres, p.apellidos, p.rif, p.telefono, p.direccion, p.correo, p.estado_civil, p.cargas_familiares, p.nivel_instruccion 
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
                    $nuevaFicha = new \App\Models\Ficha(
                        $ficha->id,
                        $ficha->idPersonal,
                        (int)$_POST['cod_cargo'],
                        (int)$_POST['cod_nomina'],
                        $ficha->fechaIngreso,
                        (float)$_POST['sueldo_basico'],
                        $ficha->diasUtilidades,
                        $ficha->diasBonoVacacional,
                        isset($_POST['porcentaje_islr']) ? (float)$_POST['porcentaje_islr'] : $ficha->porcentajeIslr,
                        $ficha->eliminado,
                        $_POST['tipo_relacion_laboral'] ?? $ficha->tipoRelacionLaboral,
                        $_POST['banco'] ?? $ficha->banco,
                        $_POST['numero_cuenta'] ?? $ficha->numeroCuenta,
                        $_POST['tipo_cuenta'] ?? $ficha->tipoCuenta
                    );
                    $this->fichaRepo->save($nuevaFicha);

                    // Update Personal as well
                    $personal = $this->personalRepo->find($ficha->idPersonal);
                    if ($personal) {
                        $nuevoPersonal = new \App\Models\Personal(
                            $personal->codPersonal,
                            $personal->cedula,
                            $personal->nombres,
                            $personal->apellidos,
                            $personal->fechaNacimiento,
                            $_POST['rif'] ?? $personal->rif,
                            $_POST['telefono'] ?? $personal->telefono,
                            $_POST['direccion'] ?? $personal->direccion,
                            $_POST['correo'] ?? $personal->correo,
                            $_POST['estado_civil'] ?? $personal->estadoCivil,
                            isset($_POST['cargas_familiares']) ? (int)$_POST['cargas_familiares'] : $personal->cargasFamiliares,
                            $_POST['nivel_instruccion'] ?? $personal->nivelInstruccion
                        );
                        $this->personalRepo->save($nuevoPersonal);
                    }
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
        $page = (int)($_GET['page'] ?? 1);
        if ($page < 1) $page = 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        $totalRecords = $this->cargoRepo->countAll();
        $totalPages = max(1, (int)ceil($totalRecords / $perPage));

        $cargos = $this->cargoRepo->all($perPage, $offset);

        $this->renderView('nomina/cargos', [
            'titulo' => 'Administración de Cargos',
            'cargos' => $cargos,
            'page' => $page,
            'totalPages' => $totalPages,
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

    // --- CRUD TIPOS DE NÓMINA --- //

    public function tiposNomina(): void
    {
        Gate::authorize('nomina.planillas.ver');
        $nominas = $this->nominaRepo->all();

        $this->renderView('nomina/tipos_nomina', [
            'titulo' => 'Tipos de Nómina',
            'nominas' => $nominas,
        ]);
    }

    public function crearNomina(): void
    {
        Gate::authorize('nomina.planillas.ver');
        $this->renderView('nomina/crear_nomina', [
            'titulo' => 'Registrar Tipo de Nómina',
        ]);
    }

    public function guardarNomina(): void
    {
        Gate::authorize('nomina.planillas.ver');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $denom = trim($_POST['denom'] ?? '');
            $tipoPeriodo = trim($_POST['tipo_periodo'] ?? '');

            if (empty($denom) || empty($tipoPeriodo)) {
                header('Location: ?route=nomina/crearNomina&error=' . urlencode('Debe completar todos los campos.'));
                exit;
            }

            $nomina = new \App\Models\Nomina(0, $denom, $tipoPeriodo);
            $id = $this->nominaRepo->save($nomina);

            if ($id) {
                $this->audit('nomina', 'CREAR', (int)$id, null, ['denom' => $denom, 'tipo_periodo' => $tipoPeriodo]);
                header('Location: ?route=nomina/tiposNomina&success=' . urlencode('Tipo de nómina creado correctamente.'));
            } else {
                header('Location: ?route=nomina/tiposNomina&error=' . urlencode('Error al crear el tipo de nómina.'));
            }
            exit;
        }
    }

    public function editarNomina(): void
    {
        Gate::authorize('nomina.planillas.ver');
        $id = (int)($_GET['id'] ?? 0);
        $nomina = $this->nominaRepo->find($id);
        if (!$nomina) {
            header('Location: ?route=nomina/tiposNomina&error=' . urlencode('Nómina no encontrada.'));
            exit;
        }

        $this->renderView('nomina/editar_nomina', [
            'titulo' => 'Editar Tipo de Nómina',
            'nomina' => $nomina,
        ]);
    }

    public function actualizarNomina(): void
    {
        Gate::authorize('nomina.planillas.ver');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['cod_nomina'] ?? 0);
            $denom = trim($_POST['denom'] ?? '');
            $tipoPeriodo = trim($_POST['tipo_periodo'] ?? '');

            $antes = $this->nominaRepo->find($id);
            $nomina = new \App\Models\Nomina($id, $denom, $tipoPeriodo);
            $this->nominaRepo->save($nomina);

            $this->audit('nomina', 'EDITAR', $id, $antes?->toArray(), $nomina->toArray());
            header('Location: ?route=nomina/tiposNomina&success=' . urlencode('Nómina actualizada correctamente.'));
            exit;
        }
    }

    public function eliminarNomina(): void
    {
        Gate::authorize('nomina.planillas.ver');
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $antes = $this->nominaRepo->find($id);
            $this->nominaRepo->delete($id);
            $this->audit('nomina', 'ELIMINAR', $id, $antes?->toArray(), null);
        }
        header('Location: ?route=nomina/tiposNomina&success=' . urlencode('Nómina eliminada correctamente.'));
        exit;
    }

    // --- FIN CRUD TIPOS DE NÓMINA --- //

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
                header('Location: ?route=nomina/emitir&error=' . urlencode('Debe seleccionar una nómina e ingresar un período válido.'));
                exit;
            }

            try {
                $this->planillaRepo->generar($idNomina, $periodo, $fechaEmision);
                header('Location: ?route=nomina/index&success=' . urlencode('Nómina generada exitosamente. Presupuesto afectado y solicitud de pago creada.'));
                exit;
            } catch (Exception $e) {
                header('Location: ?route=nomina/emitir&error=' . urlencode('Error al procesar nómina: ' . $e->getMessage()));
                exit;
            }
        }
    }
}

