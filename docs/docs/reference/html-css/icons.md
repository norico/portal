---
title: Icônes et visuels
prev: /reference/html-css/hooks.md
---

# Icônes et visuels

## Icônes SVG dans l'interface admin

### ✓ Signale un feed valide

L'icône de vérification (verte) apparaît à droite du champ d'URL :

```html
<span class="portal-input-check" title="Feed RSS valide détecté">✓</span>
```

### Effet visuel

- **Taille** : 18px
- **Couleur** : `#4CAF50` (Vert WordPress)
- **Position** : Absolute, droite du champ
- **Alignement** : Centré verticalement

### Rendre de l'icône

L'icône s'affiche automatiquement quand le feed est valid.

## Visuels en feed

Les vignettes sont récupérées dans l'ordre suivant :

### 1. Media RSS (priorité)

```xml
<media:content url="https://..." medium="image" width="1200" height="800" />
<media:thumbnail url="https://..." width="300" height="200" />
```

- **Avantage** : Sans appel HTTP, instantané
- **Usage** : Prendra le premier match

### 2. WordPress REST API

```php
/wp-json/wp/v2/posts/{id}?_embed
```

- **Cache** : 300s (5 min) via transients
- **Avantage** : Plus fiable que Media RSS
- **Fallback** : ID via `wp:post_id`, GUID, ou URL

### 3. RSS Enclosure

```xml
<enclosure url="https://..." type="image/jpeg" />
```

- **Optionnel** : Via attribut `fallback_image="true"`
- **Usage** : Dernier recours

## Classes utilitaires

### `.portal-feed-item-thumbnail`
- `aspect-ratio: 16/9` - Ratio d'image
- `object-fit: cover` - Remplissage
- `max-width: 100%` - Responsive
- `border-radius: 4px` - Coins arrondis

### `.portal-feed-item`
- `margin-bottom: 30px` - Espacement inferieur
- `padding-bottom: 20px` - Padding interne
- `border-bottom: 1px solid #eee` - Séparation
