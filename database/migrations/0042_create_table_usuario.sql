-- Migración: usuario
-- Generada: 2026-07-13 01:39:34

-- 1% del sueldo base

-- ADMINISTRADOR (Configuración Global)
CREATE TABLE IF NOT EXISTS usuario (
    id_usuario SERIAL PRIMARY KEY,
    usuario TEXT,
    contrasenya TEXT,
    cedula_personal INTEGER,
    eliminado BOOLEAN DEFAULT false
);
