# Phase 9 : Vraie interconnexion HarmonyOS — Plan d'implémentation

[中文](docs/superpowers/plans/2026-08-16-phase9-harmonyos.md) | [English](docs/superpowers/plans/2026-08-16-phase9-harmonyos.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase9-harmonyos.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase9-harmonyos.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase9-harmonyos.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase9-harmonyos.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase9-harmonyos.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase9-harmonyos.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase9-harmonyos.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ja.md)

**Objectif :** Basculer les 6 pages côté HarmonyOS des données simulées vers de vrais appels API (service :8788), corriger le problème de baseUrl codé en dur d'ApiClient, rendre la connexion réelle, et faire du client HarmonyOS un troisième client utilisable.

**Source :** Audit d'équipe de la Phase 7 (inventaire mobile-dev : les 6 pages HarmonyOS sont toutes en données simulées, 0 appel réel, baseUrl d'ApiClient codé en dur `http://127.0.0.1:8788/api`)

**Tech Stack:** ArkTS + ArkUI, @ohos.net.http

---

## État actuel (vérifié)

| Composant | Statut |
|---|---|
| `api/ApiClient.ets` | get/post/put/delete/login complets ; baseUrl codé en dur `http://127.0.0.1:8788/api`（Flutter utilise un chemin relatif même origine `/api`）；login() sans appelant |
| `pages/LoginPage.ets` | connexion simulée (setTimeout 1s puis redirection), commentaire « replace with actual API call » |
| `pages/DashboardPage.ets` | métriques `@State` codées en dur (totalCost=1250000 etc.) |
| `pages/CampaignListPage.ets` | commentaire placeholder L187 `/campaigns` |
| `pages/AccountPage.ets` | commentaire placeholder L138 `/accounts` |
| `pages/AlertPage.ets` | commentaire placeholder L146 `/alerts` |
| `pages/ReportPage.ets` | commentaire placeholder L242 `/reports?date=` |
| models | Campaign/PlatformAccount/AlertRule/ReportMetric déjà existants |
| i18n | StringResources.ets（15+ clés） |

## Task 1 : Amélioration d'ApiClient

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/api/ApiClient.ets`

### Points de conception
- **baseUrl configurable** : conserver setBaseUrl, la valeur par défaut reste `http://127.0.0.1:8788/api`（appareils réels/simulateurs : pointer vers une adresse LAN, commentaire explicatif)；éviter le chemin relatif même origine façon Flutter (ArkTS exige une URL absolue)
- **Corriger le bug de replayHeaders dupliqué** : `{ ...this.replayHeaders(), ...this.replayHeaders() }` double étalement (dans la méthode get) → une seule fois
- **Adapter la valeur de retour de login()** : `POST /api/auth/login` du service renvoie `{access_token, token_type, expires_in, user}`（comparer avec les champs réels de `service/plugin/ads-api/controller/v1/AuthController.php` — c'est access_token et non token, vérifier puis corriger le test `data.token`）
- **Gestion des erreurs** : lever une erreur/renvoyer un message d'erreur clair quand resp.responseCode n'est pas 2xx ; protection en cas d'échec de JSON.parse
- Conserver la convention existante des retours get/post/put/delete `data.data` (dépaquetage ApiResponse)

## Task 2 : Connexion réelle de LoginPage

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/pages/LoginPage.ets`

### Points de conception
- `handleLogin()` appelle `ApiClient.login(username, password)` ; succès → setToken + redirection Dashboard ; échec → message d'erreur en toast
- L'état de chargement isLoading existe déjà, le réutiliser
- Pour les messages d'erreur, privilégier le message renvoyé par le service (enveloppe ApiResponse), sinon texte générique

## Task 3 : Réalisation des cinq pages métier

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/pages/DashboardPage.ets`、`CampaignListPage.ets`、`AccountPage.ets`、`AlertPage.ets`、`ReportPage.ets`

### Correspondance des points de terminaison (confirmée par l'audit Phase 7, cohérente avec Flutter après correction)
| Page | Appel | Analyse |
|---|---|---|
| DashboardPage | `GET /reports/summary`（intervalle du jour） | `data.overview` → totalCost/total_impressions/avg_ctr etc.（montants en fen, formatFen déjà présent） |
| CampaignListPage | `GET /campaigns` | `data.list`（paginé）→ modèle Campaign |
| AccountPage | `GET /accounts` | `data.list` → modèle PlatformAccount |
| AlertPage | `GET /alerts/logs` | `data.list` → champs AlertLog（metric/rule_name/current_value/condition/threshold/status） |
| ReportPage | `GET /reports/custom`（date_start/date_end/dimensions[]/metrics[]） | `data.list` → ReportMetric |

### Points de conception
- Le chargement de page (aboutToAppear) déclenche la requête ; les données @State sont initialisées vides/0 pour éviter que des valeurs simulées subsistent
- En cas d'échec de chargement : affichage de l'erreur + nouvelle tentative (référence au modèle erreur/réessai des pages Flutter)
- Unité monétaire : le service renvoie des nombres en fen, formatFen gère déjà
- **Aucun nouveau fichier** : conserver la structure UI existante et l'i18n de chaque page

## Task 4 : Validation

### Critères d'acceptation
- [ ] ApiClient sans replayHeaders dupliqué, champs de retour de login cohérents avec AuthController
- [ ] Plus aucune donnée métier simulée codée en dur dans les 6 pages (vérification grep)
- [ ] Les chemins d'appel des 5 pages métier correspondent un à un aux routes du service (comparer avec `service/plugin/ads-api/config/route.php`)
- [ ] Vérification syntaxique ArkTS (exécuter hvigor/chaîne d'outils DevEco si disponible dans cet environnement ; sinon le préciser et vérifier manuellement)
- [ ] Régression : PHPUnit du service non affecté
