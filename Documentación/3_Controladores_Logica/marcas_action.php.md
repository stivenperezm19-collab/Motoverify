# Documentación: `marcas_action.php`

**Ubicación:** `controllers/marcas_action.php`

---

## 1. Propósito
Al igual que el gestor de categorías, este archivo es el intermediario encargado de manejar todas las operaciones relacionadas con las **marcas** (Yamaha, Suzuki, etc.). Procesa la información enviada desde el panel de administración para agregar nuevas marcas, editar sus nombres o cambiar sus logos.

## 2. Lógica Principal
Este controlador es un poco más complejo que el de categorías porque debe manejar **imágenes**.
1. Identifica qué acción se quiere hacer (Crear, Editar, Eliminar).
2. Si es Crear o Editar, primero guarda los datos de texto (el nombre de la marca).
3. **Manejo de imágenes:** Si el usuario subió el logo de una marca, el archivo primero guarda esa imagen físicamente en la carpeta `uploads/marcas/`, luego utiliza el modelo de imágenes para registrar esa ruta en la base de datos y la conecta con la marca que se acaba de crear o editar.

## 3. Relación con Otros Archivos
- **Depende de:** 
  - `models/MarcaModel.php` (para guardar la información de la marca).
  - `models/ImagenModel.php` (para registrar el logo subido).
- **Es utilizado por:** El formulario en `views/admin/marcas.php`.

## 4. Proceso de Subida de Imagen (Flujo)
Cuando se crea una marca con un logo nuevo:
1. El archivo toma la información de la marca (ej. "Honda") y la guarda, obteniendo su nuevo ID (ej. ID 5).
2. Revisa si en el formulario venía un archivo de imagen.
3. Si es así, crea un nombre único para la imagen (para que no se sobreescriba otra igual) y la mueve a la carpeta `uploads/marcas/`.
4. Le dice al `ImagenModel`: "Guarda esta foto y vincúlala con la marca número 5".

## 5. Fragmento de Código Clave

```php
// Si el usuario adjuntó una imagen válida...
if (isset($_FILES['imagen_marca']) && $_FILES['imagen_marca']['error'] === UPLOAD_ERR_OK) {
    // Definimos dónde se guardará
    $uploadDir = '../uploads/marcas/';
    
    // Creamos un nombre seguro e irrepetible para la foto
    $fileName = 'marca_' . $marcaId . '_' . time() . '.png';
    $targetPath = $uploadDir . $fileName;

    // Si logramos mover el archivo temporal a nuestra carpeta definitiva...
    if (move_uploaded_file($_FILES['imagen_marca']['tmp_name'], $targetPath)) {
        // Le pedimos al modelo que registre esta foto y la una a la marca
        $imagenModel = new ImagenModel($conn);
        $imagenModel->saveImage('uploads/marcas/' . $fileName, $fileName, 'MARCA', $marcaId);
    }
}
```
**¿Qué hace esto?**
Es el proceso de subida de archivos seguro. `move_uploaded_file` es la función de PHP que toma la foto que el usuario subió (que está guardada temporalmente) y la coloca en su hogar definitivo (`uploads/marcas/`). Luego conecta esa foto con la marca recién creada o editada.
