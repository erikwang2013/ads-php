# Phase 10 : Approfondissement et commercialisation — Plan d'implémentation

[中文](docs/superpowers/plans/2026-08-16-phase10-deepen.md) | [English](docs/superpowers/plans/2026-08-16-phase10-deepen.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase10-deepen.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase10-deepen.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase10-deepen.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase10-deepen.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase10-deepen.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase10-deepen.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase10-deepen.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase10-deepen.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase10-deepen.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase10-deepen.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase10-deepen.ja.md)

**Objectif :** Sur la base des contrats des Phase 7-9 et des canaux multiples, mettre en œuvre quatre capacités d'approfondissement : visualisation du statut de synchronisation, boucle fermée des données de conversion, packaging CI mobile, quotas SaaS multi-tenant.

**Source :** Direction déduite de l'audit d'équipe de la Phase 7 (researcher : mise en œuvre ES/séparation lecture-écriture/file d'attente, CI Flutter/HarmonyOS, vraie interconnexion des 29 plateformes, facturation SaaS avec quotas, boucle fermée des données de conversion, visualisation du statut de synchronisation, enchères IA)

**Tech Stack:** webman v2 (PHP 8.2+), Vue 3, GitHub Actions, MySQL 8.0

---

## État actuel (vérifié)

| Sous-élément candidat | État actuel |
|---|---|
| Visualisation du statut de synchronisation | table `erik_sync_errors` + `RetrySyncTask` (3 nouvelles tentatives, backoff 5^n minutes) déjà existants ; **aucune page front-end/API pour afficher le taux d'échec de synchronisation et la latence** |
| Boucle fermée des données de conversion | tables `erik_conversions` + `erik_attribution_results` existantes, moteur d'attribution implémenté ; **aucun point d'entrée de collecte des conversions** (API de rappel/pixel) |
| CI mobile | `ci.yml` : uniquement PHP syntax→PHPUnit→vue-tsc→Docker ; **aucune construction/empaquetage Flutter/HarmonyOS** |
| SaaS multi-tenant | table `erik_tenants` + middleware TenantIdentify déjà existants ; **aucune facturation/quota/statistique d'utilisation** |
| Mise en œuvre ES | scout.php configuré + dépendance webman-scout introduite ; **aucun service ES dans docker-compose** |
| Vraie interconnexion des 29 plateformes | code des 29 adaptateurs complet ; **aucun enregistrement d'interconnexion sandbox/identifiants**（nécessite des identifiants externes, marqué comme élément manuel） |

## Task 1 : Visualisation du statut de synchronisation

### Files:
- Modify: `service/plugin/ads-api/controller/v1/DashboardController.php` ou ajout de `service/plugin/ads-api/controller/v1/SyncController.php` + route
- Create: `admin/public/web/src/api/sync.ts`
- Create: `admin/public/web/src/views/sync/SyncStatus.vue`（ou intégration dans la page système）

### Points de conception
- Points de terminaison : `GET /api/sync/status`（par compte : last_sync_at, taux de réussite, échecs du jour, nouvelles tentatives en attente）+ `GET /api/sync/errors`（liste paginée des erreurs, avec last_error/retry_count/next_retry_at）
- Front-end : page de statut de synchronisation (tableau + cartes de synthèse), uniquement pour les gammes Full/Standard
- Source de données : erik_platform_accounts（last_sync_at）+ erik_sync_errors

## Task 2 : API de collecte des données de conversion

### Files:
- Modify: `service/plugin/ads-api/controller/v1/`（ajout de ConversionController + route）
- Create: `service/plugin/ads-report/service/ConversionService.php`

### Points de conception
- Points de terminaison : `POST /api/conversions`（les partenaires commerciaux renvoient les conversions : platform/campaign_id/order_id/conversion_time/value/currency/channel）+ `GET /api/conversions`（interrogation）
- Validation : campagne_id existe, montant non négatif, format de l'heure ; écriture dans erik_conversions
- Liaison d'attribution : après le rappel, déclencher éventuellement le recalcul de l'attribution (ou préciser que le recalcul est assuré par l'AttributionEngine existant de façon planifiée/manuelle)
- Front-end : ajout d'une explication/démonstration « rappel de conversion » sur la page du rapport d'attribution (optionnel)

## Task 3 : Packaging CI mobile

### Files:
- Modify: `.github/workflows/ci.yml`（nouveau job : build Flutter（web + linux ou apk）+ vérification statique HarmonyOS）

### Points de conception
- Flutter : `flutter pub get && flutter analyze && flutter build web`（ou apk, choisir la cible constructible selon l'état du dépôt ; si l'environnement flutter est limité, utiliser dart analyze）
- HarmonyOS : pas de chaîne d'outils CI Linux standard, effectuer une vérification statique documentée ou sauter (avec annotation)
- Parallèle au job php-tests existant, ne bloque pas le flux principal

## Task 4 : Quotas SaaS multi-tenant (MVP)

### Files:
- Modify: `service/plugin/ads-tenant/`（ajout de QuotaService）
- Modify: `service/plugin/ads-api/config/route.php` + controller

### Points de conception
- Données : ajout d'un champ quota à erik_tenants ou nouvelle table erik_tenant_quotas（plan/account_limit/campaign_limit/sync_quota）
- Points de contrôle : nombre de comptes liés, nombre de campagnes créées, nombre de synchronisations quotidiennes (vérification aux entrées d'AccountController/CampaignController/DataSyncTask)
- Point de terminaison : `GET /api/tenant/quota`（utilisation + quota）
- Front-end : affichage de l'utilisation du quota sur la page système (optionnel, le MVP peut se limiter à l'API)
- Gamme de versions : valeurs par défaut du quota différenciées par lite/standard/full (constantes de config)

## Critères d'acceptation (par Task)
- [ ] Task 1 : le point de terminaison sync API est utilisable, la page front-end affiche, couverture de test
- [ ] Task 2 : l'API de rappel conversions est inscriptible et interrogeable, validations efficaces, couverture de test
- [ ] Task 3 : le nouveau job CI passe (ou éléments sautés clairement annotés)
- [ ] Task 4 : l'API quota renvoie des valeurs correctes, le blocage de dépassement est effectif, couverture de test
- [ ] Tout : `php vendor/bin/phpunit --no-coverage` passe entièrement, vue-tsc passe

## Hors périmètre de cette phase (nécessite des ressources externes)
- Vraie interconnexion des 29 plateformes (nécessite les identifiants/sandbox de chaque plateforme)
- Mise en œuvre du service ES (nécessite d'ajouter le service ES et l'initialisation des index dans docker-compose)
- Suggestions d'enchères IA (préparation des modèles/données)
