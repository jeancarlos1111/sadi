CREATE TABLE IF NOT EXISTS cierre_ejercicio (
    id_cierre SERIAL PRIMARY KEY,
    anio INTEGER NOT NULL UNIQUE,
    fecha_cierre TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_usuario INTEGER,
    reversado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE SET NULL
);
