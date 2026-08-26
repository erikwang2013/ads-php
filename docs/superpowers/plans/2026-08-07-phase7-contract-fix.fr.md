# Phase 7 : Réparation des contrats inter-plateformes — Plan d'implémentation

[中文](docs/superpowers/plans/2026-08-07-phase7-contract-fix.md) | [English](docs/superpowers/plans/2026-08-07-phase7-contract-fix.en.md) | [한국어](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ko.md) | [Русский](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-07-phase7-contract-fix.de.md) | [Français](docs/superpowers/plans/2026-08-07-phase7-contract-fix.fr.md) | [Español](docs/superpowers/plans/2026-08-07-phase7-contract-fix.es.md) | [Português](docs/superpowers/plans/2026-08-07-phase7-contract-fix.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-07-phase7-contract-fix.hi.md) | [العربية](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-07-phase7-contract-fix.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-07-phase7-contract-fix.id.md) | [日本語](docs/superpowers/plans/2026-08-07-phase7-contract-fix.ja.md)

> **Mise à jour de statut (2026-08-16) :** Task 1 ✅ / Task 2 ✅ / Task 3 ✅ / Task 4 ✅ toutes terminées, validation de régression par le tester réussie (35 tests OK, vérification croisée des contrats sans point de terminaison fantôme, Phase 7 acceptée).

**Objectif :** Corriger les problèmes de contrat API inter-plateformes découverts par l'audit de l'équipe : 3 points de terminaison fantômes Flutter (404), bug de double préfixe `admin.ts` de l'Admin, `/system/info` sans route, ServiceProxy non câblé, documentation obsolète. Rétablir une consommation cohérente de l'API service par les trois clients (Admin/Flutter/HarmonyOS).

**Source :** Audit parallèle de l'équipe du 2026-08-07 (backend-dev inventaire des routes : 61 points de terminaison, vue-dev inventaire des appels Admin : 50 points d'appel, mobile-dev inventaire mobile, researcher comparaison croisée implémenté/planifié)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3 + TS, Dart 3 (Riverpod/Dio), ArkTS

---

## Task 1 : Réparer les points de terminaison fantômes Flutter (🔴 priorité maximale)

### Contexte
3 pages Flutter appellent des routes inexistantes côté service, toutes en 404 :

| Appel Flutter | Route réelle du service | Solution |
|---|---|---|
| `GET /dashboard` | aucune (le résumé du tableau de bord est dans `/reports/summary`) | passer à `GET /reports/summary` |
| `GET /alerts` | aucune (les alertes sont dans `/alerts/rules`, `/alerts/logs`, `/alerts/unread-count`) | passer à `GET /alerts/logs` (sémantique de liste d'alertes) |
| `GET /reports` | aucune (les rapports sont dans `/reports/summary`, `/reports/custom`) | passer à `GET /reports/custom` (avec paramètres date/dimension/métrique, correspond à ReportBuilder::buildCustom) |

### Files:
- Modify: `apps/flutter/lib/features/dashboard/dashboard_page.dart`（`/dashboard` → `/reports/summary` ×2 intervalles, adaptation à la structure de réponse `data.overview`/`by_platform`/`daily`）✅
- Modify: `apps/flutter/lib/features/alert/alert_page.dart`（`/alerts` → `/alerts/logs`, adaptation à la structure paginée `data.list`, champs AlertLog rule_name/metric/current_value/condition/threshold）✅
- Modify: `apps/flutter/lib/features/report/report_page.dart`（`/reports` → `/reports/custom`, paramètres date_start/date_end/dimensions[]/metrics[], analyse de `data.list`, champ cost）✅
- Verify: champs de réponse cohérents avec les retours réels de `service/plugin/ads-api/controller/v1/DashboardController.php` / `AlertController.php` / `ReportBuilder.php` ✅

### Critères d'acceptation
- [x] Les trois modifications de chemin terminées, paramètres de requête conservés (paramètres de date de la page report → date_start/date_end + dimensions/metrics) ✅
- [x] L'analyse des réponses alignée sur la structure JSON réelle du backend (overview / liste paginée / liste custom) ✅
- [x] `flutter analyze` sans erreur après modification — le cache SDK Flutter de cet environnement est en lecture seule, impossible à exécuter ; remplacé par `dart analyze` intégré au SDK sur tout le projet : **0 erreurs** (les 15 avertissements existants préexistaient aux modifications, aucun nouveau problème introduit) ✅

---

## Task 2 : Réparer le bug de double préfixe `admin.ts` de l'Admin

### Contexte
- Les chemins de `admin/public/web/src/api/admin.ts` sont écrits `/api/admin/...`, alors que le baseURL axios est déjà `/api`（`src/api/index.ts`), ce qui donne réellement `/api/api/admin/...` — les 5 appels de UserManage.vue / AuditLog.vue sont probablement en 404.
- **Problème architectural profond (confirmé par le rapport final de vue-dev)** : le backend admin (8789) fournit lui-même 12 routes locales (`/api/admin/login`, `me`, `logout`, CRUD `users`, `roles`, `audit-logs`, `/api/install/*`), mais :
  - `location /api/` de `docker/nginx/admin.conf` proxy_pass **toutes** vers `service_api` (php:8788) ;
  - `upstream admin_backend` (admin-php:8789) est bien défini, mais **aucune location ne le référence** → en production `/api/admin/*` n'atteint jamais 8789 ;
  - Le proxy de dev Vite pointe également tout `/api` vers 8788.
  - Conclusion : même en corrigeant le double préfixe, `/api/admin/*` serait toujours en 404 — les routes locales du backend admin ne sont pas câblées dans la chaîne de production.

