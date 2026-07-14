-- Migración: personal
-- Generada: 2026-07-13 01:39:34

-- NÓMINA (Recursos Humanos)
CREATE TABLE IF NOT EXISTS personal (
    cod_personal SERIAL PRIMARY KEY,
    cedula TEXT,
    nombres TEXT,
    apellidos TEXT,
    fecha_nacimiento TEXT,
    eliminado BOOLEAN DEFAULT false
);
