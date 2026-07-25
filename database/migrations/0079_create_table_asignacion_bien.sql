-- Migración: create_table_asignacion_bien
-- Fase 4: Inventario Permanente (Asignación)

CREATE TABLE IF NOT EXISTS asignacion_bien (
    id_asignacion SERIAL PRIMARY KEY,
    numero_asignacion TEXT UNIQUE NOT NULL,
    id_articulo INTEGER NOT NULL REFERENCES articulo(id_articulo),
    cedula_responsable TEXT NOT NULL,
    id_unidad_administrativa INTEGER NOT NULL REFERENCES unidad_administrativa(id_unidad_administrativa),
    fecha_asignacion DATE NOT NULL DEFAULT CURRENT_DATE,
    estado_asignacion TEXT DEFAULT 'ACTIVA', -- ACTIVA, DEVUELTA, DESINCORPORADO
    eliminado BOOLEAN DEFAULT false
);

-- Agregar la relación al Kardex
ALTER TABLE inventario_movimiento 
ADD CONSTRAINT fk_inv_asignacion 
FOREIGN KEY (id_asignacion) REFERENCES asignacion_bien(id_asignacion);
