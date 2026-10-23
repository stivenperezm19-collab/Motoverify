# Documentación: `repuestos_action.php`

**Ubicación:** `controllers/repuestos_action.php`

---

## 1. Propósito
Este es uno de los controladores más grandes y críticos del sistema. Es el encargado de recibir toda la información técnica de un repuesto nuevo (códigos, nombre, marca, especificaciones, fotos) y coordinar con los diferentes modelos para guardarlo correctamente en la base de datos. También se encarga de editar repuestos existentes y eliminarlos.

## 2. Lógica Principal
Debido a la cantidad de datos que maneja un repuesto, su lógica es extensa pero muy estructurada:
1. **Recolección:** Toma toda la información enviada desde el formulario de la página "Repuestos".
2. **Validación:** Comprueba que no se deje el código de barras o interno en blanco, y sobre todo, **verifica que no existan repuestos duplicados** (usando los modelos).
3. **Guardado principal:** Si los datos están bien, guarda la información escrita (texto) en la tabla principal de repuestos.
4. **Manejo de Imágenes:** Si se subió una o más fotografías del repuesto, las guarda en el servidor (`uploads/repuestos/`) y registra la conexión en la base de datos a través del `ImagenModel`.

## 3. Relación con Otros Archivos
- **Depende de:** `models/RepuestoModel.php` y `models/ImagenModel.php`.
- **Es utilizado por:** El formulario principal de la página `views/admin/repuestos.php`.

## 4. Control de Duplicados (Flujo de Seguridad)
Para mantener un inventario exacto, el sistema nunca permite guardar dos repuestos con el mismo código.
```php
// Ejemplo simplificado: Validar Código de Barras
if (!empty($codigo_barras)) {
    // Le preguntamos al modelo si ese código ya lo tiene otro repuesto (excluyendo el que estamos editando si es el caso)
    if ($repuestoModel->checkCodigoBarras($codigo_barras, $repuestoId)) {
        // Si responde que sí existe, detenemos todo y mostramos un error
        echo json_encode(['status' => 'error', 'message' => 'El código de barras ya está registrado en otro repuesto.']);
        exit;
    }
}
```
**¿Por qué es importante?**
En un sistema de inventario, si hay dos productos con el mismo código, el buscador o el sistema de código QR (`qr_search.php`) no sabría a cuál de los dos repuestos mostrar. Esta barrera de seguridad lo evita.

## 5. Edición vs Creación
El archivo distingue inteligentemente entre crear y editar usando una pequeña pista: **El ID**.
Si el formulario envía un `repuesto_id` vacío, el controlador sabe que es un repuesto totalmente nuevo e inserta datos. Si el formulario envía un número (ej. `repuesto_id = 15`), sabe que debe *actualizar* los datos del repuesto 15 en lugar de crear uno nuevo.
