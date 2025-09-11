# Snawbar Localization Manager

A comprehensive Laravel package that provides an intuitive web interface for managing translation files and overrides. Features a modern Bootstrap 5 UI with advanced tools for comparing, editing, and synchronizing language files across multiple locales.

## 🚀 Features

### Core Translation Management
- **Visual File Comparison**: Side-by-side comparison of translation keys across multiple languages
- **Missing Key Detection**: Automatically identifies missing translations with visual indicators
- **Real-time Editing**: Edit translation values directly in the browser with instant validation
- **File Status Overview**: Quick overview of translation completeness for each file

### Advanced Override System
- **Translation Overrides**: Create custom translation overrides without modifying core files
- **Searchable Key Selection**: Select2-powered search through all translation keys across files
- **CRUD Operations**: Complete create, read, update, delete functionality for overrides
- **Individual Record Management**: Manage each override independently with precise control

### Modern User Interface
- **Bootstrap 5 Design**: Clean, responsive interface that works on all devices
- **Interactive Components**: Modern modals, dropdowns, and form elements
- **Visual Feedback**: SweetAlert2 notifications for all user actions
- **File Organization**: Intelligent file sorting with priority for files containing missing keys

### Technical Features
- **Laravel 10+ Compatible**: Full support for modern Laravel versions
- **PHP 8.0+ Support**: Built with modern PHP features and type declarations
- **Middleware Protection**: Configurable middleware for route protection
- **Asset Management**: Organized CSS/JS assets with proper Laravel asset handling
- **Database Integration**: Efficient database storage for translation overrides

## 📦 Installation

### Step 1: Install via Composer
```bash
composer require mikailfaruqali/localization
```

### Step 2: Publish Configuration
```bash
php artisan vendor:publish --tag=snawbar-localization-config
```

### Step 3: Publish Assets
```bash
php artisan vendor:publish --tag=snawbar-localization-assets
```

### Step 4: Run Migrations
```bash
php artisan vendor:publish --tag=snawbar-localization-migrations
php artisan migrate
```

## ⚙️ Configuration

Configure the package in `config/snawbar-localization.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    |
    | Define the middleware that should be applied to the routes.
    | You can add authentication, authorization, or any custom middleware.
    |
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Define the base route prefix for the localization package.
    | For example, if set to 'localization', the routes will be:
    | - /localization/view (file selector)
    | - /localization/compare (translation editor)
    |
    */
    'route' => 'localization',

    /*
    |--------------------------------------------------------------------------
    | Language File Path
    |--------------------------------------------------------------------------
    |
    | Define the path where language files are stored.
    | By default, this points to the application's `lang` directory.
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
    */
    'base-locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Excluded Files
    |--------------------------------------------------------------------------
    |
    | Specify any language files that should be excluded from being loaded
    | or compared within the package.
    |
    */
    'exclude' => [
        // Add files to exclude, e.g., 'validation.php'
    ],
];
```

## 🎯 Usage

### Accessing the Interface

Visit the localization manager in your browser:
```
https://your-app.com/localization/view
```

### File Management

1. **File Selector**: The main dashboard shows all translation files with their completion status
2. **Visual Status Indicators**: 
   - ✅ Green check: All translations complete
   - ⚠️ Yellow warning: Missing translations detected
3. **File Selection**: Click on any file card to open the translation editor

### Translation Editor

1. **Side-by-Side Comparison**: View all languages for a file in organized columns
2. **Edit Translations**: Click on any translation value to edit it inline
3. **Missing Key Highlighting**: Missing translations are clearly marked
4. **Bulk Save**: Save all changes at once with validation

### Override Management

Access overrides at: `https://your-app.com/localization/overrides`

#### Creating Overrides
1. Navigate to the **Overrides** section
2. Click **"Add Override"**
3. **Search for Keys**: Use the searchable dropdown to find translation keys
   - Type to search across all translation files
   - Format: `file.key` (e.g., `auth.failed`, `validation.required`)
   - Preview original values in the dropdown
