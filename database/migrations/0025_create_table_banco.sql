-- Migración: banco
-- Generada: 2026-07-13 01:39:34

-- BANCO Y TESORERÍA
CREATE TABLE IF NOT EXISTS banco (
    id_banco SERIAL PRIMARY KEY,
    nombre_banco TEXT,
    eliminado BOOLEAN DEFAULT false
);
