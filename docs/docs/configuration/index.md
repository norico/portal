---
title: Configuration
prev: /installation/
next: /usage/
---

# Configuration

L'interface de configuration du plugin permet d'ajouter et de gérer les sites WordPress dont vous souhaitez récupérer les fils RSS.

## Interface d'administration

Accédez à **Réglages → Portal** pour ouvrir la page de configuration.

### Onglet Général

L'onglet **Général** permet de :

1. Ajouter les URL des sites WordPress
2. Vérifier la connectivité des fils RSS
3. Visualiser les erreurs de connexion

#### Ajouter un site WordPress

1. Dans le champ **URL du site WordPress**, saisissez l'URL de base du site (ex: `https://example.com`)
2. Le plugin détecte automatiquement le feed RSS du site (ex: `/feed/`, `/feed/rss/`, etc.)
3. Si le feed est valide, une **✓** verte apparaît à droite
4. Un bouton **RSS** permet d'ouvrir directement le feed

#### Sauvegarder

Cliquez sur **Sauver les paramètres** pour enregistrer les changements.

**Note** : Lors de la sauvegarde, si vous avez configuré au moins un site avec un feed RSS valide, un message de succès s'affiche avec le compte des sites valides :

- "Paramètres sauvegardés. 1 site avec feed RSS valide."
- "Paramètres sauvegardés. 3 de 4 sites ont un feed RSS valide."
- "Avertissement : Aucun des sites entrés n'a de feed RSS valide."

## Validation automatique des feeds

Lors de l'ajout d'une URL de site, le plugin exécute automatiquement :

1. **Test HTTP** : Vérifie le code de réponse (doit être 200)
2. **Contenu-type** : Vérifie que le response contient `application/rss+xml`, `application/xml`, ou `text/xml`
3. **Marqueurs RSS/XML** : Cherche les balises `<rss>`, `<feed>`, `<channel>`, ou `<?xml?>` dans le corps

### Feeds testés

Le plugin teste plusieurs paths possibles, dans l'ordre :

- `/feed/`
- `/feed/rss/`
- `/feed/rss2/`
- `/rss/`
- `/rss2/`
- `/wp-rss.php`
- `/wp-feed.php`
- `?feed=rss`
- `?feed=rss2`

<figure>
<img src="" alt="" width="700" style="margin: 0 auto 1em;" />
<figcaption class="image-caption" style="color: #666; font-size: 0.9em;">Exemple de validation de feed</figcaption>
</figure>

## Gestion des erreurs

### Feed invalide

**Message** : "Aucun feed RSS valide trouvé pour : https://example.com"

**Raison** :连接到 Feed n'a pas répondu ou le contenu n'est pas un feed RSS/XML valide.

**Solution** :
1. Vérifiez que le site est bien en ligne et accessible
2. Assurez-vous que WordPress n'a pas de restriction HTTP pour les headers
3. Comparez avec une URL directement l'URL du feed (ex: `https://example.com/feed/`) dans votre navigateur

### Erreur de connexion

**Message** : "HTTP 403"

**Solution** :
1. Vérifiez que le plugin est bien `https://` pour le référencement HTTPS
2. Contactez l'administrateur du site pour enlever les restrictions sur les robots
3. Vérifiez que le plugin **Maintenance mode**, **Cloudflare**, n'est pas bloqué
