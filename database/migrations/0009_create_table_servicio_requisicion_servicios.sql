-- Migración: servicio_requisicion_servicios
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS servicio_requisicion_servicios (
    id_requisicion_servicios INTEGER,
    id_servicio INTEGER,
    cantidad_srs REAL,
    FOREIGN KEY(id_requisicion_servicios) REFERENCES requisicion_servicios(id_requisicion_servicios)
);
