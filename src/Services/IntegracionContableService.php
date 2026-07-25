<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AsientoContableRepository;
use App\Repositories\VinculacionPucContableRepository;
use App\Database\Connection;
use Exception;

class IntegracionContableService
{
    private AsientoContableRepository $asientoRepo;
    private VinculacionPucContableRepository $vinculacionRepo;

    public function __construct()
    {
        $this->asientoRepo = new AsientoContableRepository();
        $this->vinculacionRepo = new VinculacionPucContableRepository();
    }

    /**
     * Registra un asiento contable patrimonial al causar una Factura / Orden de Servicio.
     * DEBE: Gasto/Activo (según vinculación)
     * HABER: Pasivo (Cuentas por Pagar)
     *
     * @param int $idDocumento ID de la tabla donde se originó el causado
     * @param string $fecha Fecha del asiento
     * @param string $concepto Razón del asiento
     * @param array $partidas Array con formato: [['id_codigo_plan_unico' => int, 'monto' => float]]
     */
    public function registrarCausadoFactura(int $idDocumento, string $fecha, string $concepto, array $partidas): void
    {
        $movimientos = [];
        $totalHaber = 0.0;
        
        $db = Connection::getInstance();

        foreach ($partidas as $partida) {
            $id_puc = (int)$partida['id_codigo_plan_unico'];
            $monto = (float)$partida['monto'];
            
            if ($monto <= 0) continue;

            // Buscar cuenta contable vinculada para el DEBE (Gasto)
            $idCuentaGasto = $this->vinculacionRepo->getCuentaContableId($id_puc, 'CAUSADO');
            error_log("DEBUG: getCuentaContableId($id_puc, 'CAUSADO') = " . var_export($idCuentaGasto, true));
            if (!$idCuentaGasto) {
                // Si no hay vinculación específica de causado, no registramos este renglón
                continue;
            }

            // Añadir al DEBE
            $movimientos[] = [
                'id_cuenta' => $idCuentaGasto,
                'tipo'      => 'D',
                'monto'     => $monto
            ];

            // Buscar cuenta contable vinculada para el HABER (Pasivo)
            // Priorizamos si la partida tiene un pasivo específico vinculado.
            $idCuentaPasivo = $this->vinculacionRepo->getCuentaContableId($id_puc, 'PASIVO');
            if (!$idCuentaPasivo) {
                // Si no tiene, buscamos la genérica de ONCOP: 2.1.1.01.01 Proveedores de Bienes y Servicios
                $stmt = $db->prepare("SELECT id_cuenta_contable FROM cuenta_contable WHERE codigo_cuenta = '2.1.1.01.01' LIMIT 1");
                $stmt->execute();
                $idCuentaPasivo = (int)$stmt->fetchColumn();
                if (!$idCuentaPasivo) {
                    throw new Exception("No se encontró la cuenta contable de Pasivo '2.1.1.01.01' ni vinculación de Pasivo para la partida.");
                }
            }

            // Añadir al HABER
            // Para consolidar pasivos de la misma cuenta:
            $key = 'H_' . $idCuentaPasivo;
            if (!isset($movimientos[$key])) {
                $movimientos[$key] = [
                    'id_cuenta' => $idCuentaPasivo,
                    'tipo'      => 'H',
                    'monto'     => 0.0
                ];
            }
            $movimientos[$key]['monto'] += $monto;
            $totalHaber += $monto;
        }

        // Convertir el array asociativo temporal de pasivos a indexado simple
        $asientoFinal = [];
        foreach ($movimientos as $k => $mov) {
            $asientoFinal[] = $mov;
        }

        // Si hay movimientos, registrar el asiento
        if (!empty($asientoFinal) && $totalHaber > 0) {
            $this->asientoRepo->registrarDesdeTransaccion($fecha, "Causado: " . $concepto, $asientoFinal, null);
        }
    }

