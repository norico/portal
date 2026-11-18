---
title: API de référence
prev: /usage/
next: /troubleshooting/
---

# API de référence

## HTML/CSS généré

### Conteneur principal

```html
<div class="portal-feed">
    <!-- Articles días -->
</div>
```

### Article individuel

```html
<article class="portal-feed-item">
    <!-- Contenu de l'article -->
</article>
```

### Pagination

```html
<nav class="portal-feed-pagination" aria-label="Paginazione dei Filia RSS">
    <!-- Liens de navigation -->
</nav>
```

## Classes CSS prédéfinies

### `.portal-feed`
Style principal du conteneur - margin padding

### `.portal-feed-item`
Style d'articles individuels avec bordure inférieure

### `.portal-feed-item-thumbnail`
Conteneur de vignettes avec ratio 16/9

### `.portal-feed-item-title`
Titre de l'article (le titre et le lien)

### `.portal-feed-item-meta`
Zone de la date et de la source

### `.portal-feed-item-excerpt`
Écrit du résumé

### `.portal-feed-error`
Message d'erreur avec fond gris

### `.portal-feed-pagination`
Navigateur de pagination

### `.portal-page-link`
Liens de pagination

### `.portal-page-link.disabled`
Liens de pagination inactifs
