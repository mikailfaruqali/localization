# Localization

A robust Laravel package that provides an intuitive interface for managing, comparing, and editing language translation files. Easily maintain your localization resources with line-by-line comparisons, a Bootstrap-driven UI, and seamless support for adding new translation keys.

## Features
- Easy Translation Management: Edit your language files directly from the browser
- Line-by-Line Comparison: Compare translation keys across multiple languages
- Bootstrap-Driven UI: Modern responsive interface for localization
- Customizable: Configure middleware, file paths, and excluded files
- Seamless Integration: Works with Laravel 10 & 11 and PHP ^8.0
- Key Synchronization: Add new translation keys across all languages
- Validation: Ensure translation integrity with built-in checks
- File Protection: Exclude critical files from modification

## Installation

Install the package via Composer:

```bash
composer require mikailfaruqali/localization
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=snawbar-localization-config
```

Publish package assets (CSS/JS):

```bash
php artisan vendor:publish --tag=snawbar-localization-assets
```

## Configuration

Configure your settings in config/snawbar-localization.php:

```php
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
    | Base Locale
    |--------------------------------------------------------------------------
    |
    | The base locale defines the default language for your application.
    | All other languages will be loaded based on this locale.
    |
    | You can customize this to match the primary language of your application.
    | In most cases, 'en' for English is the default.
    |
    */

    'base-locale' => 'en',

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
```

## Usage
Access the translation interface at:
http://your-domain.com/{route_prefix}/view

## Key Functionality:

- Edit Translations
- Navigate to desired language file
- Modify values in textareas
- Click "Save Translations"
- Automatic synchronization across files
- Compare Files
- View side-by-side translations
- Identify missing translations
- Highlight discrepancies

## Security
- Recommended Security Measures:
- Add authentication middleware:

```php
'middleware' => ['web', 'auth']
```

- Restrict access to authorized users
- Keep excluded files list updated
- Excluding Files
- Protect critical files by adding to the exclude array:

```php
'exclude' => [
    'auth.php',
    'validation.php',
    'custom-protected.php'
]
```

# Contributing
- Fork the repository
- Create feature branch:

```bash
git checkout -b feature/your-feature
```

- Commit changes with descriptive messages
-Push to branch
-Create pull request

## License

MIT License - See [LICENSE](LICENSE) file.

## Credits

- **Developed by:** Snawbar  
- **Maintainer:** Mikail Faruq Ali  
- **Contact:** [alanfaruq85@gmail.com](mailto:alanfaruq85@gmail.com)  
- **GitHub:** [github.com/mikailfaruqali/localization](https://github.com/mikailfaruqali/localization)
