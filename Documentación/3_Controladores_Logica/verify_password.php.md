# Documentación: `verify_password.php`

**Ubicación:** `controllers/verify_password.php`

---

## 1. Propósito
Este archivo actúa como un filtro de seguridad adicional (un "doble check") cuando un administrador intenta hacer algo delicado, como cambiar su información personal en la página de su perfil. Le pide al administrador que vuelva a ingresar su contraseña actual para comprobar que es realmente él quien está frente a la pantalla y no alguien que encontró la computadora desbloqueada.

## 2. Lógica Principal
1. Se asegura de saber qué usuario está haciendo la petición usando la sesión actual (`$_SESSION['user_id']`).
2. Toma la contraseña que el usuario acaba de escribir en el formulario.
3. Busca en la base de datos al administrador y compara la contraseña que escribió con la contraseña real (encriptada).
4. Responde con un simple "éxito" (`success`) si es correcta, o un "error" si falló.

## 3. Relación con Otros Archivos
- **Depende de:** `config/database.php` (conexión) y `models/AdministradorModel.php` (para buscar la clave original).
- **Es utilizado por:** `views/admin/perfil.php`. Cuando el usuario intenta guardar los cambios de su perfil, esa pantalla llama "por debajo de la mesa" (usando JavaScript) a este archivo para validar la contraseña antes de guardar.

## 4. Fragmento de Código Clave
```php
// Busca los datos de este usuario en específico
$admin = $adminModel->getById($userId);

// Comparamos la contraseña que escribió con la que tenemos guardada
if (password_verify($password, $admin['password'])) {
    // Si coincide, le decimos que todo está correcto (success)
    echo json_encode(['status' => 'success']);
} else {
    // Si no coincide, marcamos error
    echo json_encode(['status' => 'error', 'message' => 'Contraseña incorrecta.']);
}
```
**¿Qué hace esto?**
Usa la misma tecnología segura de verificación de contraseñas (`password_verify`) que se usa en el inicio de sesión para comprobar la identidad del administrador antes de permitirle hacer cambios importantes.
