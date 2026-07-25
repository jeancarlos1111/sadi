-- Migración: create_table_acta_recepcion
-- Fase 4: Inventario Permanente (Recepción)

CREATE TABLE IF NOT EXISTS acta_recepcion (
    id_acta_recepcion SERIAL PRIMARY KEY,
    numero_acta TEXT UNIQUE NOT NULL,
    fecha_recepcion DATE NOT NULL DEFAULT CURRENT_DATE,
    id_orden_de_compra INTEGER NOT NULL REFERENCES orden_de_compra(id_orden_de_compra),
    id_usuario_receptor INTEGER NOT NULL REFERENCES usuario(id_usuario),
    conformidad BOOLEAN DEFAULT true,
    observaciones TEXT,
    eliminado BOOLEAN DEFAULT false
);

CREATE TABLE IF NOT EXISTS acta_recepcion_detalle (
    id_acta_recepcion_detalle SERIAL PRIMARY KEY,
    id_acta_recepcion INTEGER NOT NULL REFERENCES acta_recepcion(id_acta_recepcion) ON DELETE CASCADE,
    id_articulo INTEGER NOT NULL REFERENCES articulo(id_articulo),
    cantidad_recibida NUMERIC(15,2) NOT NULL,
    estado_fisico TEXT DEFAULT 'NUEVO'
);
