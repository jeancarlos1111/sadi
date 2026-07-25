# Solución de Problemas (Troubleshooting)

## Podman Compose: Error de conexión a la API (podman.sock)

Si al intentar levantar el entorno con `podman-compose up` o `podman compose up` te encuentras con un error similar a este:

```text
unable to get image 'postgres:15-alpine': failed to connect to the docker API at unix:///run/user/1000/podman/podman.sock; check if the path is correct and if the daemon is running: dial unix /run/user/1000/podman/podman.sock: connect: no such file or directory
```

### Causa
El comando `podman-compose` requiere comunicarse con la API de contenedores a través de un "socket". Si estás usando Podman sin permisos de administrador (rootless), es posible que este servicio no esté activo por defecto para tu usuario.

### Solución
Debes habilitar y encender el socket de Podman a nivel de usuario. Para ello, ejecuta el siguiente comando en tu terminal:

```bash
systemctl --user enable --now podman.socket
```

Una vez ejecutado, puedes volver a correr tu comando original (por ejemplo: `podman compose up --build`) y debería funcionar correctamente.

---

## Podman Compose: Error setting up Pasta (Failed to open /dev/net/tun)

Si durante el arranque de los contenedores ves un error de red indicando que "pasta" falló:

```text
Error response from daemon: setting up Pasta: pasta failed with exit code 1:
Failed to open() /dev/net/tun: No such file or directory
```

### Causa
A partir de Podman 5.x, el backend de red por defecto para contenedores rootless se cambió a `pasta`. Sin embargo, `pasta` requiere el dispositivo del kernel `/dev/net/tun`, el cual puede no estar disponible en ciertos sistemas (como WSL, entornos restringidos o contenedores anidados).

### Solución
Puedes revertir la configuración para que Podman utilice `slirp4netns`, el backend de red anterior que opera completamente en el espacio de usuario (userspace) y no requiere este dispositivo especial.

Para solucionarlo de forma permanente en tu usuario, crea o edita el archivo `~/.config/containers/containers.conf` y añade lo siguiente:

```ini
[network]
default_rootless_network_cmd = "slirp4netns"
```

Una vez agregado, vuelve a intentar el despliegue con `podman compose up`.

---

## PHP / Docker: Error de Permisos (mkdir(): Permission denied / move_uploaded_file)

Si al intentar subir un archivo o imagen (como un logo) desde el sistema obtienes una serie de advertencias (*warnings*) similares a:

```text
Warning: mkdir(): Permission denied in /var/www/html/src/Controllers/AdminController.php
Warning: move_uploaded_file(...): Failed to open stream: No such file or directory
Warning: move_uploaded_file(): Unable to move "/tmp/..."
```

### Causa
En entornos virtualizados como Docker o Podman, el servidor web (Nginx/PHP) se ejecuta bajo un usuario sin privilegios administrativos (ej. `www-data` o similar). Si el directorio destino de la subida (por ejemplo `public/uploads`) no existe y la carpeta padre no es modificable por este usuario, PHP no podrá crear la ruta ni almacenar el archivo, denegando el permiso.

### Solución
Debes crear la carpeta requerida manualmente en el sistema anfitrión (host) en la ruta correspondiente y concederle los permisos totales, ya que al usar un volumen compartido (bind mount), el contenedor heredará esta estructura y permisos.

Ejecuta lo siguiente desde la raíz del proyecto en tu terminal:

```bash
mkdir -p public/uploads
chmod 777 public/uploads
```

Al asignar los permisos `777`, le permites al usuario del contenedor web escribir libremente dentro de esa carpeta. Una vez ejecutado, vuelve a intentar la subida del archivo desde la aplicación web.
