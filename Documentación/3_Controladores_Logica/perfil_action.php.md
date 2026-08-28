# Documentación: `perfil_action.php`

**Ubicación:** `controllers/perfil_action.php`

---

## 1. Propósito
Este controlador se ocupa exclusivamente de actualizar la información personal del administrador que ha iniciado sesión. Permite al usuario cambiar su nombre, su correo electrónico, y si lo desea, actualizar su contraseña por una nueva.

## 2. Lógica Principal
1. Se asegura de quién es el usuario solicitando la actualización mediante la sesión (`$_SESSION['user_id']`).
2. Recibe el nombre y el correo electrónico desde el formulario.
3. **Validación de Contraseña:** Si el usuario escribió una "nueva contraseña", el archivo la encripta para hacerla ilegible (segura) antes de guardarla. Si dejó el campo vacío, el sistema entiende que no quiere cambiar su clave y solo actualiza los datos básicos.
4. Llama a `AdministradorModel` para efectuar los cambios.

## 3. Relación con Otros Archivos
- **Depende de:** `models/AdministradorModel.php`.
- **Es utilizado por:** El formulario "Actualizar Perfil" en la vista `views/admin/perfil.php`. Cabe destacar que antes de llamar a este archivo, la pantalla de perfil primero llama a `verify_password.php` para asegurarse de que el usuario sabe su contraseña actual.

## 4. Fragmento de Código Clave

```php
// Verificamos si el usuario decidió escribir una contraseña nueva
$nueva_password = null; // Asumimos por defecto que no quiso cambiarla

if (!empty($_POST['nueva_password'])) {
    // Si escribió una, la encriptamos (la volvemos segura) para guardarla
    $nueva_password = password_hash($_POST['nueva_password'], PASSWORD_DEFAULT);
}

// Le pedimos al modelo que actualice los datos del perfil
$result = $adminModel->updateProfile($userId, $nombre, $email, $nueva_password);
```
**¿Qué hace esto?**
Es un mecanismo flexible. Si un administrador solo quiere cambiar su nombre, no se le obliga a cambiar su contraseña. La función `password_hash()` es vital: convierte una clave como "123456" en un texto largo e ilegible (ej. `$2y$10$w...`), lo cual es un estándar mundial de seguridad para proteger cuentas.
