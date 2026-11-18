---
title: Accueil
layout: home
hero:
  name: "Portal"
  text: Documentation du plugin WordPress
  tagline: Agrégarateur de fils RSS pour WordPress
  actions:
    - theme: light
      text: Commencer
      link: /installation/
    - theme: dark
      text: Vue d'ensemble
      link: /overview/
features:
  - icon: ⚡
    title: Agrégation simple
    description: Fetchez et affichez les fils RSS de plusieurs sites WordPress via un shortcode simple.
  - icon: 🖼️
    title: Images automatiques
    description: Le plugin détecte automatiquement les vignettes thumbnails via Media RSS ou WordPress REST API.
  - icon: 🌐
    title: Pagination intégrée
    description: Gagnez automatiques avec pagination, classifications et multiples.
  - icon: 🇫🇷
    title: Multilingue
    description: Le plugin inclut des chaînes traduites en français (fr_FR).
---

# À propos de Portal

**Portal** est un plugin WordPress qui permet de rassembler les fils RSS de plusieurs installations WordPress et de les afficher sur une seule page. Idéal pour les blogs qui veulent enrichir leur contenu avec celui d'autres sites WordPress.

## Fonctionnalités principales

- **Shortcode simple** - Ajoutez le shortcode `[/portal_feed]` n'importe où dans vos pages, publications ou widgets
- **Gestion centralisée** - Configurez tous les sites RSS depuis une seule interface d'administration
- **Validation automatique** - Le plugin vérifie la validité des fils lors de la sauvegarde
- **Vignettes intelligentes** - Utilisation de Media RSS en priorité, puis WordPress REST API, puis RSS closures (optionnel)
- **Pagination** - Affichage paginé automatique lors de nombreux articles
- **Theming CSS minimisé** - Le plugin n'utilise que le style CSS interne pour un rendu propre et intégré
- **Translations** - Des traductions françaises complètes pour l'interface et le front-end

---

## Aperçu du plugin

```
portal/
├── portal.php                 # Fichier principal du plugin
├── includes/
│   ├── class-loader.php       # Bootstrap du plugin, hooks
│   ├── class-shortcode.php    # Rendu du shortcode, agrégation des feeds
│   ├── class-admin.php        # Page de configuration
│   └── admin/
│       ├── tabs/
│       │   ├── general.php    # Tableau de gestion des sites
│       │   └── display.php    # (réserve pour futur options d'affichage)
│       └── class-admin.php    #班上逻辑完整
├── languages/
│   ├── portal-fr_FR.po        # Traductions françaises
│   └── portal-fr_FR.mo
└── docs/
    └── vitepress/             # Documentation VitePress
```