4. **Select Language**: Choose the target language
5. **Enter Value**: Provide the override translation
6. **Save**: Create the override

#### Managing Existing Overrides
- **View All**: See all overrides in a organized table
- **Edit Values**: Modify only the translation value (key and language are locked)
- **Delete**: Remove overrides when no longer needed
- **Search**: Find specific overrides quickly

## 🔧 Advanced Features

### Database Structure

The package creates an `override_translations` table:
```sql
CREATE TABLE override_translations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(255) NOT NULL,
    locale VARCHAR(2) NOT NULL,
    value TEXT NULL,
    UNIQUE KEY unique_key_locale (key, locale),
    KEY idx_key (key),
    KEY idx_locale (locale)
);
```

### API Endpoints

The package provides RESTful endpoints:

#### Translation Files
- `GET /localization/view` - File selector dashboard
- `GET /localization/compare?file={filename}` - Translation editor
- `POST /localization/update` - Save translation changes

#### Override Management
- `GET /localization/overrides` - Override management interface
- `GET /localization/overrides/search?query={term}` - Search translation keys
- `POST /localization/overrides/store` - Create new override
- `POST /localization/overrides/update` - Update existing override
- `DELETE /localization/overrides/delete` - Delete override

### Custom Middleware

Add authentication or authorization:

```php
// config/snawbar-localization.php
'middleware' => ['web', 'auth', 'can:manage-translations'],
```

### Route Customization

Change the base route prefix:

```php
// config/snawbar-localization.php
'route' => 'admin/translations', // Changes URL to /admin/translations/view
```

### File Exclusion

Protect sensitive files from editing:

```php
// config/snawbar-localization.php
'exclude' => [
    'validation.php',
    'passwords.php',
    'pagination.php',
],
```

## 🎨 Customization

### Styling

The package uses Bootstrap 5 with custom CSS. You can customize the appearance by:

1. **Publishing Assets**: `php artisan vendor:publish --tag=snawbar-localization-assets`
2. **Modifying CSS**: Edit `public/vendor/snawbar-localization/css/app.css`
3. **Custom Themes**: Add your own CSS classes

### JavaScript Customization

Customize behavior by modifying:
- `public/vendor/snawbar-localization/js/app.js`

### Views

Publish and customize views:
```bash
php artisan vendor:publish --tag=snawbar-localization-views
```

Then modify:
- `resources/views/vendor/snawbar-localization/`

## 🔒 Security Considerations

1. **Middleware Protection**: Always use appropriate middleware for production
2. **File Permissions**: Ensure proper file system permissions
3. **Input Validation**: All inputs are validated and sanitized
4. **CSRF Protection**: All forms include CSRF tokens
5. **File Exclusion**: Exclude sensitive translation files

## 🐛 Troubleshooting

### Common Issues

**Assets not loading:**
```bash
php artisan vendor:publish --tag=snawbar-localization-assets --force
```

**Permission denied:**
```bash
chmod -R 755 resources/lang/
```

**Missing styles:**
```bash
php artisan config:clear
php artisan view:clear
```

### Debug Mode

Enable debug mode for development:
```php
// .env
APP_DEBUG=true
```

## 📝 Changelog

### Version 2.0.0
- ✅ Complete Bootstrap 5 redesign
- ✅ Advanced override system with searchable keys
- ✅ Select2 integration for better UX
- ✅ Individual override record management
- ✅ Improved API structure
- ✅ Enhanced validation and error handling

### Version 1.0.0
- ✅ Initial release
- ✅ Basic translation file management
- ✅ File comparison interface
- ✅ Missing key detection

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 👥 Credits

- **Author**: Mikail Faruq Ali
- **Package**: Snawbar Localization
- **Framework**: Laravel
- **UI**: Bootstrap 5, Select2, SweetAlert2

---

**Need help?** Open an issue on GitHub or contact the maintainers.
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
