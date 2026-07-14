<?php
// database/seed_oncop_extended.php

use App\Database\Connection;

try {
    $pdo = Connection::getInstance();

    // Make sure codigo_cuenta is UNIQUE so we don't duplicate on multiple seed runs
    $pdo->exec("ALTER TABLE cuenta_contable DROP CONSTRAINT IF EXISTS cuenta_contable_codigo_cuenta_key;");
    $pdo->exec("ALTER TABLE cuenta_contable ADD CONSTRAINT cuenta_contable_codigo_cuenta_key UNIQUE (codigo_cuenta);");

    $cuentas = [
        // CLASE 1: ACTIVO
        ['1.0.0.00.00', 'ACTIVO', 'ACTIVO'],
        ['1.1.0.00.00', 'ACTIVO CIRCULANTE', 'ACTIVO'],
        ['1.1.1.00.00', 'FONDOS', 'ACTIVO'],
        ['1.1.1.01.00', 'CAJA', 'ACTIVO'],
        ['1.1.1.01.01', 'Caja Principal', 'ACTIVO'],
        ['1.1.1.01.02', 'Caja Chica', 'ACTIVO'],
        ['1.1.1.02.00', 'BANCOS', 'ACTIVO'],
        ['1.1.1.02.01', 'Bancos - Cuenta Corriente', 'ACTIVO'],
        ['1.1.1.02.02', 'Bancos - Cuenta de Ahorro', 'ACTIVO'],
        ['1.1.1.03.00', 'FONDOS EN AVANCE Y ANTICIPOS', 'ACTIVO'],
        ['1.1.1.03.01', 'Fondos en Avance', 'ACTIVO'],
        ['1.1.1.03.02', 'Fondos en Anticipo', 'ACTIVO'],
        ['1.1.2.00.00', 'CUENTAS POR COBRAR A CORTO PLAZO', 'ACTIVO'],
        ['1.1.2.01.00', 'EFECTOS POR COBRAR A CORTO PLAZO', 'ACTIVO'],
        ['1.1.2.01.01', 'Cuentas por Cobrar Comerciales', 'ACTIVO'],
        ['1.1.2.03.00', 'ANTICIPOS A PROVEEDORES Y CONTRATISTAS', 'ACTIVO'],
        ['1.1.2.03.01', 'Anticipos a Proveedores', 'ACTIVO'],
        ['1.1.2.03.02', 'Anticipos a Contratistas', 'ACTIVO'],
        ['1.1.2.05.00', 'CUENTAS POR COBRAR EMPLEADOS', 'ACTIVO'],
        ['1.1.2.05.01', 'Préstamos a Empleados', 'ACTIVO'],
        ['1.1.4.00.00', 'INVENTARIOS', 'ACTIVO'],
        ['1.1.4.01.00', 'INVENTARIO DE MATERIALES Y SUMINISTROS', 'ACTIVO'],
        ['1.1.4.01.01', 'Inventario de Materiales de Oficina', 'ACTIVO'],
        ['1.1.4.01.02', 'Inventario de Materiales de Limpieza', 'ACTIVO'],
        ['1.2.0.00.00', 'ACTIVO NO CIRCULANTE', 'ACTIVO'],
        ['1.2.3.00.00', 'BIENES DE USO (PROPIEDAD, PLANTA Y EQUIPO)', 'ACTIVO'],
        ['1.2.3.01.00', 'EDIFICACIONES Y LOCALES', 'ACTIVO'],
        ['1.2.3.01.01', 'Edificios', 'ACTIVO'],
        ['1.2.3.02.00', 'MAQUINARIAS Y EQUIPOS', 'ACTIVO'],
        ['1.2.3.02.01', 'Maquinarias', 'ACTIVO'],
        ['1.2.3.03.00', 'EQUIPOS DE TRANSPORTE', 'ACTIVO'],
        ['1.2.3.03.01', 'Vehículos Livianos', 'ACTIVO'],
        ['1.2.3.04.00', 'MOBILIARIO Y EQUIPOS DE OFICINA', 'ACTIVO'],
        ['1.2.3.04.01', 'Mobiliario de Oficina', 'ACTIVO'],
        ['1.2.3.04.02', 'Equipos de Computación', 'ACTIVO'],
        ['1.2.3.05.00', 'DEPRECIACIÓN ACUMULADA', 'ACTIVO'],
        ['1.2.3.05.01', 'Depreciación Acumulada de Edificios', 'ACTIVO'],
        ['1.2.3.05.02', 'Depreciación Acumulada de Vehículos', 'ACTIVO'],
        ['1.2.3.05.03', 'Depreciación Acumulada de Mobiliario', 'ACTIVO'],
        
        // CLASE 2: PASIVO
        ['2.0.0.00.00', 'PASIVO', 'PASIVO'],
        ['2.1.0.00.00', 'PASIVO CIRCULANTE', 'PASIVO'],
        ['2.1.1.00.00', 'CUENTAS POR PAGAR A CORTO PLAZO', 'PASIVO'],
        ['2.1.1.01.00', 'CUENTAS POR PAGAR PROVEEDORES', 'PASIVO'],
        ['2.1.1.01.01', 'Proveedores de Bienes y Servicios', 'PASIVO'],
        ['2.1.1.02.00', 'CUENTAS POR PAGAR CONTRATISTAS', 'PASIVO'],
        ['2.1.1.02.01', 'Contratistas por Obras', 'PASIVO'],
        ['2.1.1.03.00', 'OBLIGACIONES LABORALES POR PAGAR', 'PASIVO'],
        ['2.1.1.03.01', 'Sueldos y Salarios por Pagar', 'PASIVO'],
        ['2.1.1.03.02', 'Aportes Patronales por Pagar', 'PASIVO'],
        ['2.1.1.03.03', 'Prestaciones Sociales por Pagar', 'PASIVO'],
        ['2.1.1.04.00', 'OBLIGACIONES TRIBUTARIAS Y RETENCIONES', 'PASIVO'],
        ['2.1.1.04.01', 'Retención ISLR por Pagar', 'PASIVO'],
        ['2.1.1.04.02', 'Retención IVA por Pagar', 'PASIVO'],
        ['2.1.1.04.03', 'Retención Fiel Cumplimiento', 'PASIVO'],
        ['2.1.1.04.04', 'Retención Laboral por Pagar (SSO, FAOV, INCES)', 'PASIVO'],
        ['2.2.0.00.00', 'PASIVO NO CIRCULANTE', 'PASIVO'],
        ['2.2.1.00.00', 'CUENTAS POR PAGAR A LARGO PLAZO', 'PASIVO'],
        ['2.2.1.01.00', 'PRÉSTAMOS BANCARIOS A LARGO PLAZO', 'PASIVO'],
        ['2.2.1.01.01', 'Préstamos Entidades Financieras', 'PASIVO'],

        // CLASE 3: PATRIMONIO
        ['3.0.0.00.00', 'PATRIMONIO', 'PATRIMONIO'],
        ['3.1.0.00.00', 'HACIENDA PÚBLICA / CAPITAL', 'PATRIMONIO'],
        ['3.1.1.00.00', 'CAPITAL INSTITUCIONAL', 'PATRIMONIO'],
        ['3.1.1.01.00', 'CAPITAL', 'PATRIMONIO'],
        ['3.1.1.01.01', 'Capital de la Institución', 'PATRIMONIO'],
        ['3.2.0.00.00', 'RESULTADOS', 'PATRIMONIO'],
        ['3.2.1.00.00', 'RESULTADOS DEL EJERCICIO Y ACUMULADOS', 'PATRIMONIO'],
        ['3.2.1.01.00', 'RESULTADOS DEL EJERCICIO', 'PATRIMONIO'],
        ['3.2.1.01.01', 'Resultados del Ejercicio', 'PATRIMONIO'],
        ['3.2.1.02.00', 'RESULTADOS ACUMULADOS', 'PATRIMONIO'],
        ['3.2.1.02.01', 'Resultados Acumulados de Ejercicios Anteriores', 'PATRIMONIO'],

        // CLASE 4: INGRESOS
        ['4.0.0.00.00', 'INGRESOS', 'INGRESO'],
        ['4.1.0.00.00', 'INGRESOS ORDINARIOS', 'INGRESO'],
        ['4.1.1.00.00', 'INGRESOS PROPIOS', 'INGRESO'],
        ['4.1.1.01.00', 'VENTA DE BIENES Y SERVICIOS', 'INGRESO'],
        ['4.1.1.01.01', 'Ingresos por Venta de Bienes', 'INGRESO'],
        ['4.1.1.01.02', 'Ingresos por Prestación de Servicios', 'INGRESO'],
        ['4.1.2.00.00', 'APORTES Y TRANSFERENCIAS', 'INGRESO'],
        ['4.1.2.01.00', 'TRANSFERENCIAS CORRIENTES RECIBIDAS', 'INGRESO'],
        ['4.1.2.01.01', 'Aportes de la República (Situado)', 'INGRESO'],
        ['4.1.2.01.02', 'Aportes de Entes Descentralizados', 'INGRESO'],
        ['4.2.0.00.00', 'INGRESOS EXTRAORDINARIOS', 'INGRESO'],
        ['4.2.1.00.00', 'OTROS INGRESOS', 'INGRESO'],
        ['4.2.1.01.00', 'INGRESOS FINANCIEROS', 'INGRESO'],
        ['4.2.1.01.01', 'Intereses Bancarios', 'INGRESO'],

        // CLASE 5: EGRESOS
        ['5.0.0.00.00', 'EGRESOS', 'EGRESO'],
        ['5.1.0.00.00', 'GASTOS ORDINARIOS', 'EGRESO'],
        ['5.1.1.00.00', 'GASTOS DE PERSONAL', 'EGRESO'],
        ['5.1.1.01.00', 'SUELDOS Y SALARIOS', 'EGRESO'],
        ['5.1.1.01.01', 'Sueldos Básicos', 'EGRESO'],
        ['5.1.1.02.00', 'COMPENSACIONES Y BONIFICACIONES', 'EGRESO'],
        ['5.1.1.02.01', 'Bono Vacacional', 'EGRESO'],
        ['5.1.1.02.02', 'Bonificación de Fin de Año', 'EGRESO'],
        ['5.1.1.03.00', 'APORTES PATRONALES', 'EGRESO'],
        ['5.1.1.03.01', 'Aportes al Seguro Social', 'EGRESO'],
        ['5.1.2.00.00', 'MATERIALES Y SUMINISTROS', 'EGRESO'],
        ['5.1.2.01.00', 'MATERIALES DE OFICINA Y LIMPIEZA', 'EGRESO'],
        ['5.1.2.01.01', 'Gasto por Materiales de Oficina', 'EGRESO'],
        ['5.1.2.01.02', 'Gasto por Materiales de Limpieza', 'EGRESO'],
        ['5.1.3.00.00', 'SERVICIOS NO PERSONALES', 'EGRESO'],
        ['5.1.3.01.00', 'SERVICIOS BÁSICOS', 'EGRESO'],
        ['5.1.3.01.01', 'Energía Eléctrica', 'EGRESO'],
        ['5.1.3.01.02', 'Agua', 'EGRESO'],
        ['5.1.3.01.03', 'Telecomunicaciones', 'EGRESO'],
        ['5.1.3.02.00', 'MANTENIMIENTO Y REPARACIONES', 'EGRESO'],
        ['5.1.3.02.01', 'Mantenimiento de Edificaciones', 'EGRESO'],
        ['5.1.3.02.02', 'Mantenimiento de Equipos', 'EGRESO'],
        ['5.1.4.00.00', 'TRANSFERENCIAS Y DONACIONES', 'EGRESO'],
        ['5.1.4.01.00', 'TRANSFERENCIAS CORRIENTES ENTREGADAS', 'EGRESO'],
        ['5.1.4.01.01', 'Donaciones a Personas', 'EGRESO'],
        ['5.2.0.00.00', 'DEPRECIACIÓN Y AMORTIZACIÓN', 'EGRESO'],
        ['5.2.1.00.00', 'GASTO POR DEPRECIACIÓN', 'EGRESO'],
        ['5.2.1.01.00', 'DEPRECIACIÓN DE BIENES', 'EGRESO'],
        ['5.2.1.01.01', 'Gasto de Depreciación de Edificaciones', 'EGRESO'],
        ['5.2.1.01.02', 'Gasto de Depreciación de Vehículos', 'EGRESO'],
    ];

    $stmt = $pdo->prepare("INSERT INTO cuenta_contable (codigo_cuenta, denominacion_cuenta, tipo_cuenta) VALUES (?, ?, ?) ON CONFLICT (codigo_cuenta) DO NOTHING");
    
    $inserted = 0;
    foreach ($cuentas as $c) {
        $stmt->execute([$c[0], $c[1], $c[2]]);
        if ($stmt->rowCount() > 0) {
            $inserted++;
        }
    }

    echo "Cuentas ONCOP: $inserted insertadas / omitidas si ya existen.\n";
} catch (Exception $e) {
    echo "Error cargando cuentas ONCOP: " . $e->getMessage() . "\n";
}
