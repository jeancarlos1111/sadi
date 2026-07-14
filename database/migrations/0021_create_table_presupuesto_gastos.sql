-- Migración: presupuesto_gastos
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS presupuesto_gastos (

    id_presupuesto_gastos SERIAL PRIMARY KEY,
    id_estruc_presupuestaria INTEGER,
    id_codigo_plan_unico INTEGER,
    monto_asignado REAL DEFAULT 0,
    monto_comprometido REAL DEFAULT 0,
    monto_precomprometido REAL DEFAULT 0,
    monto_causado REAL DEFAULT 0,
    monto_pagado REAL DEFAULT 0,
    id_fuente_financiamiento INTEGER,
    id_unidad_administrativa INTEGER,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_estruc_presupuestaria) REFERENCES estruc_presupuestaria(id_estruc_presupuestaria) ON DELETE SET NULL,
    FOREIGN KEY (id_codigo_plan_unico) REFERENCES plan_unico_cuentas(id_codigo_plan_unico) ON DELETE SET NULL
);
