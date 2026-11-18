---
title: Résolution de problèmes
prev: /reference/
---

# Résolution de problèmes

## "Aucun site configuré"

### Symptômes

Le shortcode affiche "Aucun site configuré. Veuillez ajouter des sites WordPress dans les paramètres Portal."

### Cause

Aucun site n'a été saisi dans **Réglages → Portal**.

### Solution

1. Rendez-vous sur **Réglages → Portal** -> **Général**
2. Ajoutez au moins une URL de site WordPress
3. Vérifiez que le feed RSS est détecté (icône ✓)
4. Cliquez sur **Sauver les paramètres**

---

## "Aucun feed RSS valide trouvé"

### Symptômes

Le shortcode affiche "Aucun feed RSS valide trouvé pour les sites configurés."

### Causes possibles

1. Les sites WordPress sont hors ligne ou en maintenance
2. Les feeds RSS sont désactivés
3. Le plugin bloque la connexion

### Solution

#### Vérifier que le feed est accessible

1. Complétez l'URL du feed : `https://vostodomain.com/feed/`
2. Ouvrez cette URL dans votre navigateur
3. Vérifiez que le contenu XML/RSS s'affiche

#### Dépasser un feed invalide

1. Vérifiez que le plugin n'est pas bloqué par la **Maintenance Mode**, **Cloudflare**, ou **WAF**
2. Contactez l'administrateur du site pour enlever les restrictions HTTP
3. Testez l'accès direct via un plugin comme **W3 Total Cache**

---

## Images ne s'affichent pas

### Symptômes

Les vignettes thumbnails ne sont pas chargées ou sont affichées par défaut.

### Causes possibles

1. Les sites n'ont pas de vignettes Thumbnails
2. Media RSS est bloqué
3. REST API est désactivée

### Solution

```
[portal_feed fallback_image="true"]
```

Cliquez sur `fallback_image="true"` pour activer l'affichage des vignettes RSS en secours.

---

## Pagination ne fonctionne pas

### Symptômes

Les liens `?portal_page=2` ne fonctionnent pas ou chargent la bonne page

### Cause

Un plugin de cache (W3 Total Cache, WP Super Cache, WP Rocket) cache la page sans prendre en compte le paramètre.

### Solution

Configurez vos plugins de cache pour :

1. **W3 Total Cache** : Excluez le paramètre `portal_page` du cache
2. **WP Super Cache** : Cible les pages avec `?portal_page=...`
3. **WP Rocket** : Configurez les règles de cache ou inclusions

---

## Date ne s'affiche pas

### Symptômes

La date n'apparaît pas dans le feed.

### Solution

Utilisez le shortcode avec `show_date="false"` pour masquer la date :

```
[portal_feed show_date="true"]
```

---

## Feed n'a pas de thumbnail

### Solution

1. Vérifiez que l'article source a une image de vignette
2. Vérifiez que le site supporte Media RSS ou le REST API WordPress
3. Activez `fallback_image="true"` si nécessaire :

```
[portal_feed fallback_image="true"]
```

---

## Performance

### Feed lent à charger

**Cause** : Le plugin récupère tous les articles des feeds sur chaque consultation.

**Solution** :

1. **Limitez le nombre d'articles par page** :
   ```
   [portal_feed limit="5"]
   ```

2. **Utilisez le cache DNS** :
   Configurez **WP Super Cache** ou **W3 Total Cache** pour minimiser le feed.

3. **Utilisez un load balancer** :
   Placez les feeds connectés à une frontière de sécurité ou à un CDN.

---

## Erreur 403 Forbidden

### Cause

Le site refuse la connexion via le plugin.

### Solution

1. Vérifiez que le plugin ne reçoit pas d'erreurs HTTP
2. Désactivez les restrictions HTTP pour les bots
3. Contactez l'administrateur du site

---

## Duplication des articles

### Symptômes

Même article s'affiche multiple fois.

### Cause

Plusieurs feeds pointent vers le même issu ou la configuration duplique les articles.

### Solution

1. Modifiez les feeds pour éviter les duplications
2. Vérifiez les paramètres pour ne pas avoir les feeds en double
3. Utilisez un unique feed par site
