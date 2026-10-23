# Documentación: `api.php`

**Ubicación:** `config/api.php`

---

## 1. Propósito
Este archivo actúa como una "bóveda" o caja fuerte. Su única misión es almacenar las claves secretas y contraseñas necesarias para conectar nuestra plataforma de Motoverify con servicios de Inteligencia Artificial externos, como Google Gemini o OpenAI (ChatGPT).

## 2. Lógica Principal
El archivo no ejecuta ninguna acción. Simplemente contiene una lista (conocida como "arreglo" en programación) donde cada nombre de servicio tiene asociada su respectiva clave secreta. Cuando el sistema lo requiere, lee este archivo y obtiene la clave correcta.

## 3. Relación con Otros Archivos
- **¿De quién depende?** De nadie. Solo almacena datos estáticos.
- **¿Quién lo utiliza?** Es utilizado por los controladores encargados de procesar imágenes o textos con IA. Por ejemplo, `ai_analyze.php` y `ai_import_action.php` cargan este archivo para poder "autenticarse" ante Google Gemini o OpenAI.

## 4. Elementos Importantes
- `gemini_api_key`: Es la "llave" que autoriza a nuestro sistema a usar el servicio de inteligencia artificial Gemini de Google.
- `openai_api_key`: Es la "llave" para usar los servicios de OpenAI.

## 5. Fragmento de Código Clave

```php
return [
    // Esta es la clave secreta para conectarnos con la IA de Google Gemini.
    'gemini_api_key' => 'AQ.Ab8R... (clave oculta) ...', 
    
    // Esta es la clave secreta para conectarnos con la IA de OpenAI.
    'openai_api_key' => 'sk-proj-... (clave oculta) ...',
];
```
**¿Qué hace esto?**
Usa la palabra `return` al inicio. Esto significa que cuando otro archivo de nuestro sistema "llama" o "incluye" a `api.php`, este archivo responde entregando directamente la lista de claves para que el otro archivo pueda usarlas de inmediato.

> [!CAUTION]
> **Aviso de Seguridad:**
> Este archivo contiene claves sensibles que cuestan dinero o tienen límites de uso. Si alguien más las obtiene, podría usarlas sin permiso. Es vital que este archivo **nunca** se comparta públicamente ni se suba a repositorios de código abiertos.
