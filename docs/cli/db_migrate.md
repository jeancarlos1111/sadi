# Comando: `db:migrate`

Ejecuta y aplica las instrucciones SQL directamente en la base de datos configurada.

Toma el archivo central estructurado `database/schema.sql` y lo despacha a través de un subproceso de consola utilizando el binario `psql`. Es extremadamente útil al levantar un entorno por primera vez o al actualizar la estructura.

## Ejemplos de Uso

### 1. Migrar la base de datos de Desarrollo
```bash
php sadi db:migrate
```
*Lee la configuración (host, usuario, clave, db) del archivo `.env`.*

### 2. Migrar la base de datos de Pruebas (Testing)
```bash
php sadi db:migrate --env=.env.testing
```
*Aplica el esquema SQL en la base de datos dedicada a Pest PHP (`sadi_test` por defecto).*

[⬅ Volver al Índice](README.md)
