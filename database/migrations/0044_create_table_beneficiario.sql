-- Migración: beneficiario
-- Generada: 2026-07-13 01:39:34

-- ============================================================
-- FASE 1: ENTIDADES BASE (2026-02-28)
-- ============================================================

-- BENEFICIARIOS (personas naturales/jurídicas que reciben pagos)
CREATE TABLE IF NOT EXISTS beneficiario (
    id_beneficiario SERIAL PRIMARY KEY,
    cedula          TEXT UNIQUE,
    nombres         TEXT,
    apellidos       TEXT,
    direccion       TEXT,
    telefono        TEXT,
    email           TEXT,
    id_codigo_contable INTEGER,
    eliminado       BOOLEAN DEFAULT false
);
