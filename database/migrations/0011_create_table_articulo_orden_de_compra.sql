-- Migración: articulo_orden_de_compra
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS articulo_orden_de_compra (
    id_orden_de_compra INTEGER,
    id_articulo INTEGER,
    cantidad_aodc REAL,
    costo_aodc REAL,
    porcentaje_descuento_aodc REAL DEFAULT 0,
    descuento_aodc REAL DEFAULT 0,
    aplica_iva BOOLEAN DEFAULT false,
    FOREIGN KEY(id_orden_de_compra) REFERENCES orden_de_compra(id_orden_de_compra),
    FOREIGN KEY(id_articulo) REFERENCES articulo(id_articulo)
);
