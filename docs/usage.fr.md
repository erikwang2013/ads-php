# Guide d'utilisation

[中文](docs/usage.md) | [English](docs/usage.en.md) | [한국어](docs/usage.ko.md) | [Русский](docs/usage.ru.md) | [Deutsch](docs/usage.de.md) | [Français](docs/usage.fr.md) | [Español](docs/usage.es.md) | [Português](docs/usage.pt.md) | [हिन्दी](docs/usage.hi.md) | [العربية](docs/usage.ar.md) | [বাংলা](docs/usage.bn.md) | [Bahasa Indonesia](docs/usage.id.md) | [日本語](docs/usage.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> Pour l'installation et le déploiement, voir la section « Démarrage rapide » du README ; ce document couvre le processus complet après l'installation.

---

## 1. Première connexion

Après l'installation, ouvrez la console d'administration :

- Installation en un clic / Docker : `http://localhost`
- Développement local : `http://localhost:8789`

Connectez-vous avec le nom d'utilisateur et le mot de passe administrateur définis dans l'assistant d'installation. Après la connexion, vous arrivez sur le tableau de bord avec 8 cartes de métriques KPI (coût total, impressions, clics, conversions, CTR, CVR, CPC moyen, CPA moyen), un graphique en courbes de la tendance quotidienne des coûts, un graphique à barres de comparaison des plateformes et le TOP 10 des campagnes.

Pour changer votre mot de passe ou vos informations de compte : Gestion système → Gestion des utilisateurs.

---

## 2. Autorisation des plateformes

Le système prend en charge **16 plateformes nationales + 13 plateformes internationales**, toutes autorisées via « Gestion des comptes → Lier un compte ».

### Plateformes OAuth2 (la majorité)

1. Sélectionnez la plateforme cible sur la page « Lier un compte » et cliquez sur « Autoriser »
2. Le navigateur redirige vers la page de connexion de la plateforme ; connectez-vous et approuvez l'accès
3. Après le rappel, le système stocke automatiquement le jeton d'accès

Les plateformes autorisées apparaissent dans la liste des comptes. Les jetons expirés sont automatiquement renouvelés par `TokenRefreshTask` (à la 55e minute de chaque heure) — aucune intervention manuelle requise.

### Plateformes à clé API

Les plateformes telles que Qihoo360, Sogou et Umeng utilisent l'authentification par clé API : saisissez manuellement la clé API (et les paramètres de signature éventuels) sur la page « Lier un compte », enregistrez, et la synchronisation démarre.

> 16 plateformes nationales : Juliang (Ocean Engine), Baidu Marketing, Taobao/Alimama, Tencent Ads, Kuaishou, Xiaohongshu, Weibo, Bilibili, Youku Ads, Meituan Ads, Zhihu Ads, Qihoo360, Sogou, Umeng, JD, Pinduoduo Ads
>
> 13 plateformes internationales : Google Ads, YouTube Ads, Meta Ads, TikTok Ads, LinkedIn Ads, Snapchat Ads, Pinterest Ads, Twitter/X Ads, Amazon Ads, The Trade Desk, Spotify Ads, Twitch Ads, Netflix Ads

---

## 3. Liaison de comptes et téléversement de la bibliothèque créative

### Gestion des comptes

Après l'autorisation de la plateforme, les comptes apparaissent dans la liste « Gestion des comptes ». Chaque compte peut contrôler indépendamment sa participation à la synchronisation (`sync_enabled`). La hiérarchie publicitaire est à trois niveaux : Campagne → Groupe d'annonces → Créatif.

### Bibliothèque créative

La « Bibliothèque créative » permet de téléverser des images/vidéos avec une navigation en galerie, pour les créatifs publicitaires. Les ressources téléversées peuvent éventuellement utiliser le stockage CDN (voir ci-dessous).

### Configuration des fournisseurs de stockage CDN

Le système intègre une abstraction de stockage avec plusieurs pilotes ; plusieurs fournisseurs peuvent être configurés en même temps :

| Pilote | Description |
|--------|-------------|
| Stockage local | Pilote par défaut, stocke sur le disque du serveur |
| Alibaba Cloud OSS | AlibabaOssStorage |
| Tencent Cloud COS | TencentCosStorage |
| Compatible S3 | S3CompatibleStorage (compatible AWS S3, Qiniu Cloud, MinIO, etc.) |

Ajoutez un fournisseur sur la page « Fournisseur CDN » et renseignez les clés/paramètres de région correspondants pour l'activer.

### Téléversement pré-signé et purge du cache

- **Téléversement pré-signé** : le serveur délivre une URL pré-signée à durée limitée (PUT OSS/S3) pour chaque téléversement ; les navigateurs ou clients mobiles téléversent directement vers le stockage objet, sans passer par le serveur applicatif — moins de bande passante et de charge
- **Purge du cache** : après la mise à jour ou la suppression d'une ressource, une purge du cache CDN peut être déclenchée pour que les clients reçoivent toujours le contenu le plus récent

---

## 4. Synchronisation des données

La synchronisation est pilotée par 6 tâches planifiées (planifiées dans le processus via le plugin crontab de webman — aucun crontab externe requis) :

| Tâche | Fréquence | Rôle |
|-------|-----------|------|
| RetrySyncTask | Toutes les 3 minutes | Réessayer la dernière synchronisation échouée |
| AlertCheckTask | Toutes les 5 minutes | Évaluer les règles d'alerte |
| DataSyncTask | Toutes les 10 minutes | Synchroniser Campagnes/Groupes d'annonces/Créatifs et rapports (2 derniers jours, 9 métriques) |
| BidCheckTask | Toutes les 10 minutes | Vérifier les règles d'enchères automatiques |
| BudgetCheckTask | Toutes les 15 minutes | Vérifications d'alerte budgétaire |
| TokenRefreshTask | 55e minute de chaque heure | Renouveler les jetons de plateforme expirés |

La configuration des tâches se trouve dans `service/plugin/ads-task/config/cron.php` ; les fréquences sont modifiables. L'état de synchronisation est visible sur la page « Synchronisation des données » ; les interrupteurs par compte se trouvent dans « Gestion des comptes ».

---

## 5. Analyse des rapports

### Tableau de bord

8 cartes de métriques KPI + graphique en courbes de tendance quotidienne + graphique à barres de comparaison des plateformes + TOP 10 des campagnes, avec filtre de plage de dates et export PDF/Excel en un clic.

### Rapports personnalisés

- **Dimensions** : date, platform, campaign
- **Métriques** : cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- Prend en charge les requêtes combinées par dimension et le tri

### Analyse d'attribution

Un moteur d'attribution multiplateforme intégré prend en charge **5 modèles d'attribution** : first_touch, last_touch, linear, time_decay, position_based, avec une fenêtre de rétrospection de 30 jours. Sur la page « Analyse d'attribution », choisissez un modèle et une plage de dates pour voir la contribution de chaque canal.

### Calendrier des campagnes

Le « Calendrier des campagnes » affiche le planning de diffusion de chaque campagne en vue calendrier pour un aperçu rapide du rythme de diffusion quotidien.

### Export

Les rapports prennent en charge trois formats d'export :

- **CSV** (BOM UTF-8, s'ouvre directement dans Excel sans caractères corrompus)
- **Excel** (HTML .xls)
- **PDF** (mise en page d'impression HTML)

---

## 6. Alertes et notifications

### Règles d'alerte

Créez des règles sur la page « Règles d'alerte » : choisissez l'objet surveillé (budget/coût/impressions/clics, etc.), le seuil et la comparaison, le périmètre effectif et les canaux de notification. Les règles activées sont évaluées par `AlertCheckTask` toutes les 5 minutes et se déclenchent en cas de correspondance.

### Canaux de notification

| Canal | Description |
|-------|-------------|
| Web | Notifications dans l'application, visibles dans le « Centre de notifications » |
| Email | Envoi par e-mail (SMTP, avec repli `mail()`) ; configurez les adresses des destinataires dans la règle d'alerte |
| SMS | Envoi par SMS |
| Webhook | POST JSON vers une URL de rappel configurée ; intégrable avec WeCom/DingTalk/Feishu, etc. |

L'historique des alertes est visible sur la page « Journaux d'alertes ».

---

## 7. Applications mobiles

### Application Flutter (12 pages : Connexion/Tableau de bord/Comptes/Campagnes/Groupes d'annonces/Créatifs/Rapports/Enchères/Alertes/Notifications, etc.)

```bash
cd apps/flutter
flutter run -d chrome     # PC Web
flutter run -d android    # Téléphone Android
```

### Application HarmonyOS

Ouvrez le répertoire `apps/harmonyos` avec DevEco Studio et exécutez.

---

## 8. Multi-location (Multi-tenancy)

Le système intègre un plugin multi-locataire (ads-tenant) :

- **Identification du locataire** : le middleware `TenantIdentify` identifie le locataire actuel par requête
- **Isolation des données** : deux modes — base de données partagée isolée par `tenant_id`, ou une base de données distincte par locataire (`db_type`)
- **Gestion des quotas** : `QuotaService` valide les quotas des locataires (nombre de comptes, de ressources, etc.) ; les demandes dépassant le quota sont rejetées

---

## Documents connexes

- [Fonctionnalités](features.fr.md) — 21 modules/flux métier
- [Référence API](api.fr.md) — toutes les définitions d'interfaces
- [Architecture](architecture.fr.md) — déploiement/sécurité/modèle de données
