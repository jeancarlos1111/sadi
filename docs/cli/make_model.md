# Comando: `make:model`

Genera un nuevo **Modelo** en la carpeta `src/Models/`. 

Por normativa del proyecto SADI, los modelos actúan exclusivamente como **Data Transfer Objects (DTOs)**. Esto significa que no contienen lógica de negocio ni dependencias externas (como conexiones a la base de datos). 

## Características del archivo generado
* Incluye `declare(strict_types=1)`.
* La clase se define como `readonly class` (inmutabilidad).
* Implementa los métodos `toArray()` y `fromArray()` obligatorios para la serialización.

## Ejemplos de Uso

### 1. Crear un modelo básico
```bash
php sadi make:model Proyecto
```
*Resultado: Se crea el archivo `src/Models/Proyecto.php`.*

[⬅ Volver al Índice](README.md)
