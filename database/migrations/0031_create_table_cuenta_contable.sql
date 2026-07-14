-- Migración: cuenta_contable
-- Generada: 2026-07-13 01:39:34

-- CONTABILIDAD
CREATE TABLE IF NOT EXISTS cuenta_contable (
    id_cuenta_contable SERIAL PRIMARY KEY,
    codigo_cuenta TEXT,
    denominacion_cuenta TEXT,
    tipo_cuenta TEXT, -- ACTIVO, PASIVO, PATRIMONIO, INGRESO, EGRESO
    eliminado BOOLEAN DEFAULT false
);
