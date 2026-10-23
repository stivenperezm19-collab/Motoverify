# Documentación: `qr_search.php`

**Ubicación:** `controllers/qr_search.php`

---

## 1. Propósito
Este archivo es un motor de búsqueda rápido y exacto. Se activa cuando un usuario o administrador escanea el código de barras (o QR) de un repuesto utilizando la cámara de su celular o computadora. Su objetivo es encontrar exactamente el repuesto al que pertenece ese código.

## 2. Lógica Principal
1. Recibe el número de código escaneado (ej. "770123456").
2. Realiza una búsqueda directa y rápida en la base de datos de repuestos buscando una coincidencia en los campos `codigo_barras` o `codigo_interno`.
3. Si lo encuentra, devuelve un enlace directo a la **Ficha Técnica** de ese repuesto para que el sistema redireccione automáticamente al usuario.

## 3. Relación con Otros Archivos
- **Depende de:** `config/database.php`.
- **Es utilizado por:** El lector de códigos en la pantalla principal (Librería de escaneo QR de la interfaz).

## 4. Fragmento de Código Clave

```php
// Tomamos el código que la cámara acaba de escanear
$codigo = $_POST['scanned_code'];

// Buscamos un repuesto que tenga ese código
$query = "SELECT id_repuesto FROM REPUESTO WHERE codigo_barras = ? OR codigo_interno = ? LIMIT 1";
// ... (código de conexión seguro) ...

if ($encontrado) {
    // Si lo encontró, avisa éxito y devuelve la dirección web de la Ficha Técnica de ese repuesto
    echo json_encode([
        'status' => 'success', 
        'redirect' => '../public/ficha_tecnica.php?id=' . $encontrado['id_repuesto']
    ]);
} else {
    // Si el código no está en el inventario
    echo json_encode(['status' => 'error', 'message' => 'Repuesto no encontrado en el sistema.']);
}
```
**¿Qué hace esto?**
Automatiza la navegación. En lugar de hacer que el cliente escriba el código y presione enter, al momento en que la cámara detecta el código, este archivo valida si existe y automáticamente empuja a la pantalla del cliente hacia la ficha técnica (`redirect`).
