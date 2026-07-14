<?php

// Archivo autogenerado con datos maestros

use App\Database\Connection;

$pdo = Connection::getInstance();

try {
    $pdo->beginTransaction();

    $pdo->exec(<<<SQL
INSERT INTO tipo_servicio (id_tipo_servicio, denominacion) VALUES
(1, 'Servicios Profesionales'),
(2, 'Mantenimiento y Reparación'),
(3, 'Servicios de Transporte');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO servicio (id_servicio, denominacion, id_tipo_servicio, aplicar_iva) VALUES
(1, 'Consultoría Técnica',         1, true),
(2, 'Mantenimiento de Equipos',    2, true),
(3, 'Transporte de Correspondencia', 3, false);
SQL
    );

    $pdo->exec(<<<SQL
-- Seed basic data
INSERT INTO tipo_organizacion (id_tipo_organizacion, nombre_tipo_organizacion) VALUES 
(1, 'Firma Personal'),
(2, 'Compania Anonima (C.A.)'),
(3, 'Sociedad Anonima (S.A.)');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO tipo_de_articulo (id_tipo_de_articulo, denominacion_tda, tipo_tda) VALUES 
(1, 'Material de Oficina', 1),
(2, 'Equipos de Computación', 1);
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO unidades_de_medida (id_unidades_de_medida, denominacion_udm, unidades_udm) VALUES 
(1, 'Unidad', 'UND'),
(2, 'Caja', 'CAJ'),
(3, 'Resma', 'RES');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO proveedor (id_proveedor, rif_proveedor, compania_proveedor, id_tipo_organizacion, direccion_proveedor, telefono_proveedor) VALUES 
(1, 'J-12345678-9', 'Insumos CA', 2, 'Centro de la ciudad', '0212-5555555'),
(2, 'V-87654321-0', 'Servicios Juan', 1, 'Calle Principal', '0414-0001122');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO articulo (id_articulo, denominacion_a, id_tipo_de_articulo, id_unidades_de_medida, id_codigo_plan_unico, aplicar_iva) VALUES 
(1, 'Lápices Grafito', 1, 2, 1, true),
(2, 'Resma Papel Carta', 1, 3, 1, true),
(3, 'Monitor 24 pulgadas', 2, 1, 2, true);
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO tipo_documento (id_tipo_documento, denominacion_tipo_documento, afecta_presupuesto_tipo_documento, siglas_tipo_documento) VALUES 
(1, 'Factura', true, 'FAC'),
(2, 'Nota de Entrega', false, 'NE');
SQL
    );

    $pdo->exec(<<<SQL
-- Seed mock Presupuesto Data
INSERT INTO estruc_presupuestaria (id_estruc_presupuestaria, descripcion_ep) VALUES 
(1, 'Administración Central - Recursos Humanos'),
(2, 'Tecnología e Informática');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO plan_unico_cuentas (id_codigo_plan_unico, codigo_plan_unico, denominacion) VALUES 
(1, '401.01', 'Sueldos Básicos Personal Fijo'),
(2, '402.03', 'Materiales y Útiles de Oficina'),
(3, '404.04', 'Equipos de Computación');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO presupuesto_gastos (id_presupuesto_gastos, id_estruc_presupuestaria, id_codigo_plan_unico, monto_asignado) VALUES 
(1, 1, 1, 5000000.00),
(2, 1, 2, 50000.00),
(3, 2, 3, 120000.00);
SQL
    );

    $pdo->exec(<<<SQL
-- Seed mock Inventario Data
INSERT INTO ubicacion_articulo (id_ubicacion_articulo, denominacion_ua) VALUES 
(1, 'Almacén Principal (Sede)'),
(2, 'Depósito Auxiliar');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO inventario_insumos (id_inventario_insumos, id_articulo, cantidad_ii) VALUES 
(1, 1, 1500), -- 1500 Lápices en Principal
(2, 2, 50);
SQL
    );

    $pdo->exec(<<<SQL
-- 50 Resmas en Principal

INSERT INTO inventario_bienes (id_inventario_bienes, id_articulo, id_proveedor, id_ubicacion_articulo, acronimo_id_ib) VALUES
(1, 3, 1, 1, 'SADI-BIEN-001');
SQL
    );

    $pdo->exec(<<<SQL
-- Seed mock Banco Data
INSERT INTO banco (id_banco, nombre_banco) VALUES 
(1, 'Banco de Venezuela'),
(2, 'Banco Banesco');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO cta_bancaria (id_cta_bancaria, id_banco, numero_cta_bancaria) VALUES 
(1, 1, '0102-0000-0000-0000-1234'),
(2, 2, '0134-1111-2222-3333-4567');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO tipo_operacion_bancaria (id_tipo_operacion_bancaria, nombre_tipo_operacion_bancaria, acronimo_tipo_operacion_bancaria) VALUES 
(1, 'Depósito', 'DP'),
(2, 'Cheque', 'CH'),
(3, 'Transferencia', 'TR'),
(4, 'Nota de Débito', 'ND');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO movimiento_bancario (id_movimiento_bancario, id_cta_bancaria, id_tipo_operacion_bancaria, monto, fecha, referencia) VALUES 
(1, 1, 1, 500000.00, '2026-02-01', 'DEP-9991'),
(2, 1, 3, -15000.00, '2026-02-05', 'TR-8882');
SQL
    );

    $pdo->exec(<<<SQL
-- Seed mock Retenciones Data
INSERT INTO factura (id_factura, id_proveedor, numero_factura, fecha_factura, monto_base, monto_impuesto, monto_total) VALUES 
(1, 1, 'F-000192', '2026-02-10', 1000.00, 160.00, 1160.00),
(2, 2, 'F-558291', '2026-02-15', 5000.00, 800.00, 5800.00);
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO comprobante_retencion (id_comprobante_retencion, id_factura, tipo_retencion, numero_comprobante, porcentaje, monto_retenido, fecha_emision) VALUES 
(1, 1, 'IVA', '202602-00000001', 75.00, 120.00, '2026-02-12'),
(2, 1, 'ISLR', '202602-00000002', 2.00, 20.00, '2026-02-12'),
(3, 2, 'IVA', '202602-00000003', 100.00, 800.00, '2026-02-16');
SQL
    );

    $pdo->exec(<<<SQL
-- Seed mock Contabilidad Data
INSERT INTO cuenta_contable (id_cuenta_contable, codigo_cuenta, denominacion_cuenta, tipo_cuenta) VALUES 
(1, '1.1.01.01', 'Caja Principal', 'ACTIVO'),
(2, '1.1.02.01', 'Bancos Nacionales', 'ACTIVO'),
(3, '2.1.01.01', 'Cuentas por Pagar Proveedores', 'PASIVO'),
(4, '5.1.01.01', 'Gastos de Personal', 'EGRESO');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO comprobante_diario (id_comprobante_diario, numero_comprobante, fecha_comprobante, concepto) VALUES 
(1, 'CD-0001', '2026-02-01', 'Apertura de Ejercicio Fiscal'),
(2, 'CD-0002', '2026-02-05', 'Registro de Pago a Proveedores');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO movimiento_contable (id_movimiento_contable, id_comprobante_diario, id_cuenta_contable, tipo_operacion_mc, monto_mc) VALUES 
(1, 1, 2, 'D', 1000000.00),
(2, 1, 3, 'H', 1000000.00),
(3, 2, 3, 'D', 50000.00),
(4, 2, 2, 'H', 50000.00);
SQL
    );

    $pdo->exec(<<<SQL
-- Seed mock Nomina Data
INSERT INTO personal (cod_personal, cedula, nombres, apellidos, fecha_nacimiento) VALUES 
(1, 'V-12345678', 'Juan', 'Pérez', '1980-05-15'),
(2, 'V-87654321', 'María', 'Gómez', '1992-10-20');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO cargo (cod_cargo, nombre) VALUES 
(1, 'Director General'),
(2, 'Analista de Sistemas');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO nomina (cod_nomina, denom, tipo_periodo) VALUES 
(1, 'Nómina Empleados Fijos', 'Mensual'),
(2, 'Nómina Obreros', 'Semanal');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO ficha (cod_ficha, personal_cod_personal, cargo_cod_cargo, nomina_cod_nomina, ingreso, sueldo_basico) VALUES 
(1, 1, 1, 1, '2020-01-01', 5000.00),
(2, 2, 2, 1, '2022-03-15', 3000.00);
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO concepto_nomina (id_concepto, codigo, descripcion, tipo_concepto, formula_valor, es_porcentaje) VALUES 
(1, 'A001', 'Sueldo Básico', 'A', 100, true), -- 100% del sueldo base ficha
(2, 'A002', 'Bono Profesional', 'A', 500.00, false), -- 500 Fijo
(3, 'D001', 'Seguro Social Obligatorio (SSO)', 'D', 4, true), -- 4% del sueldo base
(4, 'D002', 'Fondo de Ahorro para la Vivienda (FAOV)', 'D', 1, true);
SQL
    );

    $pdo->exec(<<<SQL
-- Seed mock Administrador Data
INSERT INTO usuario (id_usuario, usuario, contrasenya, cedula_personal) VALUES 
(1, 'ADMINISTRADOR', 'e10adc3949ba59abbe56e057f20f883e', NULL), -- 123456
(2, 'JPAP', 'e10adc3949ba59abbe56e057f20f883e', 1);
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO anio_presupuestario (anio, estado) VALUES 
(2026, true);
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO beneficiario (id_beneficiario, cedula, nombres, apellidos, direccion, telefono) VALUES
(1, 'V-11111111', 'Carlos', 'Rodríguez', 'Av. Principal #12', '0414-1234567'),
(2, 'V-22222222', 'Ana',    'Martínez',  'Calle 5 Sur',       '0424-7654321');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO tipo_operacion_presupuesto (id_tipo_operacion_presupuesto, denominacion) VALUES
(1, 'Gasto Ordinario'),
(2, 'Inversión'),
(3, 'Transferencias');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO unidad_administrativa (id_unidad_administrativa, codigo, denominacion) VALUES
(3, 'RRHH', 'Recursos Humanos');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO fuente_financiamiento (id_fuente_financiamiento, denominacion) VALUES
(1, 'Recursos Ordinarios'),
(2, 'Situado Constitucional'),
(3, 'Ingresos Propios'),
(4, 'FONDEN');
SQL
    );

    $pdo->exec(<<<SQL
INSERT INTO presupuesto_ingresos_ramo (codigo_ramo, denominacion_ramo) VALUES
('3.01', 'Ingresos Ordinarios'),
('3.02', 'Ingresos Extraordinarios'),
('3.03', 'Ingresos de Operación'),
('3.04', 'Transferencias y Donaciones') ON CONFLICT DO NOTHING;
SQL
    );

    $pdo->commit();
    echo "Datos maestros insertados correctamente.\n";
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}
