<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;
use App\Models\PrestacionGarantia;
use App\Repositories\FichaRepository;
use App\Repositories\PersonalRepository;
use App\Repositories\PrestacionesRepository;
use App\Services\IntegracionContableService;
use Exception;
use PDO;

class PrestacionesController extends BaseController
{
    private PrestacionesRepository $prestacionesRepo;
    private FichaRepository $fichaRepo;
    private PersonalRepository $personalRepo;
    private IntegracionContableService $contableService;

    public function __construct(
        PrestacionesRepository $prestacionesRepo,
        FichaRepository $fichaRepo,
        PersonalRepository $personalRepo,
        IntegracionContableService $contableService
    ) {
        $this->prestacionesRepo = $prestacionesRepo;
        $this->fichaRepo = $fichaRepo;
        $this->personalRepo = $personalRepo;
        $this->contableService = $contableService;

        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function index(): void
    {
        Gate::authorize('nomina.prestaciones.ver');
        
        $trabajadores = [];
        $page = (int)($_GET['page'] ?? 1);
        if ($page < 1) $page = 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $totalPages = 1;

        try {
            $db = $this->personalRepo->getPdo();
            
            // Total count for pagination
            $sqlCount = "
                SELECT COUNT(*) FROM personal p
                JOIN ficha f ON p.cod_personal = f.personal_cod_personal
                WHERE p.eliminado = false AND f.eliminado = false
            ";
            $totalRecords = (int)$db->query($sqlCount)->fetchColumn();
            $totalPages = max(1, (int)ceil($totalRecords / $perPage));

            // Paginated data
            $sql = "
                SELECT 
                    p.cod_personal, p.cedula, p.nombres, p.apellidos,
                    f.cod_ficha, f.ingreso, f.sueldo_basico,
                    COALESCE(SUM(pg.monto), 0) as total_acumulado,
                    COALESCE(SUM(pg.dias_depositados), 0) as dias_acumulados
                FROM personal p
                JOIN ficha f ON p.cod_personal = f.personal_cod_personal
                LEFT JOIN prestacion_garantia pg ON f.cod_ficha = pg.cod_ficha AND pg.eliminado = false
                WHERE p.eliminado = false AND f.eliminado = false
                GROUP BY p.cod_personal, p.cedula, p.nombres, p.apellidos, f.cod_ficha, f.ingreso, f.sueldo_basico
                ORDER BY p.nombres ASC
                LIMIT :limit OFFSET :offset
            ";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $trabajadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = "Error al obtener pasivos laborales: " . $e->getMessage();
        }

        $this->renderView('nomina/prestaciones/index', [
            'titulo' => 'Pasivo Laboral (Prestaciones Sociales)',
            'trabajadores' => $trabajadores,
            'page' => $page,
            'totalPages' => $totalPages,
            'error' => $error ?? null
        ]);
    }

    public function procesarTrimestre(): void
    {
        Gate::authorize('nomina.prestaciones.crear');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $periodo = $_POST['periodo'] ?? '';
            $fechaProceso = $_POST['fecha_proceso'] ?? date('Y-m-d');
            
            if (empty($periodo)) {
                header('Location: ?route=prestaciones/procesarTrimestre&error=Debe especificar el período');
                exit;
            }

            try {
                if ($this->prestacionesRepo->existePeriodoProcesado($periodo, 'TRIMESTRAL')) {
                    throw new Exception("El período $periodo ya fue procesado anteriormente.");
                }

                // Obtener todo el personal activo
                $db = $this->personalRepo->getPdo();
                $sql = "SELECT cod_ficha, sueldo_basico, dias_utilidades, dias_bono_vacacional FROM ficha WHERE eliminado = false";
                $fichas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

                $nuevasGarantias = [];
                $montoTotalProcesado = 0.0;

                foreach ($fichas as $f) {
                    $sueldo = (float)$f['sueldo_basico'];
                    $diasUti = (int)$f['dias_utilidades'];
                    $diasBono = (int)$f['dias_bono_vacacional'];

                    // Fórmula de Salario Integral Diario
                    $sueldoDiario = $sueldo / 30;
                    $alicuotaBono = ($sueldoDiario * $diasBono) / 12;
                    $alicuotaUtilidades = ($sueldoDiario * $diasUti) / 12;
                    $salarioIntegralDiario = $sueldoDiario + ($alicuotaBono / 30) + ($alicuotaUtilidades / 30);
                    // Nota: Las alícuotas se dividen entre 30 para obtener la fracción diaria, o se calcula (dias/360) * sueldo_diario
                    // Ajuste preciso legal:
                    $salarioIntegralDiario = $sueldoDiario + (($diasBono * $sueldoDiario)/360) + (($diasUti * $sueldoDiario)/360);

                    $montoDeposito = round($salarioIntegralDiario * 15, 2);

                    $garantia = new PrestacionGarantia(
                        null,
                        (int)$f['cod_ficha'],
                        $periodo,
                        'TRIMESTRAL',
                        15,
                        round($salarioIntegralDiario, 2),
                        $montoDeposito,
                        $fechaProceso
                    );

                    $nuevasGarantias[] = $garantia;
                    $montoTotalProcesado += $montoDeposito;
                }

                if (count($nuevasGarantias) > 0) {
                    $this->prestacionesRepo->procesarMasivo($nuevasGarantias);
                    
                    // Integración contable automática
                    $this->contableService->registrarProvisionPrestaciones($montoTotalProcesado, $fechaProceso, $periodo);
                    
                    // Auditoría
                    $this->audit('prestacion_garantia', 'PROCESO_TRIMESTRAL', 0, [], ['periodo' => $periodo, 'fichas_procesadas' => count($nuevasGarantias), 'monto' => $montoTotalProcesado]);
                }

                header('Location: ?route=prestaciones/index&success=Trimestre procesado y asiento contable generado exitosamente.');
                exit;
            } catch (Exception $e) {
                header('Location: ?route=prestaciones/procesarTrimestre&error=' . urlencode($e->getMessage()));
                exit;
            }
        }

        // Si es GET, mostrar la vista
        $this->renderView('nomina/prestaciones/procesar_trimestre', [
            'titulo' => 'Procesar Garantía Trimestral',
        ]);
    }

    public function estadoCuenta(): void
    {
        Gate::authorize('nomina.prestaciones.ver');
        $codFicha = (int)($_GET['cod_ficha'] ?? 0);
        
        if (!$codFicha) {
            die("Ficha no especificada.");
        }

        try {
            $db = $this->personalRepo->getPdo();
            $stmt = $db->prepare("
                SELECT p.cedula, p.nombres, p.apellidos, f.ingreso, f.sueldo_basico
                FROM ficha f JOIN personal p ON f.personal_cod_personal = p.cod_personal 
                WHERE f.cod_ficha = ?
            ");
            $stmt->execute([$codFicha]);
            $trabajador = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$trabajador) {
                throw new Exception("Trabajador no encontrado");
            }

            $movimientos = $this->prestacionesRepo->getEstadoCuenta($codFicha);
            
            $this->renderView('nomina/prestaciones/estado_cuenta', [
                'titulo' => 'Estado de Cuenta: Prestaciones Sociales',
                'trabajador' => $trabajador,
                'movimientos' => $movimientos
            ]);
        } catch (Exception $e) {
            die("Error al consultar estado de cuenta: " . $e->getMessage());
        }
    }
}
