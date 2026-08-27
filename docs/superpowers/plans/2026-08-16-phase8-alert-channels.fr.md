# Phase 8 : Mise en place multi-canal des alertes — Plan d'implémentation

[中文](docs/superpowers/plans/2026-08-16-phase8-alert-channels.md) | [English](docs/superpowers/plans/2026-08-16-phase8-alert-channels.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase8-alert-channels.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase8-alert-channels.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase8-alert-channels.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase8-alert-channels.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase8-alert-channels.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase8-alert-channels.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase8-alert-channels.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ja.md)

**Objectif :** Combler les écarts résiduels de la Phase 5 — les canaux email/sms de `NotificationService` passent de stubs echo à des implémentations réelles (e-mail SMTP + Webhook générique), avec prise en charge de la configuration des canaux. Le canal web et Redis pub/sub sont déjà implémentés, ils restent inchangés.

**Source :** Conclusion de l'audit d'équipe de la Phase 7 (comparaison du plan par le researcher : seul élément explicitement « partiellement terminé » = canaux multiples d'alertes de la Phase 5, le répertoire `channel/` manque dans `ads-alert`)

**Tech Stack:** webman v2 (PHP 8.2+), PHPMailer (SMTP), Redis, Vue 3 + Element Plus

---

## État actuel (vérifié)

| Composant | Statut |
|---|---|
| `NotificationService::send()` | distribue via `match ($channel)` web/email/sms ; web écrit réellement dans `ads_notifications`, email/sms sont des stubs echo |
| `AlertRule.channels` | champ JSON + cast Eloquent en array, le front-end soumet déjà `['web','email','sms']` |
| Admin AlertRuleList.vue | UI de coche des canaux déjà présente (web verrouillé, email/sms optionnels) |
| Redis pub/sub | le push sur le canal `alert:new` est implémenté |
| Configuration SMTP/e-mail | aucune (pas de config mail dans service/config) |

## Task 1 : Canal e-mail (SMTP)

### Files:
- Create: `service/config/mail.php`（smtp host/port/user/pass/from/from_name/encryption, piloté par env）
- Create: `service/plugin/ads-alert/service/channel/EmailChannel.php`（implémente send(AlertLog, AlertRule)）
- Modify: `service/plugin/ads-alert/service/NotificationService.php`（la branche email appelle EmailChannel, suppression du stub echo）
- Modify: `service/composer.json`（si PHPMailer est retenu, ajouter la dépendance ; privilégier une implémentation sans dépendance `mail()`/socket pour rester léger, à évaluer par l'implémenteur）

### Points de conception
- Destinataire : lu depuis la configuration de AlertRule ou du locataire (à défaut, utiliser le champ `email` ou la valeur par défaut de la configuration)
- Objet/corps : réutiliser le gabarit de texte de sendWeb（« 告警触发: {rule.name} » + métrique/valeur courante/condition/seuil）
- Gestion des échecs : capturer les exceptions et journaliser, sans affecter les autres canaux ni le flux principal
- Dégradation élégante en cas de configuration manquante (journalisation, pas d'exception interrompante)

## Task 2 : Canal Webhook

### Files:
- Create: `service/plugin/ads-alert/service/channel/WebhookChannel.php`（POST JSON vers l'URL configurée）
- Modify: ajout de la branche `'webhook'` dans le `match` de `NotificationService::send()`

### Points de conception
- Source de configuration : extension de AlertRule avec le champ `webhook_url` (migration) ou configuration channels ; pour un changement minimal, privilégier l'ajout de la colonne `webhook_url` (nullable) dans AlertRule
- Charge utile : `{event: 'alert.triggered', alert: {...}, rule: {...}, timestamp}`, avec niveau d'alerte/métrique/valeur/seuil/heure
- Délais et nouvelle tentative : délai de connexion 5s, délai total 10s, journalisation en cas d'échec (pas de nouvelle tentative, rester simple)
- Sécurité : n'autoriser que http/https, pas de validation des adresses du réseau interne (risque SSRF noté comme limite connue, ou validation hors réseau interne — à évaluer et documenter par l'implémenteur)

## Task 3 : Canal SMS (placeholder de passerelle)

### Files:
- Modify: `NotificationService::sendSms`（conserver le placeholder, commenter clairement le point d'intégration ; si l'implémenteur évalue une solution légère réalisable, elle peut être mise en œuvre）

### Points de conception
- La passerelle SMS (Alibaba Cloud/Tencent Cloud) nécessite AK/SK et un paiement, placeholder conservé pour cette phase, commentaires indiquant les étapes d'intégration
- L'option sms de l'UI front-end reste sélectionnable mais le backend ne fait que journaliser (informer clairement l'utilisateur qu'aucune passerelle n'est configurée)

## Task 4 : Configuration des canaux et front-end

### Files:
- Modify: `admin/public/web/src/views/alert/AlertRuleList.vue`（si ajout de l'option webhook et de la saisie d'URL）
- Modify: `service/plugin/ads-api/controller/v1/AlertController.php`（la création/mise à jour des règles accepte webhook_url）
- Modify: `service/plugin/ads-alert/model/AlertRule.php`（ajout de webhook_url dans fillable/casts）
- Modify: `service/plugin/ads-alert/migration/create_alerts.sql`（ALTER ou note de script incrémental）

### Critères d'acceptation
- [ ] canal email : après configuration SMTP, le déclenchement d'une alerte envoie bien un e-mail ; dégradation élégante si non configuré
- [ ] canal webhook : au déclenchement d'une alerte, POST JSON vers l'URL configurée, champs de la charge utile complets
- [ ] canal sms : placeholder conservé, journalisation
- [ ] canal web et Redis pub/sub sans régression
- [ ] le formulaire de règles Admin permet de configurer les nouveaux champs de canaux
- [ ] `php vendor/bin/phpunit --no-coverage` passe entièrement
- [ ] nouveaux tests/mises à jour : tests de distribution des canaux AlertEngine/NotificationService
