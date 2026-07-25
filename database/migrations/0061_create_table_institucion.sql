CREATE TABLE IF NOT EXISTS institucion (
    id_institucion INTEGER PRIMARY KEY CHECK (id_institucion = 1),
    nombre VARCHAR(255) NOT NULL,
    rif VARCHAR(20) NOT NULL,
    direccion TEXT NOT NULL,
    telefono VARCHAR(50),
    correo VARCHAR(100),
    maxima_autoridad VARCHAR(255),
    cargo_autoridad VARCHAR(255),
    base_legal VARCHAR(255),
    codigo_onapre VARCHAR(50),
    logo_path VARCHAR(255),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Trigger for updated_at
CREATE OR REPLACE FUNCTION actualizar_timestamp_institucion()
RETURNS TRIGGER AS $$
BEGIN
    NEW.actualizado_en = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trigger_actualizar_institucion ON institucion;
CREATE TRIGGER trigger_actualizar_institucion
BEFORE UPDATE ON institucion
FOR EACH ROW
EXECUTE FUNCTION actualizar_timestamp_institucion();

-- Insert default record if it doesn't exist
INSERT INTO institucion (id_institucion, nombre, rif, direccion)
VALUES (1, 'Institución Pública de Prueba', 'G-20000000-0', 'Sede Central')
ON CONFLICT (id_institucion) DO NOTHING;
