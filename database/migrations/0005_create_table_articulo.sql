-- Migración: articulo
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS articulo (

    id_articulo SERIAL PRIMARY KEY,
    denominacion_a TEXT,
    observacion_a TEXT,
    id_tipo_de_articulo INTEGER,
    id_unidades_de_medida INTEGER,
    id_codigo_plan_unico INTEGER,
    aplicar_iva BOOLEAN DEFAULT false,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_tipo_de_articulo) REFERENCES tipo_de_articulo(id_tipo_de_articulo) ON DELETE SET NULL,
    FOREIGN KEY (id_unidades_de_medida) REFERENCES unidades_de_medida(id_unidades_de_medida) ON DELETE SET NULL
);
