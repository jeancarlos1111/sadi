-- Migración: periodo_presupuestario
-- Generada: 2026-07-13 01:39:34

-- PERÍODOS PRESUPUESTARIOS (CIERRE / APERTURA DE MESES)
CREATE TABLE IF NOT EXISTS periodo_presupuestario (

    id_periodo SERIAL PRIMARY KEY,
    anio INTEGER NOT NULL,
    mes  INTEGER NOT NULL,         -- 1=Enero ... 12=Diciembre
    estado TEXT NOT NULL DEFAULT 'ABIERTO',  -- 'ABIERTO' o 'CERRADO'
    fecha_cierre TEXT,
    observacion TEXT,
    UNIQUE(anio, mes),
    FOREIGN KEY (anio) REFERENCES anio_presupuestario(anio) ON DELETE SET NULL
);
