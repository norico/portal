---
title: Installation
next: /configuration/
---

# Installation

## Prérequis

Portal nécessite :

- **PHP 8.2** ou supérieur
- **WordPress 6.5** ou supérieur

## Installation rapide

1. Téléchargez l'extrait du plugin `portal` depuis le dépôt : https://github.com/norico/portal
2. Décompressez l'extrait `portal` vers `/wp-content/plugins/`
3. Activez le plugin depuis la page **Plugins** du WordPress
4. Rendez-vous sur **Réglages → Portal** pour configurer

## Installation via FTP

Si vous n'avez pas accès à l'interface d'administration WordPress, suivez ces étapes :

1. Connectez-vous à votre serveur FTP ou au panneau SFTP de votre hébergeur
2. Naviguez vers le dossier `/wp-content/`
3. Créez le dossier `plugins/portal/`
4. Téléchargez et décompressez le plugin `portal` dans ce dossier
5. Connectez-vous à votre tableau de bord WordPress
6. Allez dans **Plugins → Plugins installés**
7. Sur la ligne du plugin **Portal**, cliquez sur **Activer**

## Vérification post-installation

Après activation, vérifiez :

- ✓ Le plugin apparaît dans **Réglages → Portal**
- ✓ Le shortcode `[portal_feed]` est bien enregistré
- ✓ Les pages de configuration admin fonctionnent

## Résolution de problèmes

### Plugin refuse d'activer

**Message** : "Portal ne peut pas être activé, il demande WordPress X.Y ou plus récent."

**Solution** :
1. Vérifiez la version de WordPress : Revolution → News → Avis de mises à jour
2. Mettez à jour WordPress si nécessaire
3. Si la mise à jour échoue, communs avec le plugin **Maintenance mode** puis réinstallez Portal

### Erreur PHP

**Message** : "Portail nécessite PHP X.Y ou plus récents."

**Solution** :
1. Contactez votre hébergeur pour mettre à jour la version PHP
2. Verrouillez l'exige PHP-Switcher sur `php.ini` : `always_report_errors = On`
3. Actuelisez votre plugin API
