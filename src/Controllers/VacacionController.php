<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\VacacionRepository;
use App\Repositories\FichaRepository;
use App\Repositories\PersonalRepository;
use App\Models\Vacacion;
use App\DTOs\VacacionDTO;

class VacacionController extends BaseController
{
    private VacacionRepository $vacacionRepo;
    private FichaRepository $fichaRepo;
    private PersonalRepository $personalRepo;

    public function __construct()
    {
        $this->vacacionRepo = new VacacionRepository();
        $this->fichaRepo = new FichaRepository();
        $this->personalRepo = new PersonalRepository();

        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function index(): void
    {
        \App\Auth\Gate::authorize('nomina.planillas.ver');

        $vacaciones = $this->vacacionRepo->all();
        $dtos = [];
        foreach ($vacaciones as $vacacion) {
            $ficha = $this->fichaRepo->find($vacacion->codFicha);
            $personal = $ficha ? $this->personalRepo->find($ficha->idPersonal) : null;
            $nombre = $personal ? $personal->nombres . ' ' . $personal->apellidos : 'Desconocido';
            
            $dto = VacacionDTO::fromModel($vacacion);
            $dto->nombreTrabajador = $nombre;
            $dtos[] = $dto;
        }

        $this->renderView('vacaciones/index', [
            'titulo' => 'Vacaciones y Bono Vacacional (LOTTT)',
            'vacaciones' => $dtos
        ]);
    }

    public function crear(): void
    {
        \App\Auth\Gate::authorize('nomina.planillas.ver');

        $fichas = $this->fichaRepo->allActivos();
        
        $trabajadores = [];
        foreach ($fichas as $f) {
            $p = $this->personalRepo->find($f->idPersonal);
            if ($p) {
                $trabajadores[] = [
                    'cod_ficha' => $f->id,
                    'cedula' => $p->cedula,
                    'nombre' => $p->nombres . ' ' . $p->apellidos,
                    'fecha_ingreso' => $f->fechaIngreso,
                ];
            }
        }

        $this->renderView('vacaciones/crear', [
            'titulo' => 'Registrar Vacaciones (LOTTT)',
            'trabajadores' => $trabajadores
        ]);
    }

    public function simular(): void
    {
        \App\Auth\Gate::authorize('nomina.planillas.ver');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            return;
        }

        $codFicha = (int)$_POST['cod_ficha'];
        $fechaSalida = $_POST['fecha_salida'];

        $ficha = $this->fichaRepo->find($codFicha);
        if (!$ficha) {
            echo "<div class='alert alert-danger'>Trabajador no encontrado.</div>";
            return;
        }

        $vacacion = $this->vacacionRepo->generarSimulacion($ficha, $fechaSalida);

        if ($this->vacacionRepo->tieneVacacionSolapada($codFicha, $vacacion->fechaSalida, $vacacion->fechaRetorno)) {
            echo "<div class='alert alert-warning mb-0'><i class='fas fa-exclamation-triangle'></i> <strong>Advertencia:</strong> Este trabajador ya tiene un registro de vacaciones que se solapa con este período.</div>";
        }
        
        // Render part of HTML for Server-Driven UI
        $html = "
            <div>
                <dl class='row mb-0'>
                    <dt class='col-sm-6'>Fecha Ingreso:</dt>
                    <dd class='col-sm-6'>{$ficha->fechaIngreso}</dd>
                    
                    <dt class='col-sm-6'>Fecha Salida:</dt>
                    <dd class='col-sm-6'>{$vacacion->fechaSalida}</dd>
                    
                    <dt class='col-sm-6'>Fecha Retorno:</dt>
                    <dd class='col-sm-6'>{$vacacion->fechaRetorno} <br><small class='text-muted'>(Omitiendo sábados y domingos)</small></dd>
                </dl>
                <hr>
                <dl class='row mb-0'>
                    <dt class='col-sm-8'>Días Disfrute (Art. 190):</dt>
                    <dd class='col-sm-4 text-right'>{$vacacion->diasDisfrute} días hábiles</dd>

                    <dt class='col-sm-8'>Días Bono (Art. 192):</dt>
                    <dd class='col-sm-4 text-right'>{$vacacion->diasBono} días</dd>
                </dl>
                <hr>
                <div class='text-center'>
                    <h4 class='text-success font-weight-bold mb-0'>Total a Pagar: Bs " . number_format($vacacion->montoTotal, 2, ',', '.') . "</h4>
                    <p class='text-muted small mt-1'>
                        (Vacaciones: Bs " . number_format($vacacion->montoVacaciones, 2, ',', '.') . " + Bono: Bs " . number_format($vacacion->montoBono, 2, ',', '.') . ")
                    </p>
                </div>
            </div>
            <input type='hidden' name='fecha_retorno' value='{$vacacion->fechaRetorno}'>
            <input type='hidden' name='dias_disfrute' value='{$vacacion->diasDisfrute}'>
            <input type='hidden' name='dias_bono' value='{$vacacion->diasBono}'>
            <input type='hidden' name='monto_vacaciones' value='{$vacacion->montoVacaciones}'>
            <input type='hidden' name='monto_bono' value='{$vacacion->montoBono}'>
            <input type='hidden' name='monto_total' value='{$vacacion->montoTotal}'>
        ";

        echo $html;
    }

    public function guardar(): void
    {
        \App\Auth\Gate::authorize('nomina.planillas.ver');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $codFicha = (int)$_POST['cod_ficha'];
            $fechaSalida = $_POST['fecha_salida'];
            $fechaRetorno = $_POST['fecha_retorno'];
            
            // Validar solapamiento
            if ($this->vacacionRepo->tieneVacacionSolapada($codFicha, $fechaSalida, $fechaRetorno)) {
                header('Location: ?route=vacacion/index&error=' . urlencode('El trabajador ya tiene un registro de vacaciones que se solapa con este período.'));
                exit;
            }

            $vacacion = new Vacacion(
                $codFicha,
                $fechaSalida,
                $fechaRetorno,
                (int)$_POST['dias_disfrute'],
                (int)$_POST['dias_bono'],
                (float)$_POST['monto_vacaciones'],
                (float)$_POST['monto_bono'],
                (float)$_POST['monto_total'],
                'Pagado'
            );

            $result = $this->vacacionRepo->save($vacacion);
            
            if ($result) {
                $idInsertado = is_int($result) ? $result : $vacacion->id;
                $this->audit('vacaciones', 'CREAR', $idInsertado, null, $vacacion->toArray());
            }

            header('Location: ?route=vacacion/index&msg=Vacaciones registradas correctamente');
            exit;
        }
    }
}
