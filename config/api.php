<?php
// Este archivo guarda las configuraciones y claves secretas que necesitamos para usar servicios de inteligencia artificial (IA).
// IMPORTANTE: Este archivo contiene contraseñas muy sensibles. Nunca debes subirlo a un lugar público de internet como GitHub.

// Aquí le decimos al programa qué información debe usar cuando necesite estas claves.
return [
    // Esta es la clave secreta para conectarnos con la IA de Google llamada Gemini.
    'gemini_api_key' => '', 
    // Si necesitas crear una nueva clave, puedes hacerlo entrando a: https://aistudio.google.com/app/apikey
    // RECUERDA: Las claves de Gemini normalmente empiezan con las letras 'AIzaSy...'
    
    // Esta es la clave secreta para conectarnos con la IA de OpenAI (como ChatGPT).
    'openai_api_key' => '',
    
    // Esta es la clave para usar OpenRouter (que nos permite elegir modelos gratuitos)
    'openrouter_api_key' => '',
];

