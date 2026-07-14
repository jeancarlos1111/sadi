-- Migración: requisicion_bienes
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS requisicion_bienes (
    id_requisicion_bienes SERIAL PRIMARY KEY,
    fecha_rb TEXT,
    concepto_rb TEXT,
    id_estructura_presupuestaria INTEGER,
    eliminado BOOLEAN DEFAULT false
);
