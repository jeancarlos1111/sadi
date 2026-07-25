-- Migración: 0081_create_table_toma_inventario
-- Crea tablas para el proceso de toma de inventario físico

CREATE TABLE IF NOT EXISTS toma_inventario (
    id_toma SERIAL PRIMARY KEY,
    fecha_toma DATE NOT NULL,
    responsable TEXT NOT NULL,
    observaciones TEXT,
    estado TEXT NOT NULL DEFAULT 'ABIERTA', -- ABIERTA, CERRADA
    fecha_cierre TIMESTAMP,
    eliminado BOOLEAN DEFAULT false
);

CREATE TABLE IF NOT EXISTS toma_inventario_detalle (
    id_detalle SERIAL PRIMARY KEY,
    id_toma INTEGER NOT NULL,
    tipo TEXT NOT NULL, -- 'BIEN' o 'INSUMO'
    id_articulo INTEGER NOT NULL,
    cantidad_sistema INTEGER NOT NULL DEFAULT 0,
    cantidad_fisica INTEGER NOT NULL DEFAULT 0,
    diferencia INTEGER NOT NULL DEFAULT 0,
    justificacion TEXT,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_toma) REFERENCES toma_inventario(id_toma) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_toma_inventario_estado ON toma_inventario (estado);
CREATE INDEX IF NOT EXISTS idx_toma_inventario_detalle_toma ON toma_inventario_detalle (id_toma);
