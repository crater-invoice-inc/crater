# Exécuter et développer le projet Crater en local

> **À lire avant toute action.** Ce document décrit la façon dont CE projet fonctionne réellement en local.
> Ne pas suggérer de lancer `npm run dev` : ce n'est pas le mode de fonctionnement de ce projet.

## Stack

- Backend : Laravel 8 (PHP 8.1), base MariaDB
- Frontend : Vue 3 + Vite 2 (paquet `laravel-vite` / `innocenzi/laravel-vite`)
- Infra locale : Docker Compose (services `app`, `nginx`, `db`, `cron`)

## Règle n°1 : pas de serveur de dev Vite

L'application sert les assets **pré-construits** depuis `public/build` (via le manifest lu par `laravel-vite`).
Il n'y a **pas** de flux `npm run dev` à utiliser. Pour voir une modification frontend :

1. Rebuild du frontend (voir ci-dessous) ;
2. Rechargement de la page sur `http://localhost` (nginx du Docker Compose).

## Environnement local

- Tout tourne via Docker Compose : `docker compose up -d` puis `http://localhost`.
- **PHP n'est PAS installé sur la machine hôte.** Toute commande artisan passe par le conteneur :
  ```bash
  docker compose exec -T app php artisan <commande>
  ```
- Base de données : conteneur `db` (MariaDB), port hôte 33006.

## Rebuild du frontend (nécessaire après toute modif JS/CSS)

Le frontend se build avec **yarn** (le `yarn.lock` fait foi ; le `package-lock.json` présent est obsolète — ne pas utiliser `npm ci`, il échoue avec des erreurs ERESOLVE/lockfile incohérent).

Le build appelle `php artisan vite:config` pour découvrir les entrypoints, donc il a besoin de PHP.
Sur l'hôte où PHP est absent, utiliser un shim qui délègue au conteneur :

```bash
mkdir -p /tmp/phpshim
printf '#!/bin/sh\ndocker compose -f /chemin/vers/crater/docker-compose.yml exec -T app php "$@"\n' > /tmp/phpshim/php
chmod +x /tmp/phpshim/php
PATH="/tmp/phpshim:$PATH" yarn build   # ou: npm run build
```

Astuce environnement : Node 16 est la version sûre pour Vite 2 (les versions récentes peuvent produire des chunks différents).

## Pièges connus du build

- **Icônes heroicons** : `BaseIcon` résout les icônes dynamiquement par leur nom via
  `resources/scripts/components/hero-icons.js` (imports explicites). Ne pas repasser à un
  `import * as heroIcons from '@heroicons/vue/outline'` : le tree-shaking de Rollup supprime
  alors les icônes uniquement référencées par leur nom → icônes manquantes **en prod uniquement**
  (le dev masque le problème car il n'y a pas de tree-shaking en dev).
- Ne pas retirer `shrink-0` des classes d'icônes de `BaseIcon`/`PencilSheetIcon` : sans lui, les
  icônes des menus déroulants s'écrasent à 0px quand un libellé est long.

## Déploiement prod (automatique)

- `public/build` n'est **pas versionné** dans git. À chaque push sur `master`, le workflow
  `.github/workflows/deploy.yml` : build du frontend dans GitHub Actions → `git pull` sur le
  droplet + upload scp des assets avec swap atomique (`build-new` → `build`).
- Aucune manipulation manuelle sur la VM. Ne pas committer `public/build`.

## Mail

- La prod utilise Brevo en SMTP (`smtp-relay.brevo.com`, port **2525** — le port 587 est bloqué
  par DigitalOcean). Login SMTP : `c181c3001@smtp-brevo.com` + clé SMTP (pas le mot de passe du
  compte Brevo). La config se règle dans l'UI : Paramètres → Configuration des emails.
- Le bouton des courriels facture/devis pointe directement vers le PDF (`?pdf=1`) et son libellé
  est traduit selon le réglage `language` de la société.

## Comptes / auth en local

- Créer ou réinitialiser un mot de passe via artisan tinker, **en passant le mot de passe en clair**
  (le modèle `User` hache automatiquement via un mutateur — ne jamais passer `bcrypt(...)` soi-même,
  sinon double hachage et connexion impossible) :
  ```bash
  docker compose exec -T app php artisan tinker --execute="
    \$u = \Crater\Models\User::where('email','...')->first();
    \$u->password = 'motdepasse'; \$u->save();"
  ```

## Branches et déploiement

- Travailler sur une branche feature ; le push sur `master` déclenche le déploiement prod.
- CI : les assets frontend sont construits par GitHub Actions (Node 16 + yarn), pas localement,
  pas sur la VM.
