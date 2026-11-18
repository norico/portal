
export default ({
  lang: 'fr',
  title: "Portal - Documentation",

  description: "WordPress news federator — agréger fils RSS de plusieurs sites WordPress en un seul shortcode",

  // Favoris (bundles)
  // tailwindcss: true,

  base: '/docs/',

  // Theme
  themeConfig: {
    logo: '/readme-logo.png',

    socialLinks: [
      { icon: 'github', link: 'https://github.com/norico/portal', title: 'GitHub' },
    ],


    // Génération de l'URL du titre du plugin
    // siteTitle: 'Portal - WordPress RSS Aggregator',

    // Sidebar
    sidebar: [
      {
        text: 'Documentation',
        // Tab Index
        items: [
          {
            text: 'Accueil',
            link: '/',
          },
          {
            text: 'Installation',
            link: '/installation/',
            collapsible: true,
            items: [
              { text: 'Installation rapide', link: '/installation/' },
              { text: 'Vérification post-installation', link: '/installation/installation-verification/' },
              { text: 'Résolution de problèmes', link: '/installation/trouvez/' },
            ],
          },
          {
            text: 'Configuration',
            link: '/configuration/',
            collapsible: true,
            items: [
              { text: 'Interface d\'administration', link: '/configuration/' },
              { text: 'Validation des feeds', link: '/configuration/validation/' },
              { text: 'Gestion des erreurs', link: '/configuration/errors/' },
            ],
          },
          {
            text: 'Utilisation',
            link: '/usage/',
            collapsible: true,
            items: [
              { text: 'Shortcodes', link: '/usage/' },
              { text: 'Attributs', link: '/usage/attributes/' },
              { text: 'Intégrations', link: '/usage/integrations/' },
            ],
          },
          {
            text: 'Référence',
            link: '/reference/',
            collapsible: true,
            items: [
              { text: 'HTML généré', link: '/reference/html/' },
              { text: 'Classes CSS', link: '/reference/css/' },
              { text: 'Hooks et filtres', link: '/reference/hooks/' },
              { text: 'Icônes', link: '/reference/icons/' },
            ],
          },
          {
            text: 'Résolution de problèmes',
            link: '/troubleshooting/',
            collapsible: true,
            items: [
              { text: 'Aucun site configuré', link: '/troubleshooting/#aucun-site-configuré' },
              { text: 'Aucun feed RSS valide', link: '/troubleshooting/#aucun-feed-rss-valide-trouvée' },
              { text: 'Images ne s\'affichent pas', link: '/troubleshooting/#images-ne-saffichent-pas' },
              { text: 'Pagination', link: '/troubleshooting/#pagination-ne-fonctionne-pas' },
              { text: 'Performances', link: '/troubleshooting/#performance' },
            ],
          },
        ],
      },
    ],

    // Navigation
    nav: [

    ],

    // Vite.js
    outline: [2, 3],

    // Footer
    footer: {
      message: 'Generated with Vitepress',
      copyright: 'Copyright 2025 norico. Documentals under GNU GPLv2.',
    },

    // Recherche
    search: {
      provider: 'local',
    },
  },

  // Scopt
  vite: {
    optimizeDeps: {
      // Rolling (30 judges)
      noCache: true,
    },
  },
})
