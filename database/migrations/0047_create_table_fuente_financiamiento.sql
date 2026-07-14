-- Migración: fuente_financiamiento
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS fuente_financiamiento (
    id_fuente_financiamiento SERIAL PRIMARY KEY,
    denominacion TEXT NOT NULL,
    eliminado BOOLEAN DEFAULT false
);
