-- Migración: inventario_insumos
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS inventario_insumos (

    id_inventario_insumos SERIAL PRIMARY KEY,
    id_articulo INTEGER,
    fecha_modificacion_ii TEXT,
    cantidad_ii REAL DEFAULT 0,
    minimo_ii REAL DEFAULT 0,
    id_orden_de_compra INTEGER,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_articulo) REFERENCES articulo(id_articulo) ON DELETE SET NULL,
    FOREIGN KEY (id_orden_de_compra) REFERENCES orden_de_compra(id_orden_de_compra) ON DELETE SET NULL
);
