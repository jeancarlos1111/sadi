-- Migración: create_table_oferta_proveedor
-- Fase 2: Módulo LCP

CREATE TABLE IF NOT EXISTS oferta_proveedor (
    id_oferta SERIAL PRIMARY KEY,
    id_proceso INTEGER NOT NULL REFERENCES proceso_contratacion(id_proceso) ON DELETE CASCADE,
    id_proveedor INTEGER NOT NULL REFERENCES proveedor(id_proveedor),
    fecha_presentacion DATE NOT NULL DEFAULT CURRENT_DATE,
    monto_ofertado NUMERIC(15,2) NOT NULL DEFAULT 0,
    descripcion_oferta TEXT,
    cumple_tecnicamente BOOLEAN DEFAULT true,
    es_ganador BOOLEAN DEFAULT false,
    observaciones TEXT,
    eliminado BOOLEAN DEFAULT false
);
