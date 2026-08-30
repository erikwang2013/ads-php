# Guia de uso

[中文](docs/usage.md) | [English](docs/usage.en.md) | [한국어](docs/usage.ko.md) | [Русский](docs/usage.ru.md) | [Deutsch](docs/usage.de.md) | [Français](docs/usage.fr.md) | [Español](docs/usage.es.md) | [Português](docs/usage.pt.md) | [हिन्दी](docs/usage.hi.md) | [العربية](docs/usage.ar.md) | [বাংলা](docs/usage.bn.md) | [Bahasa Indonesia](docs/usage.id.md) | [日本語](docs/usage.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> Para instalação e implantação, consulte a seção « Início rápido » do README; este documento cobre o fluxo completo após a instalação.

---

## 1. Primeiro login

Após a instalação, abra o console administrativo:

- Instalação com um clique / Docker: `http://localhost`
- Desenvolvimento local: `http://localhost:8789`

Entre com o nome de usuário e a senha de administrador definidos no assistente de instalação. Após o login, você verá o painel com 8 cartões de métricas KPI (custo total, impressões, cliques, conversões, CTR, CVR, CPC médio, CPA médio), um gráfico de linhas da tendência diária de custos, um gráfico de barras de comparação de plataformas e o TOP 10 de campanhas.

Para alterar sua senha ou dados da conta: Gerenciamento do sistema → Gerenciamento de usuários.

---

## 2. Autorização de plataformas

O sistema suporta **16 plataformas nacionais + 13 plataformas internacionais**, todas autorizadas por meio de « Gerenciamento de contas → Vincular conta ».

### Plataformas OAuth2 (a maioria)

1. Selecione a plataforma alvo na página « Vincular conta » e clique em « Autorizar »
2. O navegador redireciona para a página de login da plataforma; faça login e aprove o acesso
3. Após o retorno, o sistema armazena automaticamente o token de acesso

As plataformas autorizadas aparecem na lista de contas. Tokens expirados são renovados automaticamente por `TokenRefreshTask` (no minuto 55 de cada hora) — nenhuma intervenção manual necessária.

### Plataformas com chave de API

Plataformas como Qihoo360, Sogou e Umeng usam autenticação por chave de API: preencha manualmente a chave de API (e os parâmetros de assinatura) na página « Vincular conta », salve e a sincronização começa.

> 16 plataformas nacionais: Juliang (Ocean Engine), Baidu Marketing, Taobao/Alimama, Tencent Ads, Kuaishou, Xiaohongshu, Weibo, Bilibili, Youku Ads, Meituan Ads, Zhihu Ads, Qihoo360, Sogou, Umeng, JD, Pinduoduo Ads
>
> 13 plataformas internacionais: Google Ads, YouTube Ads, Meta Ads, TikTok Ads, LinkedIn Ads, Snapchat Ads, Pinterest Ads, Twitter/X Ads, Amazon Ads, The Trade Desk, Spotify Ads, Twitch Ads, Netflix Ads

---

## 3. Vinculação de contas e envio à biblioteca de criativos

### Gerenciamento de contas

Após a autorização da plataforma, as contas aparecem na lista « Gerenciamento de contas ». Cada conta pode controlar de forma independente sua participação na sincronização (`sync_enabled`). A hierarquia de anúncios tem três níveis: Campanha → Grupo de anúncios → Criativo.

### Biblioteca de criativos

A « Biblioteca de criativos » permite enviar imagens/vídeos com navegação em galeria, para uso em anúncios. Os recursos enviados podem opcionalmente usar armazenamento CDN (veja abaixo).

### Configuração de provedores de armazenamento CDN

O sistema tem uma abstração de armazenamento com vários drivers; vários provedores podem ser configurados ao mesmo tempo:

| Driver | Descrição |
|--------|-----------|
| Armazenamento local | Driver padrão, armazena no disco do servidor |
| Alibaba Cloud OSS | AlibabaOssStorage |
| Tencent Cloud COS | TencentCosStorage |
| Compatível com S3 | S3CompatibleStorage (compatível com AWS S3, Qiniu Cloud, MinIO, etc.) |

Adicione um provedor na página « Provedor CDN » e preencha as chaves/parâmetros de região correspondentes para ativá-lo.

### Envio pré-assinado e purga de cache

- **Envio pré-assinado**: o servidor emite uma URL pré-assinada com limite de tempo (PUT OSS/S3) para cada envio; navegadores ou clientes móveis enviam diretamente para o armazenamento de objetos, sem passar pelo servidor de aplicações — menos banda e carga
- **Purga de cache**: após atualizar ou excluir um recurso, uma purga de cache CDN pode ser acionada para que os clientes recebam sempre o conteúdo mais recente

---

## 4. Sincronização de dados

A sincronização é conduzida por 6 tarefas agendadas (agendadas no processo pelo plugin crontab do webman — nenhum crontab externo necessário):

| Tarefa | Frequência | Responsabilidade |
|--------|------------|------------------|
| RetrySyncTask | A cada 3 minutos | Repetir a última sincronização com falha |
| AlertCheckTask | A cada 5 minutos | Avaliar regras de alerta |
| DataSyncTask | A cada 10 minutos | Sincronizar Campanhas/Grupos/Criativos e relatórios (últimos 2 dias, 9 métricas) |
| BidCheckTask | A cada 10 minutos | Verificar regras de lance automático |
| BudgetCheckTask | A cada 15 minutos | Verificações de alerta de orçamento |
| TokenRefreshTask | Minuto 55 de cada hora | Renovar tokens de plataforma expirados |

A configuração das tarefas está em `service/plugin/ads-task/config/cron.php`; as frequências podem ser modificadas. O status da sincronização é visível na página « Sincronização de dados »; os interruptores por conta estão em « Gerenciamento de contas ».

---

## 5. Análise de relatórios

### Painel

8 cartões de métricas KPI + gráfico de linhas de tendência diária + gráfico de barras de comparação de plataformas + TOP 10 de campanhas, com filtro de intervalo de datas e exportação em PDF/Excel com um clique.

### Relatórios personalizados

- **Dimensões**: date, platform, campaign
- **Métricas**: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- Suporta consultas combinadas por dimensão e ordenação

### Análise de atribuição

Um mecanismo de atribuição multiplataforma integrado suporta **5 modelos de atribuição**: first_touch, last_touch, linear, time_decay, position_based, com janela de retrospectiva de 30 dias. Na página « Análise de atribuição », escolha um modelo e um intervalo de datas para ver a contribuição de cada canal.

### Calendário de campanhas

O « Calendário de campanhas » mostra o cronograma de veiculação de cada campanha em visualização de calendário para uma rápida visão do ritmo diário.

### Exportação

Os relatórios suportam três formatos de exportação:

- **CSV** (BOM UTF-8, abre diretamente no Excel sem caracteres corrompidos)
- **Excel** (HTML .xls)
- **PDF** (layout de impressão HTML)

---

## 6. Alertas e notificações

### Regras de alerta

Crie regras na página « Regras de alerta »: escolha o objeto monitorado (orçamento/custo/impressões/cliques, etc.), o limite e a comparação, o escopo efetivo e os canais de notificação. As regras ativadas são avaliadas por `AlertCheckTask` a cada 5 minutos e disparam quando correspondem.

### Canais de notificação

| Canal | Descrição |
|-------|-----------|
| Web | Notificações no aplicativo, visíveis no « Centro de notificações » |
| Email | Envio por e-mail (SMTP, com fallback `mail()`) ; configure os endereços dos destinatários na regra de alerta |
| SMS | Envio por SMS |
| Webhook | POST JSON para uma URL de retorno configurada; integrável com WeCom/DingTalk/Feishu, etc. |

O histórico de alertas é visível na página « Registros de alertas ».

---

## 7. Aplicativos móveis

### Aplicativo Flutter (12 páginas: Login/Painel/Contas/Campanhas/Grupos de anúncios/Criativos/Relatórios/Lances/Alertas/Notificações, etc.)

```bash
cd apps/flutter
flutter run -d chrome     # PC Web
flutter run -d android    # Celular Android
```

### Aplicativo HarmonyOS

Abra o diretório `apps/harmonyos` com o DevEco Studio e execute.

---

## 8. Multi-inquilino (Multi-tenancy)

O sistema possui um plugin multi-inquilino integrado (ads-tenant):

- **Identificação do inquilino**: o middleware `TenantIdentify` identifica o inquilino atual por solicitação
- **Isolamento de dados**: dois modos — banco de dados compartilhado isolado por `tenant_id`, ou um banco de dados separado por inquilino (`db_type`)
- **Gerenciamento de cotas**: `QuotaService` valida as cotas dos inquilinos (número de contas, recursos, etc.); solicitações acima da cota são rejeitadas

---

## Documentos relacionados

- [Funcionalidades](features.pt.md) — 21 módulos/fluxos de negócio
- [Referência da API](api.pt.md) — todas as definições de interfaces
- [Arquitetura](architecture.pt.md) — implantação/segurança/modelo de dados
