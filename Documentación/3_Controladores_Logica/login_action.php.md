# Documentación: `login_action.php`

**Ubicación:** `controllers/login_action.php`

---

## 1. Propósito
Este archivo es el **guardia de seguridad** del panel de administración. Su única tarea es recibir el usuario y la contraseña que una persona escribe en la pantalla de inicio de sesión y decidir si le permite entrar al sistema o no.

## 2. Lógica Principal
Trabaja como un recepcionista que verifica una lista de invitados:
1. Revisa que la persona sí haya escrito tanto su usuario/correo como su contraseña.
2. Le pide al modelo (`AdministradorModel.php`) que busque en la base de datos si existe alguien con ese correo.
3. Si la persona existe, compara la contraseña que escribieron con la contraseña segura (encriptada) guardada en el sistema.
4. Si todo coincide, le entrega una "credencial virtual" (llamada sesión) y le dice a la página web que deje pasar a la persona al panel principal.
5. Si falla en algún punto, devuelve un mensaje de error ("Usuario incorrecto", "Contraseña incorrecta", etc.).

## 3. Relación con Otros Archivos
- **Depende de:** `config/database.php` (para conectarse) y `models/AdministradorModel.php` (para buscar al usuario).
- **Es utilizado por:** El formulario de inicio de sesión que está en `public/index.php`. Este archivo es el que procesa la petición cuando se presiona el botón "Iniciar Sesión".

## 4. Fragmento de Código Clave

```php
// Comparamos la contraseña que escribió el usuario con la encriptada en la base de datos
if (password_verify($password, $user['password'])) {
    
    // ¡La contraseña es correcta! Le creamos su sesión (su "gafete" virtual)
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    
    // Le avisamos a la pantalla que la validación fue exitosa para que lo deje pasar
    echo json_encode(['status' => 'success', 'redirect' => '../views/admin/dashboard.php']);
    exit;
} else {
    // Si la contraseña no coincide
    echo json_encode(['status' => 'error', 'message' => 'La contraseña es incorrecta.']);
    exit;
}
```
**¿Qué hace esto?**
Usa la función `password_verify` para comprobar contraseñas de forma segura. Si todo está bien, guarda el ID del usuario en `$_SESSION` (lo que le da acceso a las demás páginas restringidas) y responde con un mensaje de éxito (`success`) para que la pantalla redirija al dashboard.
