# Phase 6 : Refactorisation de l'architecture Erik Stack

[中文](docs/superpowers/plans/2026-05-15-phase6-erik-stack.md) | [English](docs/superpowers/plans/2026-05-15-phase6-erik-stack.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase6-erik-stack.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase6-erik-stack.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase6-erik-stack.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase6-erik-stack.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase6-erik-stack.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase6-erik-stack.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase6-erik-stack.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ja.md)

> Refactorisation complète : préfixe de base de données, système d'ID, système de chiffrement, copyright, normes de code

## Liste des changements

| # | Changement | Paquet | Périmètre d'impact |
|---|------|----|---------|
| 1 | Préfixe de table de base de données `erik_` | — | Tous les fichiers SQL/migration |
| 2 | Clé primaire Snowflake ID (sans auto-incrément) | erikwang2013/snowflake-php | Tous les Model + SQL |
| 3 | Chiffrement/déchiffrement hashids des IDs API | erikwang2013/hashids | Toutes les réponses Controller |
| 4 | Bascule de l'authentification JWT | erikwang2013/jwt-webman | AuthMiddleware + AuthController |
| 5 | Chiffrement/déchiffrement des données sensibles API | erikwang2013/encryption | Couche requête/réponse API |
| 6 | Chiffrement/déchiffrement des données sensibles DB | erikwang2013/encryptable | Couche Eloquent Model |
| 7 | Synchronisation/interrogation ES | erikwang2013/webman-scout | Recherche dans les rapports |
| 8 | Drapeaux nationaux | erikwang2013/season | Étiquettes de plateforme du front-end |
| 9 | Mention de copyright | — | En-tête de tous les fichiers |
| 10 | Suppression du préfixe global `\` | — | Tous les fichiers PHP |
| 11 | Ajout de commentaires aux fichiers de configuration | — | config/*.php |
| 12 | Disposition PC Web Flutter | — | Projet Flutter |
| 13 | Amélioration de la visualisation du panneau Admin | — | Graphiques du tableau de bord |
| 14 | Export PDF des données du panneau | — | Nouveau format d'export |
| 15 | Export Excel (Client+Admin) | — | Export amélioré |
| 16 | App HarmonyOS | — | Nouveau projet HarmonyOS |

## Ordre d'implémentation

**Batch A : Infrastructure (dépendances + ID + chiffrement)**
- Mettre à jour composer.json pour ajouter les 6 paquets erikwang2013
- Réécrire tous les fichiers de migration SQL (préfixe erik_ + bigint sans auto-incrément)
- Créer le trait Snowflake ID
- Mettre à jour tous les Model (utilisation de SnowflakeTrait)
- Configurer le middleware hashids
- Basculer JWT vers jwt-webman

**Batch B : Nettoyage du code**
- Supprimer tous les préfixes globaux `\`
- Ajouter l'en-tête de copyright à tous les fichiers
- Ajouter des commentaires aux fichiers de configuration

**Batch C : Améliorations front-end**
- Amélioration de la visualisation du panneau Admin (plus de graphiques, données temps réel)
- Export PDF des données du panneau
- Amélioration de l'export Excel

**Batch D : Flutter + HarmonyOS**
- Projet de disposition PC Web Flutter
- Squelette du projet HarmonyOS
