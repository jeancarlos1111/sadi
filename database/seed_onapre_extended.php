<?php
// database/seed_onapre_extended.php

use App\Database\Connection;

try {
    $pdo = Connection::getInstance();

    // Asegurar que codigo_plan_unico sea UNIQUE para evitar duplicados en reinicios
    $pdo->exec("ALTER TABLE plan_unico_cuentas DROP CONSTRAINT IF EXISTS plan_unico_cuentas_codigo_key;");
    $pdo->exec("ALTER TABLE plan_unico_cuentas ADD CONSTRAINT plan_unico_cuentas_codigo_key UNIQUE (codigo_plan_unico);");

    // Catálogo representativo del Clasificador Presupuestario de Recursos y Egresos (ONAPRE)
    $partidas = [
        // 4.01 GASTOS DE PERSONAL
        ['4.01.00.00.00', 'GASTOS DE PERSONAL'],
        ['4.01.01.00.00', 'Sueldos, Salarios y Otras Retribuciones'],
        ['4.01.01.01.00', 'Sueldos Básicos Personal Fijo'],
        ['4.01.01.02.00', 'Sueldos Básicos Personal Contratado'],
        ['4.01.01.08.00', 'Salarios Personal Obrero'],
        ['4.01.01.18.00', 'Remuneración Sustitutiva'],
        ['4.01.02.00.00', 'Compensaciones y Bonificaciones'],
        ['4.01.02.01.00', 'Bono Vacacional'],
        ['4.01.02.02.00', 'Bonificación de Fin de Año'],
        ['4.01.02.13.00', 'Bono de Alimentación'],
        ['4.01.03.00.00', 'Primas y Complementos'],
        ['4.01.03.01.00', 'Prima por Antigüedad'],
        ['4.01.03.02.00', 'Prima por Profesionalización'],
        ['4.01.04.00.00', 'Aportes Patronales'],
        ['4.01.04.01.00', 'Aporte Patronal al Seguro Social Obligatorio (IVSS)'],
        ['4.01.04.02.00', 'Aporte Patronal al Régimen Prestacional de Vivienda y Hábitat (FAOV)'],
        ['4.01.04.03.00', 'Aporte Patronal al Régimen Prestacional de Empleo'],
        ['4.01.04.05.00', 'Aporte Patronal al INCES'],

        // 4.02 MATERIALES, SUMINISTROS Y MERCANCÍAS
        ['4.02.00.00.00', 'MATERIALES, SUMINISTROS Y MERCANCÍAS'],
        ['4.02.01.00.00', 'Alimentos y Bebidas'],
        ['4.02.01.01.00', 'Alimentos y Bebidas para Personas'],
        ['4.02.02.00.00', 'Productos Agropecuarios, Forestales y Pesqueros'],
        ['4.02.03.00.00', 'Productos de Papel, Cartón e Impresos'],
        ['4.02.03.01.00', 'Papel y Cartón'],
        ['4.02.04.00.00', 'Materiales, Útiles de Oficina e Informática'],
        ['4.02.04.01.00', 'Materiales y Útiles de Oficina'],
        ['4.02.04.02.00', 'Materiales Informáticos y Accesorios'],
        ['4.02.05.00.00', 'Productos Químicos y Conexos'],
        ['4.02.05.01.00', 'Sustancias Químicas Básicas'],
        ['4.02.05.02.00', 'Productos Farmacéuticos y Medicamentos'],
        ['4.02.05.04.00', 'Material Médico Quirúrgico y Odontológico'],
        ['4.02.06.00.00', 'Combustibles y Lubricantes'],
        ['4.02.06.01.00', 'Combustibles'],
        ['4.02.06.02.00', 'Lubricantes y Derivados'],
        ['4.02.08.00.00', 'Prendas de Vestir y Calzado'],
        ['4.02.08.01.00', 'Uniformes y Vestuario'],
        ['4.02.08.02.00', 'Calzados'],
        ['4.02.09.00.00', 'Materiales de Limpieza y Aseo'],
        ['4.02.09.01.00', 'Útiles y Materiales de Limpieza'],
        ['4.02.10.00.00', 'Repuestos y Accesorios Menores'],
        ['4.02.10.01.00', 'Repuestos Menores de Vehículos'],
        ['4.02.10.02.00', 'Repuestos Menores de Maquinarias'],

        // 4.03 SERVICIOS NO PERSONALES
        ['4.03.00.00.00', 'SERVICIOS NO PERSONALES'],
        ['4.03.01.00.00', 'Servicios Básicos'],
        ['4.03.01.01.00', 'Servicio de Electricidad'],
        ['4.03.01.02.00', 'Servicio de Agua'],
        ['4.03.01.03.00', 'Servicio de Aseo Urbano'],
        ['4.03.01.04.00', 'Servicio de Telecomunicaciones (Teléfono/Internet)'],
        ['4.03.01.05.00', 'Servicio de Correo'],
        ['4.03.02.00.00', 'Publicidad, Propaganda y Relaciones Públicas'],
        ['4.03.02.01.00', 'Publicidad y Propaganda'],
        ['4.03.02.02.00', 'Gastos de Relaciones Públicas'],
        ['4.03.03.00.00', 'Impresos, Fotocopias y Reproducciones'],
        ['4.03.03.01.00', 'Impresiones y Reproducciones'],
        ['4.03.04.00.00', 'Viáticos y Pasajes'],
        ['4.03.04.01.00', 'Viáticos dentro del País'],
        ['4.03.04.02.00', 'Pasajes dentro del País'],
        ['4.03.06.00.00', 'Servicios de Alquileres'],
        ['4.03.06.01.00', 'Alquiler de Edificios y Locales'],
        ['4.03.06.02.00', 'Alquiler de Vehículos'],
        ['4.03.07.00.00', 'Servicios de Mantenimiento y Reparación'],
        ['4.03.07.01.00', 'Mantenimiento de Edificaciones y Locales'],
        ['4.03.07.02.00', 'Mantenimiento de Maquinarias y Equipos'],
        ['4.03.07.03.00', 'Mantenimiento de Vehículos'],
        ['4.03.09.00.00', 'Servicios Profesionales y Técnicos'],
        ['4.03.09.01.00', 'Consultoría y Asesoría Legal'],
        ['4.03.09.02.00', 'Consultoría y Asesoría Contable Financiera'],
        ['4.03.18.00.00', 'Impuestos, Derechos y Tasas'],
        ['4.03.18.01.00', 'Impuestos Directos e Indirectos'],

        // 4.04 ACTIVOS REALES (BIENES DE CAPITAL)
        ['4.04.00.00.00', 'ACTIVOS REALES'],
        ['4.04.01.00.00', 'Maquinarias y Equipos'],
        ['4.04.01.01.00', 'Maquinaria Agropecuaria'],
        ['4.04.01.02.00', 'Maquinaria Industrial'],
        ['4.04.02.00.00', 'Equipos de Transporte'],
        ['4.04.02.01.00', 'Vehículos Terrestres'],
        ['4.04.04.00.00', 'Mobiliario y Equipos de Oficina'],
        ['4.04.04.01.00', 'Mobiliario de Oficina'],
        ['4.04.04.02.00', 'Mobiliario para Institutos Educativos'],
        ['4.04.08.00.00', 'Equipos de Computación'],
        ['4.04.08.01.00', 'Equipos de Computación y Accesorios'],
        ['4.04.09.00.00', 'Obras'],
        ['4.04.09.01.00', 'Edificaciones Residenciales'],
        ['4.04.09.02.00', 'Edificaciones No Residenciales'],

        // 4.07 TRANSFERENCIAS Y DONACIONES
        ['4.07.00.00.00', 'TRANSFERENCIAS Y DONACIONES'],
        ['4.07.01.00.00', 'Transferencias Corrientes al Sector Privado'],
        ['4.07.01.01.00', 'Becas'],
        ['4.07.01.02.00', 'Ayudas Médicas y Sociales'],
        ['4.07.01.03.00', 'Donaciones a Personas'],
        ['4.07.03.00.00', 'Transferencias Corrientes al Sector Público'],
        ['4.07.03.01.00', 'Aportes a Entes Descentralizados'],
    ];

    $stmt = $pdo->prepare("INSERT INTO plan_unico_cuentas (codigo_plan_unico, denominacion) VALUES (?, ?) ON CONFLICT (codigo_plan_unico) DO NOTHING");
    
    $inserted = 0;
    foreach ($partidas as $p) {
        $stmt->execute([$p[0], $p[1]]);
        if ($stmt->rowCount() > 0) {
            $inserted++;
        }
    }

    echo "Partidas ONAPRE: $inserted insertadas / omitidas si ya existen.\n";
} catch (Exception $e) {
    echo "Error cargando partidas ONAPRE: " . $e->getMessage() . "\n";
}
