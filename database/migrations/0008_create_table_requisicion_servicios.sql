-- Migración: requisicion_servicios
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS requisicion_servicios (
    id_requisicion_servicios SERIAL PRIMARY KEY,
    fecha_rs DATE,
    concepto_rs TEXT,
    id_estructura_presupuestaria INTEGER,
    eliminado BOOLEAN DEFAULT false
);
