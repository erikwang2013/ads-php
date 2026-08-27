# Phase 8: Plano de Implementação dos Canais Múltiplos de Alertas

[中文](docs/superpowers/plans/2026-08-16-phase8-alert-channels.md) | [English](docs/superpowers/plans/2026-08-16-phase8-alert-channels.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase8-alert-channels.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase8-alert-channels.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase8-alert-channels.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase8-alert-channels.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase8-alert-channels.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase8-alert-channels.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase8-alert-channels.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ja.md)

**Goal:** Preencher as lacunas restantes da Fase 5 — os canais email/sms do `NotificationService` passam de stubs de echo para implementações reais (e-mail SMTP + Webhook genérico), com suporte à configuração de canais. O canal web e o Redis pub/sub já estão implementados e permanecem inalterados.

**Fonte:** Conclusão da auditoria da equipe na Fase 7 (comparação de planejamento do researcher: o único item explicitamente "parcialmente concluído" = canais múltiplos de alertas da Fase 5, com o `ads-alert` sem o diretório `channel/`)

**Tech Stack:** webman v2 (PHP 8.2+), PHPMailer (SMTP), Redis, Vue 3 + Element Plus

---

## Situação atual (verificada)

| Componente | Status |
|---|---|
| `NotificationService::send()` | `match ($channel)` distribui web/email/sms; web grava de fato em `ads_notifications`; email/sms são stubs de echo |
| `AlertRule.channels` | Campo JSON + cast array do Eloquent; o frontend já envia `['web','email','sms']` |
| Admin AlertRuleList.vue | UI de seleção de canais já existente (web bloqueado, email/sms opcionais) |
| Redis pub/sub | Push no canal `alert:new` já implementado |
| Configuração SMTP/e-mail | Inexistente (service/config não tem configuração de mail) |

## Task 1: Canal de e-mail (SMTP)

### Files:
- Criar: `service/config/mail.php` (smtp host/port/user/pass/from/from_name/encryption, orientado por env)
- Criar: `service/plugin/ads-alert/service/channel/EmailChannel.php` (implementa send(AlertLog, AlertRule))
- Modificar: `service/plugin/ads-alert/service/NotificationService.php` (o ramo email chama EmailChannel; remover o stub de echo)
- Modificar: `service/composer.json` (adicionar dependência se o PHPMailer for escolhido; preferir implementação `mail()`/socket sem dependências para manter a leveza, a critério do implementador)

### Pontos de design
- Destinatários: lidos da configuração do AlertRule ou do tenant (se ausentes, usar o campo `email` ou o padrão da configuração)
- Assunto/corpo: reutilizar o modelo de texto do sendWeb ("Alerta acionado: {rule.name}" + métrica/valor atual/condição/limiar)
- Tratamento de falhas: capturar exceções e registrar em log, sem afetar os outros canais e o fluxo principal
- Degradação graciosa quando a configuração estiver ausente (aviso no log, sem lançar exceção que interrompa)

## Task 2: Canal Webhook

### Files:
- Criar: `service/plugin/ads-alert/service/channel/WebhookChannel.php` (POST JSON para a URL configurada)
- Modificar: adicionar o ramo `'webhook'` no match de `NotificationService::send()`

### Pontos de design
- Origem da configuração: estender o AlertRule com o campo `webhook_url` (migration) ou a configuração de channels; para mudança mínima, preferir adicionar a coluna `webhook_url` (nullable) no AlertRule
- Payload: `{event: 'alert.triggered', alert: {...}, rule: {...}, timestamp}`, contendo nível do alerta/métrica/valor/limiar/hora
- Timeout e tentativas: timeout de conexão 5s, timeout total 10s; falhas registradas em log (sem nova tentativa, mantendo simples)
- Segurança: permitir apenas http/https, sem validação de endereços de rede interna (risco de SSRF documentado como limitação conhecida, ou validar a exclusão de rede interna — a critério e registro do implementador)

## Task 3: Canal SMS (placeholder de gateway)

### Files:
- Modificar: `NotificationService::sendSms` (manter o placeholder e comentar claramente o ponto de integração; o implementador pode adotar uma solução leve se avaliar que vale a pena)

### Pontos de design
- O gateway SMS (Alibaba Cloud/Tencent Cloud) exige AK/SK e pagamento; nesta fase, manter a implementação placeholder com comentários indicando os passos de integração
- A opção sms na UI do frontend permanece selecionável, mas o backend apenas registra em log (informando claramente ao usuário que o gateway não está configurado)

## Task 4: Configuração de canais e frontend

### Files:
- Modificar: `admin/public/web/src/views/alert/AlertRuleList.vue` (adicionar opção webhook e campo de URL, se aplicável)
- Modificar: `service/plugin/ads-api/controller/v1/AlertController.php` (criação/atualização de regras aceita webhook_url)
- Modificar: `service/plugin/ads-alert/model/AlertRule.php` (adicionar webhook_url em fillable/casts)
- Modificar: `service/plugin/ads-alert/migration/create_alerts.sql` (ALTER ou descrição do script incremental)

### Aceitação
- [ ] Canal email: após configurar SMTP, o disparo de alerta envia e-mail; sem configuração, degradação graciosa
- [ ] Canal webhook: ao disparar alerta, POST JSON para a URL configurada com campos completos no payload
- [ ] Canal sms: manter o placeholder, registrar em log
- [ ] Regressão do canal web e do Redis pub/sub sem impacto
- [ ] O formulário de regras do Admin permite configurar os novos campos de canal
- [ ] `php vendor/bin/phpunit --no-coverage` passando integralmente
- [ ] Testes novos/atualizados: testes de distribuição de canais do AlertEngine/NotificationService

