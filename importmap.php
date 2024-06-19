<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    'admin' => [
        'path' => './assets/admin.js',
        'entrypoint' => true,
    ],
    'login' => [
        'path' => './assets/login.js',
        'entrypoint' => true,
    ],
    'registro' => [
        'path' => './assets/registro.js',
        'entrypoint' => true,
    ],
    'notfound' => [
        'path' => './assets/notfound.js',
        'entrypoint' => true,
    ],
    'competiciones' => [
        'path' => './assets/competiciones.js',
        'entrypoint' => true,
    ],
    'ui' => [
        'path' => './assets/ui.js',
        'entrypoint' => true,
    ],
    'usuarios' => [
        'path' => './assets/usuarios.js',
        'entrypoint' => true,
    ],
    'bootstrap-icons/font/bootstrap-icons.min.css' => [
        'version' => '1.11.3',
        'type' => 'css',
    ],
];
