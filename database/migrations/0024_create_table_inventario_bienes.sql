-- Migración: inventario_bienes
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS inventario_bienes (

    id_inventario_bienes SERIAL PRIMARY KEY,
    id_articulo INTEGER,
    id_proveedor INTEGER,
    fecha_compra_ib TEXT,
    id_orden_de_compra INTEGER,
    costo_ib REAL DEFAULT 0,
    id_estado_bienes INTEGER,
    id_ubicacion_articulo INTEGER,
    acronimo_id_ib TEXT,
    revisado BOOLEAN DEFAULT false,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_articulo) REFERENCES articulo(id_articulo) ON DELETE SET NULL,
    FOREIGN KEY (id_proveedor) REFERENCES proveedor(id_proveedor) ON DELETE SET NULL,
    FOREIGN KEY (id_orden_de_compra) REFERENCES orden_de_compra(id_orden_de_compra) ON DELETE SET NULL,
    FOREIGN KEY (id_ubicacion_articulo) REFERENCES ubicacion_articulo(id_ubicacion_articulo) ON DELETE SET NULL
);
