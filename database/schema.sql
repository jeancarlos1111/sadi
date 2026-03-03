-- SADI SQLite Schema for Development

-- We don't use schema prefixes here because SQLite will attach the same DB under different names.

CREATE TABLE IF NOT EXISTS proveedor (
    id_proveedor INTEGER PRIMARY KEY AUTOINCREMENT,
    rif_proveedor TEXT UNIQUE,
    nit_proveedor TEXT,
    compania_proveedor TEXT,
    id_tipo_organizacion INTEGER,
    direccion_proveedor TEXT,
    telefono_proveedor TEXT,
    id_codigo_contable INTEGER,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tipo_organizacion (
    id_tipo_organizacion INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre_tipo_organizacion TEXT,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tipo_de_articulo (
    id_tipo_de_articulo INTEGER PRIMARY KEY AUTOINCREMENT,
    denominacion_tda TEXT,
    descripcion_tda TEXT,
    tipo_tda INTEGER,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS unidades_de_medida (
    id_unidades_de_medida INTEGER PRIMARY KEY AUTOINCREMENT,
    denominacion_udm TEXT,
    unidades_udm TEXT,
    observacion_udm TEXT,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS articulo (
    id_articulo INTEGER PRIMARY KEY AUTOINCREMENT,
    denominacion_a TEXT,
    observacion_a TEXT,
    id_tipo_de_articulo INTEGER,
    id_unidades_de_medida INTEGER,
    id_codigo_plan_unico INTEGER,
    aplicar_iva BOOLEAN DEFAULT 0,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS requisicion_bienes (
    id_requisicion_bienes INTEGER PRIMARY KEY AUTOINCREMENT,
    fecha_rb TEXT,
    concepto_rb TEXT,
    id_estructura_presupuestaria INTEGER,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS articulo_requisicion_bienes (
    id_requisicion_bienes INTEGER,
    id_articulo INTEGER,
    cantidad_arb REAL,
    FOREIGN KEY(id_requisicion_bienes) REFERENCES requisicion_bienes(id_requisicion_bienes),
    FOREIGN KEY(id_articulo) REFERENCES articulo(id_articulo)
);

CREATE TABLE IF NOT EXISTS orden_de_compra (
    id_orden_de_compra INTEGER PRIMARY KEY AUTOINCREMENT,
    fecha_odc TEXT,
    concepto_odc TEXT,
    id_proveedor INTEGER,
    porcentaje_iva_odc REAL,
    monto_base_odc REAL,
    monto_iva_odc REAL,
    monto_total_odc REAL,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS articulo_orden_de_compra (
    id_orden_de_compra INTEGER,
    id_articulo INTEGER,
    cantidad_aodc REAL,
    costo_aodc REAL,
    porcentaje_descuento_aodc REAL DEFAULT 0,
    descuento_aodc REAL DEFAULT 0,
    aplica_iva BOOLEAN DEFAULT 0,
    FOREIGN KEY(id_orden_de_compra) REFERENCES orden_de_compra(id_orden_de_compra),
    FOREIGN KEY(id_articulo) REFERENCES articulo(id_articulo)
);

CREATE TABLE IF NOT EXISTS tipo_documento (
    id_tipo_documento INTEGER PRIMARY KEY AUTOINCREMENT,
    denominacion_tipo_documento TEXT,
    afecta_presupuesto_tipo_documento BOOLEAN,
    siglas_tipo_documento TEXT,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS documento (
    id_documento INTEGER PRIMARY KEY AUTOINCREMENT,
    id_orden_de_compra INTEGER, 
    nro_documento_d TEXT,
    nro_control_d TEXT,
    fecha_emision_d TEXT,
    fecha_vencimiento_d TEXT,
    id_proveedor INTEGER,
    id_tipo_documento INTEGER,
    monto_base_d REAL,
    monto_impuesto_d REAL,
    monto_total_d REAL,
    observacion_d TEXT,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS solicitud_pago (
    id_solicitud_pago INTEGER PRIMARY KEY AUTOINCREMENT,
    fecha_solicitud_pago TEXT,
    concepto_solicitud_pago TEXT,
    monto_pagar_solicitud_pago REAL,
    id_documento INTEGER,
    contabilizada BOOLEAN DEFAULT 0,
    eliminado BOOLEAN DEFAULT 0
);

-- Seed basic data
INSERT OR IGNORE INTO tipo_organizacion (id_tipo_organizacion, nombre_tipo_organizacion) VALUES 
(1, 'Firma Personal'),
(2, 'Compania Anonima (C.A.)'),
(3, 'Sociedad Anonima (S.A.)');

INSERT OR IGNORE INTO tipo_de_articulo (id_tipo_de_articulo, denominacion_tda, tipo_tda) VALUES 
(1, 'Material de Oficina', 1),
(2, 'Equipos de Computación', 1);

INSERT OR IGNORE INTO unidades_de_medida (id_unidades_de_medida, denominacion_udm, unidades_udm) VALUES 
(1, 'Unidad', 'UND'),
(2, 'Caja', 'CAJ'),
(3, 'Resma', 'RES');

INSERT OR IGNORE INTO proveedor (id_proveedor, rif_proveedor, compania_proveedor, id_tipo_organizacion, direccion_proveedor, telefono_proveedor) VALUES 
(1, 'J-12345678-9', 'Insumos CA', 2, 'Centro de la ciudad', '0212-5555555'),
(2, 'V-87654321-0', 'Servicios Juan', 1, 'Calle Principal', '0414-0001122');

INSERT OR IGNORE INTO articulo (id_articulo, denominacion_a, id_tipo_de_articulo, id_unidades_de_medida, aplicar_iva) VALUES 
(1, 'Lápices Grafito', 1, 2, 1),
(2, 'Resma Papel Carta', 1, 3, 1),
(3, 'Monitor 24 pulgadas', 2, 1, 1);

INSERT OR IGNORE INTO tipo_documento (id_tipo_documento, denominacion_tipo_documento, afecta_presupuesto_tipo_documento, siglas_tipo_documento) VALUES 
(1, 'Factura', 1, 'FAC'),
(2, 'Nota de Entrega', 0, 'NE');

-- PRESUPUESTO
CREATE TABLE IF NOT EXISTS estruc_presupuestaria (
    id_estruc_presupuestaria INTEGER PRIMARY KEY AUTOINCREMENT,
    id_acciones_centralizadas INTEGER,
    id_accion_especifica INTEGER,
    id_otras_acciones_especificas INTEGER,
    descripcion_ep TEXT,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS plan_unico_cuentas (
    id_codigo_plan_unico INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo_plan_unico TEXT,
    denominacion TEXT,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS presupuesto_gastos (
    id_presupuesto_gastos INTEGER PRIMARY KEY AUTOINCREMENT,
    id_estruc_presupuestaria INTEGER,
    id_codigo_plan_unico INTEGER,
    monto_asignado REAL DEFAULT 0,
    monto_comprometido REAL DEFAULT 0,
    monto_causado REAL DEFAULT 0,
    monto_pagado REAL DEFAULT 0,
    eliminado BOOLEAN DEFAULT 0
);

-- Seed mock Presupuesto Data
INSERT OR IGNORE INTO estruc_presupuestaria (id_estruc_presupuestaria, descripcion_ep) VALUES 
(1, 'Administración Central - Recursos Humanos'),
(2, 'Tecnología e Informática');

INSERT OR IGNORE INTO plan_unico_cuentas (id_codigo_plan_unico, codigo_plan_unico, denominacion) VALUES 
(1, '401.01', 'Sueldos Básicos Personal Fijo'),
(2, '402.03', 'Materiales y Útiles de Oficina'),
(3, '404.04', 'Equipos de Computación');

INSERT OR IGNORE INTO presupuesto_gastos (id_presupuesto_gastos, id_estruc_presupuestaria, id_codigo_plan_unico, monto_asignado) VALUES 
(1, 1, 1, 5000000.00),
(2, 1, 2, 50000.00),
(3, 2, 3, 120000.00);

-- INVENTARIO (ALMACÉN)
CREATE TABLE IF NOT EXISTS ubicacion_articulo (
    id_ubicacion_articulo INTEGER PRIMARY KEY AUTOINCREMENT,
    denominacion_ua TEXT,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS inventario_insumos (
    id_inventario_insumos INTEGER PRIMARY KEY AUTOINCREMENT,
    id_articulo INTEGER,
    fecha_modificacion_ii TEXT,
    cantidad_ii REAL DEFAULT 0,
    minimo_ii REAL DEFAULT 0,
    id_orden_de_compra INTEGER,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS inventario_bienes (
    id_inventario_bienes INTEGER PRIMARY KEY AUTOINCREMENT,
    id_articulo INTEGER,
    id_proveedor INTEGER,
    fecha_compra_ib TEXT,
    id_orden_de_compra INTEGER,
    costo_ib REAL DEFAULT 0,
    id_estado_bienes INTEGER,
    id_ubicacion_articulo INTEGER,
    acronimo_id_ib TEXT,
    revisado BOOLEAN DEFAULT 0,
    eliminado BOOLEAN DEFAULT 0
);

-- Seed mock Inventario Data
INSERT OR IGNORE INTO ubicacion_articulo (id_ubicacion_articulo, denominacion_ua) VALUES 
(1, 'Almacén Principal (Sede)'),
(2, 'Depósito Auxiliar');

INSERT OR IGNORE INTO inventario_insumos (id_inventario_insumos, id_articulo, cantidad_ii) VALUES 
(1, 1, 1500), -- 1500 Lápices en Principal
(2, 2, 50); -- 50 Resmas en Principal

INSERT OR IGNORE INTO inventario_bienes (id_inventario_bienes, id_articulo, id_proveedor, id_ubicacion_articulo, acronimo_id_ib) VALUES
(1, 3, 1, 1, 'SADI-BIEN-001');

-- BANCO Y TESORERÍA
CREATE TABLE IF NOT EXISTS banco (
    id_banco INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre_banco TEXT,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS cta_bancaria (
    id_cta_bancaria INTEGER PRIMARY KEY AUTOINCREMENT,
    id_banco INTEGER,
    numero_cta_bancaria TEXT,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tipo_operacion_bancaria (
    id_tipo_operacion_bancaria INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre_tipo_operacion_bancaria TEXT,
    acronimo_tipo_operacion_bancaria TEXT,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS movimiento_bancario (
    id_movimiento_bancario INTEGER PRIMARY KEY AUTOINCREMENT,
    id_cta_bancaria INTEGER,
    id_tipo_operacion_bancaria INTEGER,
    monto REAL DEFAULT 0,
    fecha TEXT,
    referencia TEXT,
    eliminado BOOLEAN DEFAULT 0
);

-- Seed mock Banco Data
INSERT OR IGNORE INTO banco (id_banco, nombre_banco) VALUES 
(1, 'Banco de Venezuela'),
(2, 'Banco Banesco');

INSERT OR IGNORE INTO cta_bancaria (id_cta_bancaria, id_banco, numero_cta_bancaria) VALUES 
(1, 1, '0102-0000-0000-0000-1234'),
(2, 2, '0134-1111-2222-3333-4567');

INSERT OR IGNORE INTO tipo_operacion_bancaria (id_tipo_operacion_bancaria, nombre_tipo_operacion_bancaria, acronimo_tipo_operacion_bancaria) VALUES 
(1, 'Depósito', 'DP'),
(2, 'Cheque', 'CH'),
(3, 'Transferencia', 'TR'),
(4, 'Nota de Débito', 'ND');

INSERT OR IGNORE INTO movimiento_bancario (id_movimiento_bancario, id_cta_bancaria, id_tipo_operacion_bancaria, monto, fecha, referencia) VALUES 
(1, 1, 1, 500000.00, '2026-02-01', 'DEP-9991'),
(2, 1, 3, -15000.00, '2026-02-05', 'TR-8882');

-- RETENCIONES E IMPUESTOS
CREATE TABLE IF NOT EXISTS factura (
    id_factura INTEGER PRIMARY KEY AUTOINCREMENT,
    id_proveedor INTEGER,
    numero_factura TEXT,
    fecha_factura TEXT,
    monto_base REAL DEFAULT 0,
    monto_impuesto REAL DEFAULT 0,
    monto_total REAL DEFAULT 0,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS comprobante_retencion (
    id_comprobante_retencion INTEGER PRIMARY KEY AUTOINCREMENT,
    id_factura INTEGER,
    tipo_retencion TEXT, -- IVA, ISLR, 1X1000
    numero_comprobante TEXT,
    porcentaje REAL DEFAULT 0,
    monto_retenido REAL DEFAULT 0,
    fecha_emision TEXT,
    eliminado BOOLEAN DEFAULT 0
);

-- Seed mock Retenciones Data
INSERT OR IGNORE INTO factura (id_factura, id_proveedor, numero_factura, fecha_factura, monto_base, monto_impuesto, monto_total) VALUES 
(1, 1, 'F-000192', '2026-02-10', 1000.00, 160.00, 1160.00),
(2, 2, 'F-558291', '2026-02-15', 5000.00, 800.00, 5800.00);

INSERT OR IGNORE INTO comprobante_retencion (id_comprobante_retencion, id_factura, tipo_retencion, numero_comprobante, porcentaje, monto_retenido, fecha_emision) VALUES 
(1, 1, 'IVA', '202602-00000001', 75.00, 120.00, '2026-02-12'),
(2, 1, 'ISLR', '202602-00000002', 2.00, 20.00, '2026-02-12'),
(3, 2, 'IVA', '202602-00000003', 100.00, 800.00, '2026-02-16');

-- CONTABILIDAD
CREATE TABLE IF NOT EXISTS cuenta_contable (
    id_cuenta_contable INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo_cuenta TEXT,
    denominacion_cuenta TEXT,
    tipo_cuenta TEXT, -- ACTIVO, PASIVO, PATRIMONIO, INGRESO, EGRESO
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS comprobante_diario (
    id_comprobante_diario INTEGER PRIMARY KEY AUTOINCREMENT,
    numero_comprobante TEXT,
    fecha_comprobante TEXT,
    concepto TEXT,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS movimiento_contable (
    id_movimiento_contable INTEGER PRIMARY KEY AUTOINCREMENT,
    id_comprobante_diario INTEGER,
    id_cuenta_contable INTEGER,
    tipo_operacion_mc TEXT, -- D (Debe), H (Haber)
    monto_mc REAL DEFAULT 0,
    eliminado BOOLEAN DEFAULT 0
);

-- Seed mock Contabilidad Data
INSERT OR IGNORE INTO cuenta_contable (id_cuenta_contable, codigo_cuenta, denominacion_cuenta, tipo_cuenta) VALUES 
(1, '1.1.01.01', 'Caja Principal', 'ACTIVO'),
(2, '1.1.02.01', 'Bancos Nacionales', 'ACTIVO'),
(3, '2.1.01.01', 'Cuentas por Pagar Proveedores', 'PASIVO'),
(4, '5.1.01.01', 'Gastos de Personal', 'EGRESO');

INSERT OR IGNORE INTO comprobante_diario (id_comprobante_diario, numero_comprobante, fecha_comprobante, concepto) VALUES 
(1, 'CD-0001', '2026-02-01', 'Apertura de Ejercicio Fiscal'),
(2, 'CD-0002', '2026-02-05', 'Registro de Pago a Proveedores');

INSERT OR IGNORE INTO movimiento_contable (id_movimiento_contable, id_comprobante_diario, id_cuenta_contable, tipo_operacion_mc, monto_mc) VALUES 
(1, 1, 2, 'D', 1000000.00),
(2, 1, 3, 'H', 1000000.00),
(3, 2, 3, 'D', 50000.00),
(4, 2, 2, 'H', 50000.00);

-- NÓMINA (Recursos Humanos)
CREATE TABLE IF NOT EXISTS personal (
    cod_personal INTEGER PRIMARY KEY AUTOINCREMENT,
    cedula TEXT,
    nombres TEXT,
    apellidos TEXT,
    fecha_nacimiento TEXT,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS cargo (
    cod_cargo INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS nomina (
    cod_nomina INTEGER PRIMARY KEY AUTOINCREMENT,
    denom TEXT,
    tipo_periodo TEXT,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS ficha (
    cod_ficha INTEGER PRIMARY KEY AUTOINCREMENT,
    personal_cod_personal INTEGER,
    cargo_cod_cargo INTEGER,
    nomina_cod_nomina INTEGER,
    ingreso TEXT,
    sueldo_basico REAL DEFAULT 0,
    eliminado BOOLEAN DEFAULT 0
);

-- TABLAS TRANSACCIONALES DE NÓMINA

CREATE TABLE IF NOT EXISTS concepto_nomina (
    id_concepto INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo TEXT,
    descripcion TEXT,
    tipo_concepto TEXT, -- 'A' Asignacion, 'D' Deduccion
    formula_valor REAL, -- Puede ser un monto fijo o un porcentaje. Para simplicidad de MVP usaremos montos fijos o un flag de porcentaje.
    es_porcentaje BOOLEAN DEFAULT 0, -- Si es 1, formula_valor es % sobre sueldo basico
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS planilla_nomina (
    id_planilla INTEGER PRIMARY KEY AUTOINCREMENT,
    nomina_cod_nomina INTEGER,
    fecha_emision TEXT,
    periodo TEXT, -- Ej: '1era Quincena Enero 2026'
    monto_total_asignaciones REAL DEFAULT 0,
    monto_total_deducciones REAL DEFAULT 0,
    monto_total_neto REAL DEFAULT 0,
    contabilizada BOOLEAN DEFAULT 0, -- Si ya generó Solicitud de Pago
    FOREIGN KEY(nomina_cod_nomina) REFERENCES nomina(cod_nomina)
);

CREATE TABLE IF NOT EXISTS detalle_planilla_nomina (
    id_detalle_planilla INTEGER PRIMARY KEY AUTOINCREMENT,
    id_planilla INTEGER,
    cod_ficha INTEGER,
    neto_trabajador REAL DEFAULT 0,
    FOREIGN KEY(id_planilla) REFERENCES planilla_nomina(id_planilla),
    FOREIGN KEY(cod_ficha) REFERENCES ficha(cod_ficha)
);

CREATE TABLE IF NOT EXISTS detalle_recibo_concepto (
    id_detalle_recibo INTEGER PRIMARY KEY AUTOINCREMENT,
    id_detalle_planilla INTEGER,
    id_concepto INTEGER,
    monto_calculado REAL DEFAULT 0,
    FOREIGN KEY(id_detalle_planilla) REFERENCES detalle_planilla_nomina(id_detalle_planilla),
    FOREIGN KEY(id_concepto) REFERENCES concepto_nomina(id_concepto)
);

-- Seed mock Nomina Data
INSERT OR IGNORE INTO personal (cod_personal, cedula, nombres, apellidos, fecha_nacimiento) VALUES 
(1, 'V-12345678', 'Juan', 'Pérez', '1980-05-15'),
(2, 'V-87654321', 'María', 'Gómez', '1992-10-20');

INSERT OR IGNORE INTO cargo (cod_cargo, nombre) VALUES 
(1, 'Director General'),
(2, 'Analista de Sistemas');

INSERT OR IGNORE INTO nomina (cod_nomina, denom, tipo_periodo) VALUES 
(1, 'Nómina Empleados Fijos', 'Mensual'),
(2, 'Nómina Obreros', 'Semanal');

INSERT OR IGNORE INTO ficha (cod_ficha, personal_cod_personal, cargo_cod_cargo, nomina_cod_nomina, ingreso, sueldo_basico) VALUES 
(1, 1, 1, 1, '2020-01-01', 5000.00),
(2, 2, 2, 1, '2022-03-15', 3000.00);

INSERT OR IGNORE INTO concepto_nomina (id_concepto, codigo, descripcion, tipo_concepto, formula_valor, es_porcentaje) VALUES 
(1, 'A001', 'Sueldo Básico', 'A', 100, 1), -- 100% del sueldo base ficha
(2, 'A002', 'Bono Profesional', 'A', 500.00, 0), -- 500 Fijo
(3, 'D001', 'Seguro Social Obligatorio (SSO)', 'D', 4, 1), -- 4% del sueldo base
(4, 'D002', 'Fondo de Ahorro para la Vivienda (FAOV)', 'D', 1, 1); -- 1% del sueldo base

-- ADMINISTRADOR (Configuración Global)
CREATE TABLE IF NOT EXISTS usuario (
    id_usuario INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario TEXT,
    contrasenya TEXT,
    cedula_personal INTEGER,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS anio_presupuestario (
    anio INTEGER PRIMARY KEY,
    estado BOOLEAN DEFAULT 1
);

-- Seed mock Administrador Data
INSERT OR IGNORE INTO usuario (id_usuario, usuario, contrasenya, cedula_personal) VALUES 
(1, 'ADMINISTRADOR', 'e10adc3949ba59abbe56e057f20f883e', NULL), -- 123456
(2, 'JPAP', 'e10adc3949ba59abbe56e057f20f883e', 1);

INSERT OR IGNORE INTO anio_presupuestario (anio, estado) VALUES 
(2026, 1);

-- ============================================================
-- FASE 1: ENTIDADES BASE (2026-02-28)
-- ============================================================

-- BENEFICIARIOS (personas naturales/jurídicas que reciben pagos)
CREATE TABLE IF NOT EXISTS beneficiario (
    id_beneficiario INTEGER PRIMARY KEY AUTOINCREMENT,
    cedula          TEXT UNIQUE,
    nombres         TEXT,
    apellidos       TEXT,
    direccion       TEXT,
    telefono        TEXT,
    email           TEXT,
    id_codigo_contable INTEGER,
    eliminado       BOOLEAN DEFAULT 0
);

INSERT OR IGNORE INTO beneficiario (id_beneficiario, cedula, nombres, apellidos, direccion, telefono) VALUES
(1, 'V-11111111', 'Carlos', 'Rodríguez', 'Av. Principal #12', '0414-1234567'),
(2, 'V-22222222', 'Ana',    'Martínez',  'Calle 5 Sur',       '0424-7654321');

-- TIPOS DE SERVICIO
CREATE TABLE IF NOT EXISTS tipo_servicio (
    id_tipo_servicio INTEGER PRIMARY KEY AUTOINCREMENT,
    denominacion     TEXT,
    descripcion      TEXT,
    eliminado        BOOLEAN DEFAULT 0
);

INSERT OR IGNORE INTO tipo_servicio (id_tipo_servicio, denominacion) VALUES
(1, 'Servicios Profesionales'),
(2, 'Mantenimiento y Reparación'),
(3, 'Servicios de Transporte');

-- CATÁLOGO DE SERVICIOS
CREATE TABLE IF NOT EXISTS servicio (
    id_servicio      INTEGER PRIMARY KEY AUTOINCREMENT,
    denominacion     TEXT,
    descripcion      TEXT,
    id_tipo_servicio INTEGER,
    aplicar_iva      BOOLEAN DEFAULT 0,
    eliminado        BOOLEAN DEFAULT 0
);

INSERT OR IGNORE INTO servicio (id_servicio, denominacion, id_tipo_servicio, aplicar_iva) VALUES
(1, 'Consultoría Técnica',         1, 1),
(2, 'Mantenimiento de Equipos',    2, 1),
(3, 'Transporte de Correspondencia', 3, 0);

-- TIPOS DE OPERACIÓN PRESUPUESTARIA
CREATE TABLE IF NOT EXISTS tipo_operacion_presupuesto (
    id_tipo_operacion_presupuesto INTEGER PRIMARY KEY AUTOINCREMENT,
    denominacion TEXT,
    descripcion  TEXT,
    eliminado    BOOLEAN DEFAULT 0
);

INSERT OR IGNORE INTO tipo_operacion_presupuesto (id_tipo_operacion_presupuesto, denominacion) VALUES
(1, 'Gasto Ordinario'),
(2, 'Inversión'),
(3, 'Transferencias');

-- UNIDADES ADMINISTRATIVAS
CREATE TABLE IF NOT EXISTS unidad_administrativa (
    id_unidad_administrativa INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo       TEXT,
    denominacion TEXT,
    eliminado    BOOLEAN DEFAULT 0
);

INSERT OR IGNORE INTO unidad_administrativa (id_unidad_administrativa, codigo, denominacion) VALUES
(3, 'RRHH', 'Recursos Humanos');

-- FASE 5: PROYECTOS Y ACCIONES CENTRALIZADAS (CON METAS INTEGRADAS TIPO SIGAFS)
CREATE TABLE IF NOT EXISTS proyecto (
    id_proyecto INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo_proyecto TEXT NOT NULL,
    denominacion TEXT NOT NULL,
    unidad_medida TEXT,
    anio_inicio TEXT,
    anio_culm TEXT,
    cant_programada_trim_i REAL DEFAULT 0,
    cant_ejecutada_trim_i REAL DEFAULT 0,
    cant_programada_trim_ii REAL DEFAULT 0,
    cant_ejecutada_trim_ii REAL DEFAULT 0,
    cant_programada_trim_iii REAL DEFAULT 0,
    cant_ejecutada_trim_iii REAL DEFAULT 0,
    cant_programada_trim_iv REAL DEFAULT 0,
    cant_ejecutada_trim_iv REAL DEFAULT 0,
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS accion_centralizada (
    id_accion_centralizada INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo_accion_centralizada TEXT NOT NULL,
    denominacion TEXT NOT NULL,
    unidad_medida TEXT,
    anio_inicio TEXT,
    anio_culm TEXT,
    cant_programada_trim_i REAL DEFAULT 0,
    cant_ejecutada_trim_i REAL DEFAULT 0,
    cant_programada_trim_ii REAL DEFAULT 0,
    cant_ejecutada_trim_ii REAL DEFAULT 0,
    cant_programada_trim_iii REAL DEFAULT 0,
    cant_ejecutada_trim_iii REAL DEFAULT 0,
    cant_programada_trim_iv REAL DEFAULT 0,
    cant_ejecutada_trim_iv REAL DEFAULT 0,
    eliminado BOOLEAN DEFAULT 0
);

-- PROCESOS PRESUPUESTARIOS: COMPROBANTES Y MOVIMIENTOS
CREATE TABLE IF NOT EXISTS comprobante_presupuestario (
    id_comprobante INTEGER PRIMARY KEY AUTOINCREMENT,
    acronimo_c TEXT NOT NULL,          -- Ej: 'AAP' (Apertura), 'TR' (Traspaso), 'CA' (Crédito Adicional)
    numero_c TEXT NOT NULL,            -- Correlativo string
    fecha_c TEXT NOT NULL,
    denominacion_c TEXT NOT NULL,
    referencia_c TEXT,                 -- Reemplazo de referenci interna SIGAFS
    beneficiario_cedula TEXT,          -- Relacionado a pagos
    estado TEXT DEFAULT 'APROBADO',
    eliminado BOOLEAN DEFAULT 0
);

CREATE TABLE IF NOT EXISTS movimiento_presupuestario (
    id_movimiento_presupuestario INTEGER PRIMARY KEY AUTOINCREMENT,
    id_comprobante INTEGER NOT NULL,
    id_estruc_presupuestaria INTEGER NOT NULL,
    id_codigo_plan_unico INTEGER NOT NULL,
    id_operacion TEXT NOT NULL,        -- (ej. 'AAP', 'CA', 'CG', 'PAG', 'TR') suma o resta
    monto_mp REAL DEFAULT 0,
    descripcion_mp TEXT,
    eliminado BOOLEAN DEFAULT 0,
    FOREIGN KEY(id_comprobante) REFERENCES comprobante_presupuestario(id_comprobante)
);

-- PERÍODOS PRESUPUESTARIOS (CIERRE / APERTURA DE MESES)
CREATE TABLE IF NOT EXISTS periodo_presupuestario (
    id_periodo INTEGER PRIMARY KEY AUTOINCREMENT,
    anio INTEGER NOT NULL,
    mes  INTEGER NOT NULL,         -- 1=Enero ... 12=Diciembre
    estado TEXT NOT NULL DEFAULT 'ABIERTO',  -- 'ABIERTO' o 'CERRADO'
    fecha_cierre TEXT,
    observacion TEXT,
    UNIQUE(anio, mes)
);

-- REFORMULACIÓN PRESUPUESTARIA
-- Guarda el monto reformulado por estructura/cuenta (análogo a la tabla 'reformulacion' de SIGAFS)
CREATE TABLE IF NOT EXISTS reformulacion (
    id_reformulacion INTEGER PRIMARY KEY AUTOINCREMENT,
    id_estruc_presupuestaria INTEGER NOT NULL,
    id_codigo_plan_unico INTEGER NOT NULL,
    monto_reformulado REAL NOT NULL DEFAULT 0,
    observacion TEXT,
    fecha_registro TEXT NOT NULL DEFAULT (date('now')),
    eliminado BOOLEAN DEFAULT 0,
    UNIQUE(id_estruc_presupuestaria, id_codigo_plan_unico)
);
