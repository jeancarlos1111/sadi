-- Migración: pac
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS pac (

    id_pac SERIAL PRIMARY KEY,
    id_proyecto INTEGER,
    id_accion_centralizada INTEGER,
    id_articulo INTEGER NOT NULL,
    cantidad_anual REAL DEFAULT 0,
    trim_1 REAL DEFAULT 0,
    trim_2 REAL DEFAULT 0,
    trim_3 REAL DEFAULT 0,
    trim_4 REAL DEFAULT 0,
    costo_estimado REAL DEFAULT 0,
    estatus TEXT DEFAULT 'PLANIFICADO',
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_articulo) REFERENCES articulo(id_articulo) ON DELETE SET NULL
);
