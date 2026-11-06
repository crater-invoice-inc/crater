## Module "Calculateur" — budgetisation de mandat

Nouvelle branche `feature/calculateur`. Le module remplace le fichier Excel : un "calculateur" = un mandat budgété, sauvegardé et modifiable, composé de lignes réparties en 5 catégories, avec totaux calculés en temps réel.

### Modèle de données

**Table `calculators`** (en-tête du mandat)
- `id`, `company_id` (FK companies, cascade), `creator_id`
- `name` (nom du mandat/client), `notes` (texte libre, nullable)
- timestamps

**Table `calculator_items`** (lignes de tous types)
- `id`, `calculator_id` (FK cascade), `company_id`
- `category` — string dans : `pre_production`, `production`, `post_production`, `travel`, `production_expenses`
- `item_name` (role / tâche / matériel…)
- `unit_price` — integer cents (comme `items.price` dans Crater)
- `quantity` — decimal(15,2) (heures, kms, jours…)
- `type` — `role` | `task` | `cost` (pour unité affichée : heures vs km vs jour ; optionnel, défaut `cost`)
- timestamps

Totaux calculés côté client (somme `unit_price × quantity` par catégorie + total général), cohérent avec la logique Excel (coût seulement, pas de marge pour l'instant).

### Backend (patterns copiés sur le module Items)

1. Migration `xxxx_create_calculators_table.php` (+ items dans la même migration ou seconde).
2. `app/Models/Calculator.php` — `$guarded=['id']`, casts, `scopeWhereCompany` (header `company`), `createCalculator($request)` / `updateCalculator($request)` qui sauvegardent les lignes en masse (delete + re-insert ou sync par id), relation `items()`.
3. `app/Http/Requests/CalculatorsRequest.php` — validation (name requis, items: array, chaque item avec category in:…, item_name requis, unit_price/quantity numériques).
4. `app/Http/Resources/CalculatorResource.php` (avec les items inclus).
5. `app/Http/Controllers/V1/Admin/Calculator/CalculatorsController.php` — index (paginé, filtrable), store, update, show, delete (bulk ids comme Items).
6. `routes/api.php` — `Route::apiResource('calculators', ...)` dans le groupe `['auth:sanctum','company']` → `['bouncer']`.
7. Menu : entrée dans `config/crater.php` `main_menu` (titre i18n `navigation.calculator`, lien `/admin/calculators`) + clé `navigation.calculator` dans `resources/scripts/locales/en.json` et `fr.json` ("Calculateur").

### Frontend

1. Store Pinia `resources/scripts/admin/stores/calculator.js` (axios inline, pattern `item.js`).
2. Vues `resources/scripts/admin/views/calculator/` :
   - `Index.vue` — liste des mandats (nom, total, date), actions voir/éditer/supprimer.
   - `Create.vue` (gère create + edit via `:id`) — formulaire avec 5 sections/p卡片 (pré-production, production, post-production, frais de déplacement, dépenses de production) ; ajout/suppression de lignes par section ; `<BaseMoney>` (v-money3) pour les prix ; totaux par catégorie et total général mis à jour en direct ; bouton Enregistrer.
3. Routes dans `resources/scripts/admin/admin-router.js` (`/admin/calculators`, `/admin/calculators/create`, `/admin/calculators/:id/edit`).

### Hors périmètre (plus tard si tu veux)
- Conversion du calcul vers un devis/estimate existant de Crater.
- Champ marge % pour calculer le prix de vente.
- Export PDF du budget.

Vérification : migrations lancées, build front (`yarn build` ou `npm run build`), test manuel des routes API si possible.