    /**
     * Registra un asiento contable al procesar un Pago.
     * DEBE: Pasivo (Cuentas por Pagar)
     * HABER: Banco
     *
     * @param int $idSolicitudPago
     * @param string $fecha
     * @param string $concepto
     * @param array $partidas Array con formato: [['id_codigo_plan_unico' => int, 'monto' => float]]
     * @param int $idCtaBancaria La cuenta bancaria de donde sale el dinero
     */
    public function registrarPago(int $idSolicitudPago, string $fecha, string $concepto, array $partidas, int $idCtaBancaria): void
    {
        $db = Connection::getInstance();

        // Obtener la cuenta contable asociada al Banco (Haber)
        $stmt = $db->prepare("SELECT id_cuenta_contable FROM cta_bancaria WHERE id_cta_bancaria = ?");
        $stmt->execute([$idCtaBancaria]);
        $idCuentaBanco = (int)$stmt->fetchColumn();

        if (!$idCuentaBanco) {
            throw new Exception("La cuenta bancaria seleccionada no tiene asignada una Cuenta Contable en el sistema.");
        }

        $movimientos = [];
        $totalDebe = 0.0;

        foreach ($partidas as $partida) {
            $id_puc = (int)$partida['id_codigo_plan_unico'];
            $monto = (float)$partida['monto'];
            
            if ($monto <= 0) continue;

            // Buscar cuenta de Pasivo (DEBE en el Pago)
            $idCuentaPasivo = $this->vinculacionRepo->getCuentaContableId($id_puc, 'PASIVO');
            if (!$idCuentaPasivo) {
                // Si no tiene, buscamos la genérica de ONCOP
                $stmtCxP = $db->prepare("SELECT id_cuenta_contable FROM cuenta_contable WHERE codigo_cuenta = '2.1.1.01.01' LIMIT 1");
                $stmtCxP->execute();
                $idCuentaPasivo = (int)$stmtCxP->fetchColumn();
            }

            if ($idCuentaPasivo) {
                $key = 'D_' . $idCuentaPasivo;
                if (!isset($movimientos[$key])) {
                    $movimientos[$key] = [
                        'id_cuenta' => $idCuentaPasivo,
                        'tipo'      => 'D',
                        'monto'     => 0.0
                    ];
                }
                $movimientos[$key]['monto'] += $monto;
                $totalDebe += $monto;
            }
        }

        // Si tenemos movimientos en el DEBE, armar el asiento
        if ($totalDebe > 0) {
            $asientoFinal = [];
            foreach ($movimientos as $mov) {
                $asientoFinal[] = $mov;
            }

            // Añadir el HABER (Banco) por el total
            $asientoFinal[] = [
                'id_cuenta' => $idCuentaBanco,
                'tipo'      => 'H',
                'monto'     => $totalDebe
            ];

            $this->asientoRepo->registrarDesdeTransaccion($fecha, "Pago: " . $concepto, $asientoFinal, $idSolicitudPago);
        }
    }
    /**
     * Registra un asiento contable automático para la provisión trimestral de Prestaciones Sociales.
     * DEBE: Gasto (5.1.1.04.01)
     * HABER: Pasivo (2.1.1.03.03 - Prestaciones Sociales por Pagar)
     * 
     * @param float $montoTotal El total de la nómina depositada en garantía
     * @param string $fecha La fecha de registro
     * @param string $periodo El periodo procesado
     */
    public function registrarProvisionPrestaciones(float $montoTotal, string $fecha, string $periodo): void
    {
        if ($montoTotal <= 0) return;

        $db = Connection::getInstance();

        // 1. Obtener o crear cuenta de Gasto
        $stmt = $db->prepare("SELECT id_cuenta_contable FROM cuenta_contable WHERE codigo_cuenta = '5.1.1.04.01' LIMIT 1");
        $stmt->execute();
        $idCuentaGasto = $stmt->fetchColumn();
        if (!$idCuentaGasto) {
            $db->exec("INSERT INTO cuenta_contable (codigo_cuenta, denominacion_cuenta, tipo_cuenta) VALUES ('5.1.1.04.01', 'Gasto por Prestaciones Sociales', 'EGRESO')");
            $idCuentaGasto = (int)$db->lastInsertId();
        }

        // 2. Obtener o crear cuenta de Pasivo
        $stmt = $db->prepare("SELECT id_cuenta_contable FROM cuenta_contable WHERE codigo_cuenta = '2.1.1.03.03' LIMIT 1");
        $stmt->execute();
        $idCuentaPasivo = $stmt->fetchColumn();
        if (!$idCuentaPasivo) {
            $db->exec("INSERT INTO cuenta_contable (codigo_cuenta, denominacion_cuenta, tipo_cuenta) VALUES ('2.1.1.03.03', 'Prestaciones Sociales por Pagar', 'PASIVO')");
            $idCuentaPasivo = (int)$db->lastInsertId();
        }

        $asientoFinal = [
            [
                'id_cuenta' => (int)$idCuentaGasto,
                'tipo'      => 'D',
                'monto'     => $montoTotal
            ],
            [
                'id_cuenta' => (int)$idCuentaPasivo,
                'tipo'      => 'H',
                'monto'     => $montoTotal
            ]
        ];

        $this->asientoRepo->registrarDesdeTransaccion($fecha, "Provisión de Prestaciones Sociales - Período " . $periodo, $asientoFinal, null);
    }
}
