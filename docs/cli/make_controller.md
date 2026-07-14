# Comando: `make:controller`

Genera un nuevo **Controlador** en la carpeta `src/Controllers/`.

El Controlador es responsable de recibir la petición HTTP, validar la entrada, comunicarse con el Repositorio para obtener/guardar datos, y renderizar la Vista `.phtml`. 

## Características del archivo generado
* Hereda de la clase base `HomeController`.
* Incluye un método `index()` preparado por defecto con el renderizado de la vista y la inyección del parámetro `$titulo`.

## Ejemplos de Uso

### 1. Crear un controlador
```bash
php sadi make:controller Formulacion
```
*Genera el archivo `src/Controllers/FormulacionController.php`.*

*(Nota: Para generar un controlador con todos los métodos CRUD (index, form, store, update, eliminar), es preferible utilizar el comando `make:section`).*

[⬅ Volver al Índice](README.md)
