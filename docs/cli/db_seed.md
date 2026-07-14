# Comando: `db:seed`

Ejecuta los scripts PHP encargados de inyectar datos maestros, diccionarios o usuarios iniciales a la base de datos (Sembradores o Seeders).

El comando busca iterativamente en la carpeta `database/` cualquier archivo que cumpla con el patrón `seed_*.php` y lo ejecuta respetando el entorno del sistema (con acceso a las clases y al Autoloader).

## Ejemplos de Uso

### 1. Poblar la base de datos
```bash
php sadi db:seed
```
*Si tienes archivos como `seed_onapre_extended.php` o `seed_oncop_extended.php`, el CLI los detectará y los insertará secuencialmente.*

[⬅ Volver al Índice](README.md)
