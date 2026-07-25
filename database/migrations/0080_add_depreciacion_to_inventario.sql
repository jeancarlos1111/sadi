-- Migración: 0080_add_depreciacion_to_inventario
-- Agrega columnas para depreciación lineal de activos fijos

ALTER TABLE inventario_bienes ADD COLUMN vida_util_meses INTEGER DEFAULT 0;
ALTER TABLE inventario_bienes ADD COLUMN valor_residual REAL DEFAULT 0;

CREATE TABLE IF NOT EXISTS depreciacion_mensual (
    id_depreciacion SERIAL PRIMARY KEY,
    id_inventario_bienes INTEGER NOT NULL,
    mes INTEGER NOT NULL,
    anio INTEGER NOT NULL,
    monto_depreciado REAL NOT NULL,
    valor_en_libros REAL NOT NULL,
    id_comprobante_diario INTEGER,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_inventario_bienes) REFERENCES inventario_bienes(id_inventario_bienes) ON DELETE CASCADE,
    FOREIGN KEY (id_comprobante_diario) REFERENCES comprobante_diario(id_comprobante_diario) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_depreciacion_bienes ON depreciacion_mensual (id_inventario_bienes);
CREATE INDEX IF NOT EXISTS idx_depreciacion_periodo ON depreciacion_mensual (anio, mes);
