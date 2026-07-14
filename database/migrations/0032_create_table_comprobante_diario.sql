-- Migración: comprobante_diario
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS comprobante_diario (
    id_comprobante_diario SERIAL PRIMARY KEY,
    numero_comprobante TEXT,
    fecha_comprobante TEXT,
    concepto TEXT,
    eliminado BOOLEAN DEFAULT false
);
