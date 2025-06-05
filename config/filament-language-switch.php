<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | These are the locales that Filament will use to put translate the app.
    |
    */

    'locales' => [
        'en' => 'English',
        'mt' => 'Maltese',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | This is the default locale that Filament will use to translate the app.
    | Set as null to use app's locale.
    |
    */

    'default_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Flags
    |--------------------------------------------------------------------------
    |
    | This is the default flag style that Filament will use.
    | flags: enables the flagpack.
    | circled-flags: enables the circled-flags.
    | null: disables flags
    |
    */

    'flag_style' => 'flags',

    /*
    |--------------------------------------------------------------------------
    | Show full text with flag
    |--------------------------------------------------------------------------
    |
    | This is the default flag style that Filament will use.
    | flags: enables the flagpack.
    | circled-flags: enables the circled-flags.
    | null: disables flags
    |
    */

    'show_language_name' => true,

    /*
    |--------------------------------------------------------------------------
    | Show language switcher in auth pages
    |--------------------------------------------------------------------------
    |
    | This will show the language switcher on login, register, forgot password.
    |
    */

    'show_in_auth' => true,

    /*
    |--------------------------------------------------------------------------
    | Show language switcher in admin pages
    |--------------------------------------------------------------------------
    |
    | This will show the language switcher on admin pages.
    |
    */

    'show_in_admin' => true,

    /*
    |--------------------------------------------------------------------------
    | Language Switcher position in the user menu
    |--------------------------------------------------------------------------
    |
    | This will place the language switcher in the user menu.
    |
    */

    'in_user_menu' => true,

    /*
    |--------------------------------------------------------------------------
    | Only show the language switcher for admin panel
    |--------------------------------------------------------------------------
    |
    | Enabling this option will only make the language switcher for admin panel.
    |
    */

    'admin_panel_only' => true,
];
