# Documentación: `RepuestoModel.php`

**Ubicación:** `models/RepuestoModel.php`

---

## 1. Propósito
Este archivo funciona como el "gestor de inventario" del sistema. Su trabajo exclusivo es interactuar con la base de datos para realizar todo lo relacionado con los repuestos: crearlos, actualizarlos, revisar si sus códigos existen y borrarlos. Ninguna otra parte del sistema guarda repuestos directamente, sino que le piden el favor a este modelo.

## 2. Lógica Principal
La clase `RepuestoModel` agrupa varias operaciones bajo una misma herramienta.
1. Al iniciar (en su constructor `__construct`), se conecta a la base de datos (`$conn`).
2. Tiene funciones específicas para cada tarea (ej. revisar códigos, guardar, editar, borrar).
3. Todas las funciones utilizan consultas preparadas (usando signos de interrogación `?`) para evitar inyecciones SQL y mantener la plataforma segura.

## 3. Relación con Otros Archivos
- **¿De quién depende?** Depende del archivo `config/database.php` (para tener la conexión) y se apoya en el concepto de imágenes de `ImagenModel.php` a la hora de borrar.
- **¿Quién lo utiliza?** Es invocado principalmente por `controllers/repuestos_action.php` y `controllers/bulk_insert_action.php` cada vez que el administrador guarda, edita o elimina repuestos del inventario.

## 4. Funciones Importantes
- **`checkCodigoBarras($codigo)` / `checkCodigoInterno($codigo)`**: Revisa en la base de datos si el código que se intenta registrar ya existe. Esto evita repuestos duplicados.
- **`create($data)`**: Toma toda la información (nombre, descripción, marca, etc.) y la inserta como un nuevo repuesto. Devuelve el número de identificación único (ID) del repuesto guardado.
- **`update($id, $data)`**: Busca un repuesto existente usando su ID y actualiza sus características.
- **`delete($id)`**: Primero desconecta cualquier imagen asociada al repuesto y luego elimina el repuesto de forma definitiva de la base de datos.

## 5. Fragmento de Código Clave

```php
// Función para eliminar un repuesto
public function delete($id) {
    // 1. Primero desconecta y borra la relación de imágenes
    $queryRel = "DELETE FROM REPUESTO_IMAGEN WHERE id_repuesto = ?";
    $stmtRel = $this->conn->prepare($queryRel);
    if($stmtRel) {
        $stmtRel->bind_param("i", $id);
        $stmtRel->execute();
        $stmtRel->close();
    }

    // 2. Luego borra el repuesto como tal
    $query = "DELETE FROM REPUESTO WHERE id_repuesto = ?";
    $stmt = $this->conn->prepare($query);
    if (!$stmt) return false;
    
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result; // Devuelve si funcionó o no
}
```
**¿Qué hace esto?**
Esta es una regla importante de limpieza de datos. Si se borra un repuesto que tiene fotos asignadas, primero se rompe el lazo entre las fotos y el repuesto (para que no queden datos fantasma en la tabla `REPUESTO_IMAGEN`) y finalmente se elimina el repuesto de la tabla principal `REPUESTO`.
