# Documentación: `catalogo_action.php`

**Ubicación:** `controllers/catalogo_action.php`

---

## 1. Propósito
Este archivo funciona como el mediador entre la página de "Catálogo" (donde el administrador ve y edita las categorías) y la base de datos. Se encarga de procesar todas las solicitudes del administrador relacionadas con la gestión de categorías: crearlas, actualizarlas y eliminarlas (o desactivarlas).

## 2. Lógica Principal
El archivo primero verifica que el usuario sea realmente un administrador (comprobando la sesión). Luego, identifica qué "acción" quiere realizar el usuario, enviada a través de un dato oculto en el formulario.
Según la acción solicitada, ejecuta una función distinta en la base de datos:
- **`create` (Crear):** Revisa que el nombre no esté vacío ni repetido, y luego guarda la nueva categoría.
- **`update` (Editar):** Verifica que la categoría exista mediante su ID, comprueba que el nuevo nombre no esté siendo usado por otra categoría distinta, y luego guarda los cambios.
- **`delete` (Eliminar):** Por motivos de seguridad y para no dañar repuestos existentes que pertenezcan a esa categoría, el sistema usualmente "desactiva" la categoría en lugar de borrarla para siempre.

## 3. Relación con Otros Archivos
- **Depende de:** `config/database.php` (para conectarse) y `models/CategoriaModel.php` (para ejecutar los comandos de guardar/editar/borrar en la tabla real).
- **Es utilizado por:** La vista `views/admin/catalogo.php`. Cuando el administrador rellena el formulario de categoría y hace clic en "Guardar", la información viaja hacia este controlador.

## 4. Fragmento de Código Clave

```php
// Comprobamos qué acción nos está pidiendo el administrador
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    // Si quiere crear, primero validamos que el nombre no esté repetido
    if ($categoriaModel->checkNameExists($nombre)) {
        echo json_encode(['status' => 'error', 'message' => 'Ya existe una categoría con este nombre.']);
        exit;
    }
    // Si todo está bien, la guardamos
    $result = $categoriaModel->create($nombre, $descripcion);
    // ...
}
```
**¿Qué hace esto?**
Es el "cerebro de distribución". Dependiendo de la variable `$action`, el archivo sabe exactamente qué bloque de código ejecutar. La validación antes de guardar (`checkNameExists`) es crucial para mantener organizada la tienda y no tener categorías duplicadas.
