# Documentación: `logout.php`

**Ubicación:** `controllers/logout.php`

---

## 1. Propósito
Este archivo funciona como la **puerta de salida** del sistema. Se encarga de cerrar de forma segura la cuenta del administrador, borrando todos los registros temporales de su conexión para que nadie más pueda usar su sesión en esa computadora.

## 2. Lógica Principal
El proceso de cerrar sesión debe ser seguro, por lo que el archivo sigue tres pasos críticos:
1. Vacía toda la información temporal (la sesión actual).
2. Borra las "galletas" (cookies) que el navegador pudo haber guardado para recordar al usuario.
3. Destruye definitivamente el espacio de memoria de la sesión en el servidor y redirige al usuario a la página de inicio pública.

## 3. Relación con Otros Archivos
- **¿Quién lo utiliza?** Es activado desde los botones "Cerrar Sesión" que se encuentran en el panel de administrador (por ejemplo, en `views/admin/perfil.php`). Al hacer clic en esos enlaces, el usuario es enviado a este archivo.

## 4. Fragmento de Código Clave

```php
// 1. Borramos la información guardada del usuario (su nombre, su ID)
$_SESSION = array();

// 2. Destruimos por completo el sistema de sesión para este usuario
session_destroy();

// 3. Lo enviamos de regreso a la página principal pública
header("Location: ../public/index.php");
exit;
```
**¿Qué hace esto?**
Primero se asegura de vaciar la variable `$_SESSION`. Luego utiliza `session_destroy()` que literalmente le dice al servidor "olvida a esta persona". Finalmente, `header()` funciona como un transporte que lleva al navegador de vuelta a la página principal de Motoverify.
