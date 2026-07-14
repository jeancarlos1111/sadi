-- Migración: servicio
-- Generada: 2026-07-13 01:39:34

-- CATÁLOGO DE SERVICIOS
CREATE TABLE IF NOT EXISTS servicio (

    id_servicio      SERIAL PRIMARY KEY,
    denominacion     TEXT,
    descripcion      TEXT,
    id_tipo_servicio INTEGER,
    id_codigo_plan_unico INTEGER,
    aplicar_iva      BOOLEAN DEFAULT false,
    eliminado        BOOLEAN DEFAULT false,
    FOREIGN KEY (id_tipo_servicio) REFERENCES tipo_servicio(id_tipo_servicio) ON DELETE SET NULL
);
