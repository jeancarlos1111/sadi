-- Migración: indicador_proyecto
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS indicador_proyecto (
    id_indicador_proyecto SERIAL PRIMARY KEY,
    id_proyecto INTEGER NOT NULL,
    indicador_eficacia TEXT,
    indicador_eficiencia TEXT,
    indicador_calidad TEXT,
    indicador_impacto TEXT,
    medio_verificacion TEXT,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY(id_proyecto) REFERENCES proyecto(id_proyecto)
);
