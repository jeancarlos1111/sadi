-- Migración: nomina
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS nomina (
    cod_nomina SERIAL PRIMARY KEY,
    denom TEXT,
    tipo_periodo TEXT,
    eliminado BOOLEAN DEFAULT false
);
