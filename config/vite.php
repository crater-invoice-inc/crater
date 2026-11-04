<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Entrypoints
    |--------------------------------------------------------------------------
    | The files in the configured directories will be considered
    | entry points and will not be required in the configuration file.
    | To disable the feature, set to false.
    */
    'entrypoints' => [
        'resources/scripts/main.js',
    ],
    'ignore_patterns' => ["/\.d\.ts$/"],

    /*
    |--------------------------------------------------------------------------
    | Aliases
    |--------------------------------------------------------------------------
    | These aliases will be added to the Vite configuration and used
    | to generate a proper tsconfig.json file.
    */
    'aliases' => [
        '@' => 'resources',
    ],

    /*
    |--------------------------------------------------------------------------
    | Static assets path
    |--------------------------------------------------------------------------
    | This option defines the directory that Vite considers as the
    | public directory. Its content will be copied to the build directory
    | at build-time.
    | https://vitejs.dev/config/#publicdir
    */
    'public_directory' => resource_path('static'),

    /*
    |--------------------------------------------------------------------------
    | Ping timeout
    |--------------------------------------------------------------------------
    | The maximum duration, in seconds, that the ping to the development
    | server should take while trying to determine whether to use the
    | manifest or the server in a local environment.
    */
    'ping_timeout' => .1,

    /*
    |--------------------------------------------------------------------------
    | Build path
    |--------------------------------------------------------------------------
    | The directory, relative to /public, in which Vite will build
    | the production files. This should match "build.outDir" in the Vite
    | configuration file.
    */
    'build_path' => 'build',

    /*
    |--------------------------------------------------------------------------
    | Development URL
    |--------------------------------------------------------------------------
    | The URL at which the Vite development server runs.
    | This is used to generate the script tags when developing.
    */
    'dev_url' => env('VITE_DEV_URL', 'http://localhost:3000'),
];
