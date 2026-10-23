# Documentación: `ImagenModel.php`

**Ubicación:** `models/ImagenModel.php`

---

## 1. Propósito
En el sistema, las imágenes no se guardan directamente dentro del Repuesto o la Marca, sino que se gestionan de forma independiente. Este archivo se encarga de todo lo relacionado con las fotos subidas a la plataforma: registrar dónde se guardó físicamente el archivo en el servidor y conectarlo (vincularlo) con la entidad que corresponda (un repuesto, una marca o una categoría).

## 2. Lógica Principal
Trabaja en dos pasos:
1. Registra la imagen en una tabla general llamada `IMAGEN`, anotando la ruta del archivo (ej. `uploads/repuestos/foto1.jpg`).
2. Conecta ese registro general con la tabla específica (ej. `REPUESTO_IMAGEN` o `MARCA_IMAGEN`) para saber a quién le pertenece.

## 3. Relación con Otros Archivos
- **¿Quién lo utiliza?** Los controladores como `repuestos_action.php` o `marcas_action.php`. Cuando el administrador sube una foto en el formulario, el controlador guarda la imagen en la carpeta `uploads` y luego llama a `ImagenModel` para que la registre en la base de datos.

## 4. Funciones Importantes
- **`saveImage($ruta, $nombre_original, $tipo_entidad, $id_entidad)`**: Es la función maestra. Hace todo el trabajo de registro y vinculación explicado arriba de una sola vez.
- **`limpiarRelacion($tipo_entidad, $id_entidad)`**: Si un administrador cambia la foto de una marca, esta función borra la conexión de la foto anterior para que la marca no termine con múltiples fotos mezcladas.

## 5. Ejemplo de Proceso (Flujo)
1. Subes la foto del repuesto "Freno X".
2. El sistema guarda la foto física en la carpeta `uploads/repuestos/`.
3. Llama a `saveImage(...)` pasándole la ruta de la foto y diciendo "Esta foto pertenece a un REPUESTO con ID 5".
4. El modelo inserta la foto en la tabla general `IMAGEN` (y recibe, por ejemplo, el ID de imagen 10).
5. Luego inserta en la tabla `REPUESTO_IMAGEN` que el repuesto 5 está vinculado con la imagen 10.