### Point de décision (à confirmer par backend-dev + vue-dev + devops)
- Option A (recommandée) : vue-dev change les chemins de `admin.ts` en relatifs `/admin/users`, `/admin/audit-logs`, et **devops ajoute `location /api/admin/` → `proxy_pass http://admin_backend` dans Nginx** (placé avant `location /api/`, correspondance de préfixe exacte prioritaire), pour que les routes dédiées admin soient servies directement par 8789, tandis que les routes métier restent sur 8788
- Option B : backend-dev ajoute les routes `/api/admin/*` dans service (chevauchenent de responsabilités avec le panneau Admin, non recommandé)
- Option C : les requêtes métier passent aussi par ServiceProxy (nécessite un câblage, changement le plus important, à considérer uniquement si une authentification unifiée côté admin est requise)

### Files:
- Modify: `admin/public/web/src/api/admin.ts`（supprimer le préfixe `/api`）
- Modify: `docker/nginx/admin.conf`（ajouter `location /api/admin/` → upstream admin_backend）
- Modify: `admin/public/web/vite.config.ts`（le proxy de dev ajoute la règle `/api/admin` → 8789, placée avant `/api`）
- Verify: les routes du backend admin dans `admin/config/route.php`（/api/admin/users etc.）correspondent aux appels front-end

### Critères d'acceptation
- [x] Les chemins de requête front-end cohérents avec les routes backend réellement existantes (pas de 404) — les 9 méthodes de admin.ts toutes vérifiées contre route.php ✅, vue-tsc passe
- [x] Nginx / Vite savent tous deux router correctement `/api/admin/*` vers 8789, le reste de `/api/*` vers 8788 — Nginx a ajouté `location /api/admin/`, Vite a ajouté le proxy `/api/admin` (placé avant `/api`) ✅
- [x] Les pages UserManage / AuditLog fonctionnent — chemins alignés (y compris la décision listRoles → `/admin/users/roles`) ✅

---

## Task 3 : `/system/info` sans route + décision ServiceProxy

### Contexte
- `SystemInfo.vue` / `stores/admin.ts` appellent `GET /api/system/info`, le service n'a pas cette route (uniquement /health, /ping), le 404 est avalé par try/catch
- `admin/app/controller/ServiceProxy.php` est défini mais a 0 appelant actif dans tout le dépôt (« défini mais non câblé »)

