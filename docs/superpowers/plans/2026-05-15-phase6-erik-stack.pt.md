# Phase 6: Erik Stack Architecture Refactoring

[中文](docs/superpowers/plans/2026-05-15-phase6-erik-stack.md) | [English](docs/superpowers/plans/2026-05-15-phase6-erik-stack.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase6-erik-stack.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase6-erik-stack.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase6-erik-stack.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase6-erik-stack.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase6-erik-stack.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase6-erik-stack.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase6-erik-stack.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ja.md)

> Refatoração abrangente: prefixo do banco de dados, sistema de IDs, sistema de criptografia, direitos autorais, padrões de código

## Lista de alterações

| # | Alteração | Pacote | Escopo de impacto |
|---|------|----|---------|
| 1 | Prefixo `ads_` das tabelas do banco | — | Todos os arquivos SQL/migração |
| 2 | Chave primária Snowflake ID (sem autoincremento) | erikwang2013/snowflake-php | Todos os Models + SQL |
| 3 | Criptografia/descriptografia de IDs da API com hashids | erikwang2013/hashids | Todas as respostas dos Controllers |
| 4 | Migração de autenticação JWT | erikwang2013/jwt-webman | AuthMiddleware + AuthController |
| 5 | Criptografia de dados sensíveis da API | erikwang2013/encryption | Camada de requisições/respostas da API |
| 6 | Criptografia de dados sensíveis do banco | erikwang2013/encryptable | Camada de Models Eloquent |
| 7 | Sincronização/consulta de dados ES | erikwang2013/webman-scout | Busca de relatórios |
| 8 | Bandeiras de países | erikwang2013/season | Etiquetas de plataforma no frontend |
| 9 | Aviso de direitos autorais | — | Cabeçalho de todos os arquivos |
| 10 | Remover o prefixo global `\` | — | Todos os arquivos PHP |
| 11 | Adicionar comentários nos arquivos de configuração | — | config/*.php |
| 12 | Layout Flutter Web para PC | — | Projeto Flutter |
| 13 | Aprimoramento de visualização do painel Admin | — | Gráficos do dashboard |
| 14 | Exportação de dados do painel em PDF | — | Novo formato de exportação |
| 15 | Exportação Excel (Client+Admin) | — | Exportação aprimorada |
| 16 | Aplicativo HarmonyOS | — | Novo projeto HarmonyOS |

## Ordem de implementação

**Batch A: Infraestrutura (dependências + ID + criptografia)**
- Atualizar o composer.json adicionando os 6 pacotes erikwang2013
- Reescrever todos os arquivos de migração SQL (prefixo ads_ + bigint sem autoincremento)
- Criar a trait de Snowflake ID
- Atualizar todos os Models (usando SnowflakeTrait)
- Configurar o middleware de hashids
- Migrar o JWT para o jwt-webman

**Batch B: Limpeza de código**
- Remover todos os prefixos globais `\`
- Adicionar cabeçalho de direitos autorais a todos os arquivos
- Adicionar comentários nos arquivos de configuração

**Batch C: Aprimoramentos de frontend**
- Aprimoramento de visualização do painel Admin (mais gráficos, dados em tempo real)
- Exportação de dados do painel em PDF
- Aprimoramento da exportação Excel

**Batch D: Flutter + HarmonyOS**
- Projeto de layout Flutter Web para PC
- Estrutura base do projeto HarmonyOS

