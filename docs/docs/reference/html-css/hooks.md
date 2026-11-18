---
title: Hooks et filtres
prev: /reference/
---

# Hooks et filtres

Portal utilise les hooks standards de WordPress pour sa configuration. Voici la liste complète des hooks disponibles.

## Actions : `plugins_loaded`

L'initialisation du plugin - chargement des shortcodes et admin.

```php
add_action('plugins_loaded', function() {
    $loader = new Portal_Loader(PORTAL_FILE);
    $loader->init();
});
```

## Actions : `admin_menu`

Ajoute la page de paramètres Portal.

```php
add_action('admin_menu', function() {
    add_menu_page(...);
});
```

## Actions : `admin_init`

Registre les options et paramètres.

```php
add_action('admin_init', function() {
    register_setting('portal_settings_group', 'portal_settings');
});
```

## Filtrés : `plugin_action_links_{$basename}`

Ajoute un lien "Paramètres" sur la page des plugins.

```php
add_filter("plugin_action_links_{$portal_basename}", function($links) {
    $links[] = '<a href="' . admin_url('admin.php?page=portal-settings') . '">Paramètres</a>';
    return $links;
});
```

## Filtrés : `plugin_row_meta`

Ajoute un lien "Documentation" sur la ligne du plugin.

```php
add_filter('plugin_row_meta', function($links, $file) {
    if ($plugin_basename === $file) {
        $links[] = '<a href="https://github.com/norico/portal/docs/" target="_blank">Documentation</a>';
    }
    return $links;
});
```

## Courtecodes : `portal_feed`

Rendre le shortcode `portal_feed` avec ses attributs personnalisés.

```php
add_shortcode('portal_feed', function($atts) {
    // Vostre traitement
});
```
