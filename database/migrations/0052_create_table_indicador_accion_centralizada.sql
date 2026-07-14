-- Migración: indicador_accion_centralizada
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS indicador_accion_centralizada (
    id_indicador_ac SERIAL PRIMARY KEY,
    id_accion_centralizada INTEGER NOT NULL,
    indicador_eficacia TEXT,
    indicador_eficiencia TEXT,
    indicador_calidad TEXT,
    indicador_impacto TEXT,
    medio_verificacion TEXT,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY(id_accion_centralizada) REFERENCES accion_centralizada(id_accion_centralizada)
);
