-- Migración: cargo
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS cargo (
    cod_cargo SERIAL PRIMARY KEY,
    nombre TEXT,
    eliminado BOOLEAN DEFAULT false
);
