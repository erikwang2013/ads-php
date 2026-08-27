# Document de conception fonctionnelle

[中文](docs/features.md) | [English](docs/features.en.md) | [한국어](docs/features.ko.md) | [Русский](docs/features.ru.md) | [Deutsch](docs/features.de.md) | [Français](docs/features.fr.md) | [Español](docs/features.es.md) | [Português](docs/features.pt.md) | [हिन्दी](docs/features.hi.md) | [العربية](docs/features.ar.md) | [বাংলা](docs/features.bn.md) | [Bahasa Indonesia](docs/features.id.md) | [日本語](docs/features.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> Toutes les définitions d'interfaces API (requêtes/réponses/paramètres) se trouvent dans [api.fr.md](api.fr.md).

---

## Aperçu des modules

| # | Module | Contrôleur/Service | Nombre de routes API | Pages Vue |
|---|------|--------|-----------|----------|
| 1 | Authentification | AuthController | 3 | LoginPage |
| 2 | Gestion des plateformes | PlatformController | 3 | — |
| 3 | Gestion des comptes | AccountController | 5 | AccountList, AccountBind |
| 4 | Plans publicitaires | CampaignController | 6 | CampaignList |
| 5 | Groupes d'annonces | AdGroupController | 5 | AdGroupList |
| 6 | Créations publicitaires | CreativeController | 2 | CreativeList |
| 7 | Rapports de données | DashboardController, ReportController, ExportController | 8 | DashboardPage, ReportView, ReportExport, CampaignCalendar, AttributionReport |
| 8 | Surveillance des alertes | AlertController | 7 | AlertRuleList, AlertLogList |
| 9 | Centre de notifications | NotificationController | 4 | NotificationList |
| 10 | Enchères automatiques | BidRuleController | 5 | BidRuleList |
| 11 | Modèles de ciblage | TargetingTemplateController | 5 | — |
| 12 | Administration système | AdminUserController, AuditLogController | 5 | UserManage, AuditLog |
| 13 | Synchronisation des données | DataSyncTask, TokenRefreshTask, RetrySyncTask | — | — |
| 14 | Bibliothèque de ressources | AssetController | 4 | AssetGallery |
| 15 | Alerte de budget | BudgetAlertService + BudgetCheckTask | 1 | — |
| 16 | Calendrier de diffusion | CalendarService | 1 | CampaignCalendar |
| 17 | Attribution inter-plateformes | AttributionEngine | 2 | AttributionReport |
| 18 | Vérification de santé | HealthController | 2 | — |
| 19 | Captcha | CaptchaController | 2 | — |
| 20 | Documentation API | DocController | 1 | — |

**Total** : 20 modules, 65+ routes, 18 pages Vue

---

## Module 1 : Authentification

- Vérification du captcha (optionnelle)
- Interrogation de la table `admin_users`
- Vérification bcrypt `password_verify()`
- Génération de jeton JWT (TTL 24h)
- Ajout automatique des anciens jetons à la liste noire
- Extraction du `uid` du jeton pour interroger les informations utilisateur

Interface : Connexion / Rafraîchissement de jeton / Utilisateur courant → [api.fr.md module 2](api.fr.md#模块-2-认证)

---

## Modules 2-3 : Gestion des plateformes et des comptes

- Cache de la liste des plateformes pendant 1 heure (Redis), intégration des emoji de drapeaux Season
- Flux OAuth : génération d'un state aléatoire → construction de l'URL d'autorisation → traitement du callback → stockage du jeton
- Cache de la liste/détail des comptes pendant 5 minutes

Interface : Liste des plateformes / OAuth / CRUD des comptes + synchronisation → [api.fr.md module 3](api.fr.md#模块-3-平台--账户)

---

## Modules 4-6 : Hiérarchie de diffusion publicitaire

### Structure de données

```
Campaign (广告计划)
  ├── AdGroup (广告组) × N
  │     └── Creative (创意) × N
  └── ReportMetrics (报表指标)
```

- La création d'un plan passe par l'adaptateur de plateforme + écriture en local
- Filtrage par plateforme/statut/mot-clé, la liste inclut le récapitulatif du jour
- La création d'un groupe d'annonces prend en charge le chargement d'un modèle de ciblage via `targeting_template_id`

Interface : Plans / Groupes d'annonces / Créations → [api.fr.md modules 4-6](api.fr.md#模块-4-广告计划)

---

## Module 7 : Rapports de données

- Cache du récapitulatif du tableau de bord pendant 5 minutes : 8 cartes d'indicateurs KPI + courbe de tendance quotidienne + histogramme par plateforme
- Dimensions du rapport personnalisé : date, platform, campaign
- Indicateurs : cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- Formats d'export : CSV (UTF-8 BOM), Excel (HTML .xls), PDF (impression HTML)

Interface : Récapitulatif / Personnalisé / Export → [api.fr.md module 7](api.fr.md#模块-7-报表)

---

## Module 8 : Surveillance des alertes

### Flux d'évaluation d'AlertEngine

```
遍历 enabled=1 的规则
  → 查询 ads_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 去重检查 (check_interval 内已有触发 → 跳过)
  → 创建 AlertLog (status=triggered)
  → NotificationService.send()
```

### Canaux de notification

| Canal | Statut | Implémentation |
|------|------|------|
| web | ✅ | Écriture dans ads_notifications |
| email | placeholder | stub echo |
| sms | placeholder | stub echo |
| Redis pub/sub | ✅ | Push JSON sur le canal `alert:new` |

Interface : CRUD des règles / Journaux d'alerte / Confirmation / Non lus → [api.fr.md module 8](api.fr.md#模块-8-告警)

---

## Module 9 : Centre de notifications

- Interrogation toutes les 30 s par le store Pinia du frontend
- Icône de cloche dans la barre latérale + badge numérique des non lus

Interface : Liste / Non lus / Marquer comme lu / Tout marquer comme lu → [api.fr.md module 9](api.fr.md#模块-9-通知)

---

## Module 10 : Moteur d'enchères automatiques

### Flux d'évaluation de BidEngine

```
遍历 enabled=1 的规则
  → 查询 ads_report_metrics (今天数据, 按 scope 过滤)
  → compare(metric_value, threshold, condition)
  → 冷却检查 (cooldown_minutes 内是否有过操作)
  → 执行动作:
    - adjust_budget: 新预算 = current + adjust_step, 限制 [budget_min, budget_max]
    - toggle_pause: 暂停计划
    - toggle_enable: 启用计划
  → 通过 AdapterRegistry → PlatformAdapter 调用平台 API
  → 更新本地 DB + 写入 BidLog
```

### Champs de règle

| Champ | Type | Description |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | Indicateur surveillé |
| condition | gt/gte/lt/lte | Condition de déclenchement |
| threshold | DECIMAL(12,2) | Seuil |
| scope | tenant/platform/campaign | Portée d'application |
| action_type | adjust_budget/toggle_pause/toggle_enable | Action |
| adjust_step | INT (centimes) | Pas d'ajustement du budget (positif = augmentation, négatif = réduction) |
| budget_min, budget_max | BIGINT | Bornes du budget |
| cooldown_minutes | INT | Période de refroidissement |

Interface : CRUD des règles / Historique des enchères → [api.fr.md module 10](api.fr.md#模块-10-自动出价)

---

## Module 11 : Modèles de ciblage d'audience

### Intégration aux groupes d'annonces

```
POST /api/ad-groups 支持 targeting_template_id
→ 加载模板 targeting JSON
→ 合并请求中的 targeting 覆盖
→ 传递给平台适配器
```

### Schéma JSON générique

```json
{
  "geo": { "countries": [], "regions": [], "cities": [] },
  "age": { "min": 18, "max": 55 },
  "gender": "all",
  "interests": [],
  "behaviors": [],
  "devices": { "os": [], "types": [] },
  "languages": [],
  "placements": [],
  "custom_audiences": [],
  "lookalike_audiences": []
}
```

Interface : CRUD des modèles → [api.fr.md module 11](api.fr.md#模块-11-定向模板)

---

## Module 12 : Administration système (Admin)

- La liste des utilisateurs encode les ID avec hashids
- La création d'utilisateur hache le mot de passe avec bcrypt
- La désactivation d'un utilisateur est une désactivation douce (status=0)

Champs du journal d'audit : `{ user_id, username, action, resource, resource_id, detail, ip, user_agent, client_platform }`

Interface : Gestion des utilisateurs / Journaux d'audit / Rôles → [points de terminaison Admin de api.fr.md](api.fr.md#admin-端点端口-8789)

---

## Module 13 : Synchronisation des données

### Flux de DataSyncTask (toutes les 10 minutes)

```
遍历 sync_enabled=1 的账户
  → 获取平台适配器
  → 同步 Campaigns (fetchCampaigns → updateOrInsert)
  → 同步 AdGroups (fetchAdGroups → 遍历每 campaign)
  → 同步 Creatives (fetchCreatives → 遍历每 ad_group)
  → 同步 Reports (fetchReports → 过去 2 天 daily, 9 个指标)
  → 清除 Dashboard 缓存
  → 更新 last_sync_at
```

---

## Format de réponse

### Succès
```json
{ "code": 0, "message": "操作成功", "data": { ... } }
```

### Pagination
```json
{ "code": 0, "message": "success", "data": { "list": [...], "pagination": { "page": 1, "per_page": 20, "total": 100, "total_pages": 5 } } }
```

### Erreur
```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
```

---

## Module 14 : Bibliothèque de ressources publicitaires

- Types pris en charge : image/jpeg, image/png, image/gif, image/webp, video/mp4
- Stockage des fichiers : `public/uploads/assets/`
- Frontend : galerie en grille + upload par glisser-déposer + aperçu d'image + lecture vidéo + copie d'URL

Interface : Upload / Liste / Détail / Suppression → [api.fr.md module 12](api.fr.md#模块-12-素材库)

---

## Module 15 : Alerte de budget

- Alertes en trois paliers : yellow (≥50 %), orange (≥80 %), red (≥100 %)
- BudgetCheckTask exécuté toutes les 15 minutes
- Déduplication : un seul avis par plan et par palier par jour
- Écriture dans la table `ads_notifications`

Interface : Alerte de budget → [api.fr.md module 7](api.fr.md#模块-7-报表)

---

## Module 16 : Calendrier de diffusion

- Agrégation des plannings de campagne par date
- Diagramme de Gantt frontend : axe x = dates, axe y = plans, couleurs par plateforme
- Bascule de vues mois/semaine

Interface : Calendrier de diffusion → [api.fr.md module 7](api.fr.md#模块-7-报表)

---

## Module 17 : Attribution inter-plateformes

### Modèles d'attribution

| Modèle | Algorithme |
|------|------|
| first_touch | Premier point de contact 100 % |
| last_touch | Dernier point de contact 100 % |
| linear | Répartition égale entre tous les points de contact (1/N) |
| time_decay | e^(-λ×Δt), demi-vie de 7 jours |
| position_based | 40 % premier + 40 % dernier + 20 % intermédiaire |

- Fenêtre de remontée : 30 jours
- Sources des points de contact : `ads_report_metrics` (clics > 0)
- Résultats écrits dans `ads_attribution_results`
- Frontend : AttributionReport.vue avec bascule de modèle + cartes statistiques + histogramme ECharts + table de détail

### Tables de données

| Table | Champs |
|----|------|
| `ads_conversions` | id, tenant_id, platform, campaign_id, order_id, conversion_time, value, currency, channel |
| `ads_attribution_results` | id, tenant_id, conversion_id, model, campaign_id, credit |

Interface : Analyse d'attribution / Liste des modèles → [api.fr.md module 7](api.fr.md#模块-7-报表)

### Vérification de santé
```json
{ "status": "healthy", "timestamp": "2026-05-21T...", "checks": { "database": "ok", "redis": "ok" } }
```

---

## Module 18 : Résilience des appels plateforme (disjoncteur / dégradation)

### Machine à états du disjoncteur

`CircuitBreaker` (service/plugin/ads-platform/src/CircuitBreaker.php) — état par plateforme :

| État | Déclencheur | Comportement |
|------|-------------|--------------|
| CLOSED | Normal | Appels autorisés |
| OPEN | 5 échecs consécutifs | Échec rapide, plateforme ignorée |
| HALF_OPEN | Après 30s de refroidissement | Un appel de sonde autorisé |
| CLOSED | Sonde réussie | Rétabli, compteur remis à zéro |
| OPEN | Nouvel échec de sonde | Disjonction à nouveau |

### Proxy GuardedAdapter

- `AdapterRegistry::get()` renvoie un proxy GuardedAdapter ; 14 points d'appel sans modification
- En OPEN, lève `CircuitBreakerOpenException` (échec rapide) ; la couche de tâches l'absorbe = dégradation en ignorant la plateforme
- Méthode Generator : itération complète → success, interruption → failure

### Vérification des délais

- Les 29 adaptateurs incluent CURLOPT_TIMEOUT (30/60s) + CURLOPT_CONNECTTIMEOUT (10s)

### Couverture de tests

- CircuitBreakerTest 8 cas + GuardedAdapterTest 13 cas

### Limite connue

- État en mémoire sur un nœud ; le multi-nœuds nécessite un état Redis partagé
