# Documentación: `bulk_insert_action.php`

**Ubicación:** `controllers/bulk_insert_action.php`

---

## 1. Propósito
Este archivo significa "Acción de Inserción Masiva". Es el compañero final de la herramienta de "Importación con IA". Una vez que la IA leyó la factura y el administrador revisó la tabla en pantalla, este archivo se encarga de guardar **todos esos repuestos al mismo tiempo** en la base de datos.

## 2. Lógica Principal
1. Recibe un paquete grande de datos (una lista de múltiples repuestos).
2. Abre la lista y empieza a procesar un repuesto a la vez.
3. Para cada repuesto, revisa si la "Marca" y "Categoría" que descubrió la IA ya existen en el sistema.
    - Si la marca no existe, **la crea automáticamente**.
    - Si la categoría no existe, **la crea automáticamente**.
4. Finalmente, guarda el repuesto y repite el ciclo hasta terminar toda la lista.

## 3. Relación con Otros Archivos
- **Depende de:** `models/RepuestoModel.php`, `models/MarcaModel.php`, `models/CategoriaModel.php`. Depende de todos porque, al ser masivo, necesita crear todo tipo de datos.
- **Es utilizado por:** El botón "Guardar Seleccionados" que aparece después de haber usado la importación por Inteligencia Artificial.

## 4. Fragmento de Código Clave

```php
// Recorremos la lista de repuestos uno por uno (esto es un bucle 'foreach')
foreach ($repuestos_a_guardar as $rep) {
    
    $id_marca_final = nulo;
    // Buscamos si la marca que dice el documento ya está registrada
    if ($marcaModel->checkNameExists($rep['marca_texto'])) {
        $id_marca_final = $marcaModel->obtenerIdPorNombre($rep['marca_texto']);
    } else {
        // ¡Magia! Si la marca no existe en el catálogo, la crea en ese mismo instante
        $id_marca_final = $marcaModel->create($rep['marca_texto'], 'Creada por IA');
    }
    
    // Luego guarda el repuesto asignándole esa marca
    $repuestoModel->create(...);
}
```
**¿Por qué es importante esto?**
Le ahorra muchísimo tiempo al administrador. Si la IA detecta que en la factura hay piezas de la marca "Brembo", y "Brembo" no estaba registrada en el sistema de Motoverify, el administrador no tiene que ir a la pantalla de Marcas a crearla manualmente. Este archivo lo detecta y la crea en fracciones de segundo antes de guardar el repuesto.
