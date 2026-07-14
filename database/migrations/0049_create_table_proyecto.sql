-- Migración: proyecto
-- Generada: 2026-07-13 01:39:34

-- FASE 5: PROYECTOS Y ACCIONES CENTRALIZADAS (CON METAS INTEGRADAS TIPO SIGAFS)
CREATE TABLE IF NOT EXISTS proyecto (

    id_proyecto SERIAL PRIMARY KEY,
    codigo_proyecto TEXT NOT NULL,
    denominacion TEXT NOT NULL,
    unidad_medida TEXT,
    anio_inicio TEXT,
    anio_culm TEXT,
    cant_programada_trim_i REAL DEFAULT 0,
    cant_ejecutada_trim_i REAL DEFAULT 0,
    cant_programada_trim_ii REAL DEFAULT 0,
    cant_ejecutada_trim_ii REAL DEFAULT 0,
    cant_programada_trim_iii REAL DEFAULT 0,
    cant_ejecutada_trim_iii REAL DEFAULT 0,
    cant_programada_trim_iv REAL DEFAULT 0,
    cant_ejecutada_trim_iv REAL DEFAULT 0,
    indicador_eficacia TEXT,
    indicador_eficiencia TEXT,
    indicador_calidad TEXT,
    indicador_impacto TEXT,
    medio_verificacion TEXT,
    id_unidad_administrativa INTEGER,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_unidad_administrativa) REFERENCES unidad_administrativa(id_unidad_administrativa) ON DELETE SET NULL
);
