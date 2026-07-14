-- Migración: ficha
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS ficha (
    cod_ficha SERIAL PRIMARY KEY,
    personal_cod_personal INTEGER,
    cargo_cod_cargo INTEGER,
    nomina_cod_nomina INTEGER,
    ingreso TEXT,
    sueldo_basico REAL DEFAULT 0,
    eliminado BOOLEAN DEFAULT false
);
