-- Migración: articulo_requisicion_bienes
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS articulo_requisicion_bienes (
    id_requisicion_bienes INTEGER,
    id_articulo INTEGER,
    cantidad_arb REAL,
    FOREIGN KEY(id_requisicion_bienes) REFERENCES requisicion_bienes(id_requisicion_bienes),
    FOREIGN KEY(id_articulo) REFERENCES articulo(id_articulo)
);