### Point de décision
- `/system/info` : Option A — le front-end appelle `/health` (déjà présent dans service) ; Option B — backend-dev ajoute le point de terminaison `/api/system/info` dans service (renvoie les informations de version/environnement, utile aussi pour HarmonyOS/Flutter, recommandée)
- ServiceProxy : Option A — le câbler aux APIs dédiées admin dont l'admin a besoin (par exemple le relais des journaux d'audit) ; Option B — supprimer la classe et mettre à jour la documentation pour déclarer « Admin se connecte directement au service » (architecture actuelle réelle)

### Exécuté (2026-08-16)
- **`/system/info` → Option A (le front-end bascule sur `/health`)** : SystemInfo.vue utilise axios natif pour appeler `GET /health`, évalue `checks.database === 'ok'` ; la route `/health` ne porte pas de préfixe `/api` côté service, Vite a ajouté le proxy `/health`, `location /health` existait déjà dans Nginx ; le code mort de `stores/admin.ts` bascule aussi vers `/health` ✅
- **ServiceProxy → Option B (conservé + documentation)** : la classe est conservée comme infrastructure de réserve（`ServiceProxy::init()` s'auto-initialise sans danger), le commentaire de `admin/config/app.php` est mis à jour en « infrastructure de réserve, aucun appelant actif actuellement » ✅

### Critères d'acceptation
- [x] Décision `/system/info` appliquée : le front-end a supprimé l'appel (bascule sur /health), plus de requête fantôme 404 ✅
- [x] Décision ServiceProxy appliquée : classe conservée avec commentaire de config expliquant l'état actuel ✅

---

## Task 4 : Rétro-remplissage de la documentation et unification des consignes

### Contexte
- README « 14 contrôleurs / 45+ points de terminaison » obsolète (en réalité 17 contrôleurs / 61 points de terminaison)
- Les cases à cocher des phases de `docs/superpowers/plans/` non remplies (code implémenté mais documentation non cochée)
- Le statut HarmonyOS « UI en cours de planification » obsolète (en réalité 6 pages + ApiClient prêts)
- Le défaut `.../api/v1` d'install.html / InstallController incohérent avec le défaut `/api` de la config (en-tête X-API-Version)
- Le commentaire de CacheService parle de cache à deux niveaux, il est en réalité à trois niveaux (L1 mémoire / APCu / Redis)

### Files:
- Modify: `README.md` / `README.en.md`（nombre de contrôleurs, nombre de points de terminaison, statut HarmonyOS, niveaux de cache）
- Modify: `admin/public/install.html` / `admin/app/controller/InstallController.php`（unification du préfixe de version）
- Modify: `service/support/CacheService.php`（correction du commentaire）
- Optional: rétro-remplir les cases à cocher de `docs/superpowers/plans/*.md`

### Exécuté (2026-08-16)
- README.md / README.en.md : 17 contrôleurs / 61 points de terminaison / 6 pages HarmonyOS / 19 pages Vue / consignes de connexion directe SPA toutes mises à jour ✅
- install.html / InstallController : valeur par défaut `/api/v1` → `/api` (mécanisme d'en-tête X-API-Version) ✅
- Cases à cocher des 8 plans de phase remplies ✅ (phase7 exclu, en attente d'exécution)

### Critères d'acceptation
- [x] Les données du README cohérentes avec le code (17 contrôleurs / 61 points de terminaison / 6 pages HarmonyOS) ✅
- [x] Le préfixe de version de l'assistant d'installation cohérent avec le mécanisme X-API-Version ✅

---

## Planification des phases suivantes (Phase 8-10, hors de ce plan)

| Phase | Contenu | Statut |
|---|---|---|
| Phase 8 | Mise en place multi-canal des alertes : ajout de channel/ dans ads-alert (Email SMTP, Webhook, passerelle SMS en placeholder) — comble l'écart résiduel de la Phase 5 | À démarrer |
| Phase 9 | Vraie interconnexion HarmonyOS : 6 pages connectées à ApiClient (actuellement 0 appel réel, toutes en données simulées) | À démarrer |
| Phase 10 | Approfondissement et commercialisation : vraie interconnexion des 29 plateformes, visualisation du statut de synchronisation, boucle fermée des données de conversion, packaging CI Flutter/HarmonyOS, quotas SaaS multi-tenant | À démarrer |
