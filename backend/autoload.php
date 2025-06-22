<?php
spl_autoload_register(function ($className) {
    $paths = [
        __DIR__ . '/controllers/',
        __DIR__ . '/models/',
        __DIR__ . '/libs/',
    ];

    foreach ($paths as $path) {
        $filePath = $path . $className . '.php';
        if (file_exists($filePath)) {
            require_once $filePath;
            return;
        }
    }

    throw new Exception("Clase no encontrada: $className");
});
