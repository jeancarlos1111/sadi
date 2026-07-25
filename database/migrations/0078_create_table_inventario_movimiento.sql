-- Migración: create_table_inventario_movimiento
-- Fase 4: Inventario Permanente (Kardex)

CREATE TABLE IF NOT EXISTS inventario_movimiento (
    id_movimiento SERIAL PRIMARY KEY,
    id_articulo INTEGER NOT NULL REFERENCES articulo(id_articulo),
    fecha DATE NOT NULL DEFAULT CURRENT_DATE,
    tipo_movimiento TEXT NOT NULL, -- ENTRADA, SALIDA, AJUSTE
    cantidad NUMERIC(15,2) NOT NULL,
    costo_unitario NUMERIC(15,2) DEFAULT 0,
    id_acta_recepcion INTEGER REFERENCES acta_recepcion(id_acta_recepcion),
    id_asignacion INTEGER, -- Se vinculará en la migración 0079
    eliminado BOOLEAN DEFAULT false
);

-- Asegurar que la tabla articulo tenga stock_actual
ALTER TABLE articulo ADD COLUMN IF NOT EXISTS stock_actual NUMERIC(15,2) DEFAULT 0;
