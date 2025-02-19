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
    | Excluded Files
    |--------------------------------------------------------------------------
    |
    | Specify any language files that should be excluded from being loaded
    | or compared within the package. Useful if certain files should not
    | be modified through the UI.
    |
    */

    'exclude' => [

    ],
];
