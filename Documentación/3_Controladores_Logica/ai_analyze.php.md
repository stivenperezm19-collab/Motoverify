# Documentación: `ai_analyze.php`

**Ubicación:** `controllers/ai_analyze.php`

---

## 1. Propósito
Este es uno de los archivos más innovadores del sistema. Funciona como un "analista visual". Su objetivo es recibir la foto de un repuesto que acaba de tomar el administrador, enviarla a la Inteligencia Artificial de Google Gemini y pedirle que identifique de qué repuesto se trata.

## 2. Lógica Principal
1. Recibe la fotografía que envió el usuario desde la cámara (enviada en formato de texto llamado Base64).
2. Convierte esa fotografía a un formato adecuado.
3. Se conecta al archivo `config/api.php` para obtener la clave secreta de Gemini.
4. Hace una llamada por internet a los servidores de Google enviando la foto y una instrucción clara: *"Analiza esta imagen y dime qué repuesto de moto es"*.
5. Cuando la Inteligencia Artificial responde, el controlador le pasa esa respuesta a la página web para que llene los datos o realice una búsqueda sugerida.

## 3. Relación con Otros Archivos
- **Depende de:** `config/api.php` (para poder "entrar" a usar la inteligencia artificial).
- **Es utilizado por:** Los botones de "cámara" que hay en `views/admin/dashboard.php` y `views/admin/repuestos.php`.

## 4. Fragmento de Código Clave

```php
// Obtenemos la clave de Gemini desde nuestra bóveda segura
$config = require '../config/api.php';
$apiKey = $config['gemini_api_key'];

// Preparamos la orden para la Inteligencia Artificial
$prompt = "Analiza esta imagen de un repuesto de motocicleta. 
Dime qué es, para qué marca o modelo podría servir y dame un nombre corto 
que pueda usar para buscarlo en mi inventario.";

// (Oculto): Código complejo que envía la foto y la pregunta a Google por internet
// ...
```
**¿Qué hace esto?**
El `prompt` (o instrucción) es vital. Le explica a la Inteligencia Artificial qué estamos buscando. Entre más claro sea este mensaje, más exacta será la respuesta de la IA cuando identifique el repuesto.
