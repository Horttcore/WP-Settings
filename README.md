# WordPress Settings API Composer Package

A helper package for working with the WordPress Settings API

## Installation

`composer require ralfhortt/wp-settings`

## Usage

### Settings Page

The Settings Page approach uses the WordPress Settings API to create admin settings pages and subpages.

#### Basic Usage

```php
<?php
use RalfHortt\Settings\SettingsPage;

(new SettingsPage)
    ->page('My Theme Settings')
    ->subpageUnder('options-general.php')
    ->panel( __('My Panel', 'textdomain') )
        ->section( __('My Section', 'textdomain') )
            ->checkbox( 'my-checkbox', __('Checkbox', 'textdomain') )
            ->color( 'my-color', __('Color', 'textdomain') )
            ->file( 'my-file', __('File', 'textdomain') )
            ->image( 'my-image', __('Image', 'textdomain') )
            ->pageDropdown( 'my-page', __('Page', 'textdomain') )
            ->radio( 'my-radio', __('Radio', 'textdomain'), ['option1' => 'Option 1', 'option2' => 'Option 2'] )
            ->select( 'my-select', __('Select', 'textdomain'), ['option1' => 'Option 1', 'option2' => 'Option 2'] )
            ->text( 'my-text', __('Text', 'textdomain') )
            ->textarea( 'my-textarea', __('Textarea', 'textdomain') )
            ->url( 'my-url', __('Url', 'textdomain') )
    ->register();
```

#### Settings Page Configuration

```php
<?php
use RalfHortt\Settings\SettingsPage;

(new SettingsPage)
    ->page(
        'Theme Configuration',
        'Theme Config',                    // menu title
        'my-theme-config',                // slug
        'edit_theme_options'              // capability
    )
    ->subpageUnder('options-general.php') // parent (Settings menu)
    ->panel('Design Settings')
        ->section('Colors')
            ->color('primary-color', 'Primary Color')
    ->register();
```

#### Multiple Panel Tabs On One Page

When you register more than one panel on the same page, the page renders panel tabs automatically.

```php
<?php
use RalfHortt\Settings\SettingsPage;

(new SettingsPage)
    ->page('Theme Configuration')
    ->subpageUnder('options-general.php')
    ->panel('Design')
        ->section('Colors')
            ->color('primary-color', 'Primary Color')
    ->panel('Content')
        ->section('Homepage')
            ->text('hero-title', 'Hero Title')
    ->panel('Integrations')
        ->section('API')
            ->url('api-endpoint', 'API Endpoint')
    ->register();
```

Notes:

- Panels become tabs only when at least two panels exist.
- A panel can contain multiple sections.
- Section order follows the order you define in your fluent chain.

#### Top-Level Menu Page

```php
<?php
use RalfHortt\Settings\SettingsPage;

(new SettingsPage)
    ->page(
        'Brand Manager',        // title
        null,                   // menu title (defaults to title)
        null,                   // slug (auto-generated)
        'manage_options'        // capability
    )
    ->topLevel('dashicons-art', 25)
    ->panel('Brand Assets')
        ->section('Logos')
            ->image('main-logo', 'Main Logo')
    ->register();
```

#### Available Parent Menus

- `'themes.php'` - Appearance menu
- `'options-general.php'` - Settings menu (default)
- `'tools.php'` - Tools menu
- `null` - Top-level menu (requires icon and position)
- Custom parent slug for submenus

## Advanced Usage

### Settings Page Advanced Features

#### Options Storage

```php
<?php
use RalfHortt\Settings\SettingsPage;

(new SettingsPage)
    ->page('My Settings')
    ->subpageUnder('options-general.php')
    ->panel( __('My Panel', 'textdomain')  )
        ->section( __('My Section', 'textdomain') )
            ->text(
                'my-text',                    // identifier
                __('My Text', 'textdomain'),  // label
                ''                            // default value
            )
    ->register();
```

#### Check for a capability

```php
<?php
use RalfHortt\Settings\SettingsPage;

(new SettingsPage)
    ->page('My Settings')
    ->subpageUnder('options-general.php')
    ->panel( 'My Panel' )
        ->section( __('My Section', 'textdomain') )
            ->text(
                'my-text',                    // identifier
                __('My Text', 'textdomain'),  // label
                '',                           // default value
                'edit_posts'                  // capability
            )
    ->register();
```

#### Add a description

```php
<?php
use RalfHortt\Settings\SettingsPage;

(new SettingsPage)
    ->page('My Settings')
    ->subpageUnder('options-general.php')
    ->panel( 'My Panel' )
        ->section( 'My Section' )
            ->text(
                'my-text',                               // identifier
                'Text',                                  // label
                '',                                      // default value
                '',                                      // capability
                __('This is awesome', 'textdomain')     // description
            )
    ->register();
```

#### Retrieving data

```php
<?php
// Retrieve data from your settings page fields
$option = get_option('my-text');
```

## Changelog

### v1.0.0

- Initial release
