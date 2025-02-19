<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    |
    | Define the middleware that should be applied to the routes of the
    | localization package. By default, it includes "web" middleware,
    | ensuring session and CSRF protection support.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Define the base route prefix for the localization package. This prefix
    | will be used for all routes related to localization management.
    | For example, if set to 'localization', the routes will be:
    | - /localization/view (to view translations)
    | - /localization/update (to update translations)
    |
    | You can customize this prefix to fit your application's routing structure.
    |
    */

    'route' => 'localization',

    /*
    |--------------------------------------------------------------------------
    | Language File Path
    |--------------------------------------------------------------------------
    |
    | Define the path where language files are stored. By default, this points
    | to the application's `lang` directory. This is where the package will
    | load language files from.
    |
    | You can customize this if your language files are stored in a different
    | location.
    |
    */

    'path' => lang_path(),

    /*
    |--------------------------------------------------------------------------
    | Excluded Files
    |--------------------------------------------------------------------------
    |
    | Specify any language files that should be excluded from being loaded
    | or compared within the package. Useful if certain files should not
    | be modified through the UI.
    |
    */

    'exclude' => [
        'auth.php',
        'validation.php',
        'pagination.php',
        'passwords.php',
    ],
];
