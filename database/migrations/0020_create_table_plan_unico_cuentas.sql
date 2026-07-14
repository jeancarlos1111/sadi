-- Migración: plan_unico_cuentas
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS plan_unico_cuentas (
    id_codigo_plan_unico SERIAL PRIMARY KEY,
    codigo_plan_unico TEXT,
    denominacion TEXT,
    eliminado BOOLEAN DEFAULT false
);
