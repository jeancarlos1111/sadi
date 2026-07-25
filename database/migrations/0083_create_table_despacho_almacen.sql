-- Migración: create_table_despacho_almacen
-- Fase 4: Inventario Permanente (Salidas/Despachos)

CREATE TABLE IF NOT EXISTS despacho_almacen (
    id_despacho_almacen SERIAL PRIMARY KEY,
    numero_despacho TEXT UNIQUE NOT NULL,
    fecha_despacho DATE NOT NULL DEFAULT CURRENT_DATE,
    id_unidad_administrativa INTEGER NOT NULL REFERENCES unidad_administrativa(id_unidad_administrativa),
    solicitante TEXT NOT NULL,
    id_usuario_despacha INTEGER NOT NULL REFERENCES usuario(id_usuario),
    observaciones TEXT,
    estado TEXT DEFAULT 'DESPACHADO',
    eliminado BOOLEAN DEFAULT false
);

CREATE TABLE IF NOT EXISTS despacho_almacen_detalle (
    id_despacho_almacen_detalle SERIAL PRIMARY KEY,
    id_despacho_almacen INTEGER NOT NULL REFERENCES despacho_almacen(id_despacho_almacen) ON DELETE CASCADE,
    id_articulo INTEGER NOT NULL REFERENCES articulo(id_articulo),
    cantidad_despachada NUMERIC(15,2) NOT NULL
);
