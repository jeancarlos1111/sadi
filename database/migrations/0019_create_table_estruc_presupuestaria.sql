-- Migración: estruc_presupuestaria
-- Generada: 2026-07-13 01:39:34

-- PRESUPUESTO
CREATE TABLE IF NOT EXISTS estruc_presupuestaria (
    id_estruc_presupuestaria SERIAL PRIMARY KEY,
    id_acciones_centralizadas INTEGER,
    id_accion_especifica INTEGER,
    id_otras_acciones_especificas INTEGER,
    descripcion_ep TEXT,
    eliminado BOOLEAN DEFAULT false
);
