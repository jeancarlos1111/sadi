-- Migración: servicio_orden_de_servicio
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS servicio_orden_de_servicio (
    id_orden_de_servicio INTEGER,
    id_servicio INTEGER,
    cantidad_sods REAL,
    costo_sods REAL,
    aplica_iva BOOLEAN DEFAULT false,
    FOREIGN KEY(id_orden_de_servicio) REFERENCES orden_de_servicio(id_orden_de_servicio),
    FOREIGN KEY(id_servicio) REFERENCES servicio(id_servicio)
);
