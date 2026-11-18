---
title: Utiliser Portal
prev: /configuration/
next: /reference/
---

# Utilisation

## Exemple de base

Utilisez le shortcode `portal_feed` n'importe où dans votre page, publication, ou widget :

```
[portal_feed]
```

## Options du shortcode

Le shortcode supporte les attributs ci-dessous.

| Attribut | Par défaut | Description |
|----------|------------|-------------|
| `limit` | 10 | *Nombre d'articles par page* |
| `page` | 1 | *Page d'affichage initiale* (gagné par `?portal_page=N`) |
| `show_date` | `true` | *Afficher la date de publication* |
| `show_source` | `true` | *Afficher l'URL du site source* |
| `show_thumbnail` | `true` | *Afficher l'image de vignette* |
| `fallback_image` | `false` | *Utiliser la media RSS enclosure en image de backup* |
| `use_api` | `true` | *Récupérer les vignettes via l'API REST WordPress* |
| `excerpt_length` | 150 | *Longueur de l'excerpt en caractère* |
| `class` | `portal-feed` | *Classe CSS personnalisée pour le conteneur* |

## Exemples d'utilisation

### Afficher les 5 articles sans date

```
[portal_feed limit="5" show_date="false"]
```

### Vidéo thumbnail uniquement

```
[portal_feed show_thumbnail="true" show_date="false" show_source="false"]
```

### Foire de 8 caractères

```
[portal_feed excerpt_length="8 class="my-custom-feed"]
```

### Pagination avec 20 articles par page

```
[portal_feed limit="20"]
```

## Pagination

Lorsque les articles dépassent le nombre spécifié dans `limit`, la pagination est activée automatiquement.

### Navigation

Les liens de pagination utilisent le paramètre de requête `portal_page` :

- Page 1 : `?portal_page=1`
- Page 2 : `?portal_page=2`
- etc.

### Exemple

```
https://vostordomain.com/ma-page.php?portal_page=2
```

<figure>
<figcaption class="image-caption" style="color: #666; font-size: 0.9em;">Exemple de pagination</figcaption>
</figure>

## Intégration personnalisée

Vous pouvez créer votre propre attribut de shortcode en modifiant le fichier `class-shortcode.php`.

### Ajout d'un attribut personnalisé

1. Ouvrez `portal/includes/class-shortcode.php`
2. Trouvez le shortcode `shortcode_atts()` 
3. Ajoutez votre nouveaux attributs au tableau

Exemple :
```php
$atts = shortcode_atts( [
    'limit'          => $limit,
    'page'           => 1,
    'show_date'      => 'true',
    // ... votre attribut personnalisé
], $atts, 'portal_feed' );
```

## Intégration avec WooCommerce

Pour afficher les dernières publications de votre boutique :

```
[portal_feed limit="3" class="woocommerce-feed"]
```

## Intégration avec des systèmes de third-party

### Google Custom Search

Utilisez le shortcode Portal pour enrichir vos résultats de couverture :

```
[portal_feed limit="10" show_source="false"]
```

Réformulez vos mots-clés : `https://www.google.com/search?q=plat`
