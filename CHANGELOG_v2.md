# ClinicApp v2.0 — Changelog

## 2026-11-12 — Navegação mobile e otimização para celular

- Menu mobile expansível em largura disponível, com botões Menu/Fechar, estado acessível e rolagem interna em telas baixas.
- Fecha ao selecionar uma seção, clicar fora, mover o foco para fora, pressionar Esc ou voltar ao tamanho de desktop; foco do teclado acompanha a seção escolhida.
- Componente details continua funcionando sem JavaScript; script de navegação isolado e leve no bundle público, sem carregar gráficos administrativos.
- Cabeçalho fixo durante a rolagem em telas pequenas, desativado em orientação paisagem com pouca altura.
- Botões e links com áreas de toque maiores, tipografia legível, cartões em coluna, imagens proporcionais e suporte às áreas seguras de celulares.
- Mantidos exemplos preenchidos, configurações por empresa e seções habilitadas pelo admin. Sem alteração de banco.

## 2026-11-12 — Exemplo completo e editável da página inicial

- Botão Carregar exemplo preenchido no editor exclusivo do admin, com confirmação explícita, CSRF, controle de revisão e isolamento por empresa.
- Preenche as sete áreas da página, especialidades habilitadas e todos os profissionais ativos cadastrados na empresa demo, sem criar ou modificar usuários.
- Textos institucionais e contatos fictícios identificados como demonstração. Reutiliza as fotos já vinculadas aos profissionais e imagens institucionais existentes ao carregar pelo editor.
- Três depoimentos fictícios somente na pré-visualização, nunca publicados como avaliações reais.
- Exemplo sempre salvo em rascunho; primeira configuração anterior preservada em website_example_backup.
- SQL opcional seed_pagina_web_exemplo.sql resolve IDs no banco de destino e atende exclusivamente à Clínica Demo MultiEspecialidades. Reimportação não sobrescreve edições posteriores.

## 2026-11-12 — Identidade visual de clínica médica

- Redesign do site público: cabeçalho clínico, navegação compacta, botões de agendamento destacados e menu nativo acessível no celular.
- Destaque inicial com foto cadastrada e legenda; sem foto, painel institucional com especialidades habilitadas, sem carregar imagens de terceiros.
- Ícones vetoriais locais por especialidade, cartões da equipe com retratos maiores, registros profissionais e apresentação personalizada.
- Seção institucional em duas colunas, avaliações com autoria e aviso de curadoria, contato organizado por canal e rodapé institucional.
- Identidade mantém cores, títulos, textos, fotos, ordem e habilitação de seções por empresa; seções desativadas não aparecem nos menus ou destaques.
- Tela de acessos (site desativado) alinhada à identidade médica, preservando escolha profissionais/pacientes/ambos.
- Corrigida a marcação HTML do aviso de pré-visualização. Sem migração adicional de banco.

## 2026-11-12 — Acessos iniciais e equipe cadastrada

- Site desativado: escolher login de profissionais, pacientes ou ambos no menu Página da Web (admin).
- Login de pacientes exige Área do Paciente habilitada; se o portal for desabilitado posteriormente, o link é ocultado. URLs de autenticação existentes não são bloqueadas por preferências de exibição.
- Cartões da equipe permitem selecionar profissionais ativos da mesma empresa, preencher nome, especialidade e registro e personalizar a apresentação pública.
- Validação de vínculos, duplicidades e isolamento multiempresa; inativação ou especialidade desabilitada oculta automaticamente os cartões vinculados.
- Página inicial resolve clínica por domínio ou endereço curto, inclusive quando não publicada, sem exibir o site de outra clínica no domínio cadastrado.
- Layout público refinado, tela de acessos responsiva e link de navegação acessível. Sem alteração de schema.

## 2026-11-12 — Página da Web modular e exclusiva do admin

- Site público da clínica na entrada inicial, publicação/rascunho, endereço exclusivo e seleção multiempresa/domínio cadastrado.
- Menu Página da Web e pré-visualização restritos estritamente ao perfil admin, inclusive bloqueando desenvolvedor.
- Destaque, sobre, profissionais, especialidades, avaliações, contatos e rodapé com habilitação, ordem, títulos e textos independentes.
- Cartões editáveis com inclusão, remoção, ordenação e habilitação individual; avaliações exigem declaração de autorização e não recebem selo de verificação externa.
- Identidade visual, SEO básico, contatos, botões, logotipo e fotos personalizáveis; imagens protegidas por vínculo à página publicada.
- CSS público separado, sem gráficos administrativos; configurações por empresa sem nova migração além da v15 já incluída.
- Instalador prepara storage/site e o ZIP mantém todas as funcionalidades anteriores, incluindo pesquisa de pacientes e portal de agendamentos.

## 2026-11-12 — Área do paciente e solicitações online (v15)

- Ativação por empresa e botão Área do paciente no login, inicialmente desabilitado.
- Acesso por CPF e telefone/celular cadastrado, sem exposição de dados clínicos; cookie separado e limites persistentes de tentativas.
- Agenda própria e solicitação por especialidade, profissional, dia e horário; expediente configurável, bloqueios e ocupações respeitados.
- Análise pela clínica com confirmação/recusa, revalidação de disponibilidade e criação transacional na agenda.
- Migração idempotente v15, banco geral com 46 tabelas e instalador/manifesto atualizados, mantendo seeds selecionáveis.
- Autenticação por conhecimento de CPF/telefone: não comprova posse do número e não inclui OTP. Uso depende de aceite explícito do administrador.

## 2026-11-12 — Pesquisa de pacientes em formulários e filtros

- Campo Pesquisar paciente nos 15 seletores de paciente existentes, mais seleção de agendamento da telemedicina e lista do odontograma.
- Busca por nome sem distinção de acentos/maiúsculas, múltiplos termos, contador, mensagem sem resultados e botão Limpar.
- Preserva paciente selecionado, envio do ID original, required, eventos e reset dos formulários. Enter na busca não envia o formulário.
- Suporte automático a novos seletores inseridos dinamicamente, rótulos acessíveis e feedback por leitor de tela.
- Mantém isolamento entre empresas e filtros de pacientes ativos; sem alteração de estrutura do banco.
- Assets Vite recompilados e pacote completo com instalador e seeds selecionáveis.

## 2026-11-12 — Controle de especialidades por empresa

- Painel do desenvolvedor com seleção de empresa e habilitação/desabilitação das 10 especialidades.
- Persistência por empresa, CSRF, validação de chaves e log de alterações.
- Seletores e menus respeitam a configuração; acesso direto aos módulos exclusivos desabilitados é bloqueado.
- Pacientes, profissionais e prontuários preservados; reabilitação restaura o acesso aos módulos.
- Compatível com a estrutura v14, sem migração adicional; instalador e seeds selecionáveis preservados.

## 🆕 Novos Recursos

### Universais (todos os temas)
- **#4 Dashboard BI** — `/modules/relatorios/` — Gráficos Chart.js: faturamento × despesas, top procedimentos, novos pacientes, status de agendamentos, ocupação por profissional, KPIs (ticket médio, no-show, margem)
- **#5 Financeiro Completo** — `/modules/financeiro/` — Contas a receber/pagar, parcelamento automático, categorias customizáveis, fluxo de caixa, formas de pagamento (PIX, cartão, boleto)
- **#25 Assinatura Digital** — Assinatura no canvas (touch/mouse) + hash SHA-256 + QR Code de validação em receitas, atestados e planos de tratamento. Página pública `/validar.php`
- **#26 Backup Automático** — `/modules/backup/` — Dump compactado .sql.gz, retenção 30 dias, cron em `/cron/backup_auto.php`
- **#27 2FA (Autenticação 2 Fatores)** — `/modules/usuarios/seguranca.php` — TOTP (Google Authenticator) + Email, códigos de recuperação
- **#28 PWA** — `manifest.json` + `sw.js` + ícones — Instalável como app, funciona offline (cache)
- **#36 Importação CSV** — `/modules/importar/` — Pacientes e procedimentos via CSV, templates para download, log detalhado de erros

### Exclusivos Odontologia 🦷
- **#9 Planos de Tratamento** — `/modules/planos/` — Orçamento detalhado por dente/face, parcelamento, aprovação com assinatura do paciente, acompanhamento de execução
- **#10 Odontograma Avançado** — Dentição **permanente / decídua / mista**, timeline de evolução, 9 condições visuais (cárie, restauração, coroa, endodontia, implante, etc), tratamento planejado, valor estimado

### Exclusivos Medicina 🩺
- **#12 Prescrição Eletrônica** — `/modules/prontuario/receita.php` — Busca rápida de medicamentos (47 cadastrados), assinatura digital, hash criptográfico, QR Code de validação
- **#13 Telemedicina** — `/modules/telemedicina/` — Salas Jitsi Meet (gratuito), links únicos paciente/profissional, termo de consentimento, envio por WhatsApp/email
- **#14 CID-10** — Banco com 200 códigos mais usados, autocomplete em exames/receitas, API `/api/cid10_search.php`
- **#15 Solicitação de Exames** — `/modules/exames/` — Modelos pré-prontos (Check-up, Perfil Lipídico, Função Hepática/Renal, Tireoide, Pré-natal), urgência, anexo de resultado

## 🔧 Melhorias Técnicas

- Output buffer (`ob_start()`) em `config.php` para evitar erros de "headers already sent"
- Sidebar dinâmica por tema (mostra Odontograma + Planos OU Exames + Telemedicina)
- Service Worker com network-first para PHP e cache-first para assets
- API endpoints REST para autocomplete (CID-10, medicamentos)

## 📦 Banco de Dados

Execute `sql/upgrade_v2.sql` **após** `sql/database.sql`. Adiciona:
- 12 tabelas novas (financeiro, planos, exames, CID, medicamentos, backups, etc.)
- 200 códigos CID-10 pré-populados
- 47 medicamentos comuns
- 6 modelos de exames prontos
- 10 categorias financeiras padrão
- Colunas novas em `usuarios` (mfa_*), `receitas` (hash, QR, assinatura), `agendamentos` (modalidade), `odontograma` (tipo_denticao, tratamento_planejado)

## 🚀 Como Atualizar de v1 → v2

1. **Backup:** sempre faça backup antes
   ```sql
   mysqldump -u usuario -p banco > backup_v1.sql
   ```
2. **Banco:** importe `sql/upgrade_v2.sql` no phpMyAdmin
3. **Arquivos:** substitua todos os arquivos (mantenha seu `config/config.php`)
4. **Permissões:** garanta escrita em `storage/backups/` e `storage/imports/`
5. **Cron (opcional):** configure backup diário automático
   ```
   0 3 * * * /usr/bin/php /home/USUARIO/.../public_html/cron/backup_auto.php
   ```

## ⚙️ Configurações Pós-Atualização

### 2FA
Acesse **Meu Perfil → Segurança** e escolha:
- TOTP: escaneie QR code com Google Authenticator
- E-mail: receba código a cada login

### Backup Automático
hPanel → Avançado → Cron Jobs:
- Comando: `php /home/USUARIO/.../public_html/cron/backup_auto.php`
- Frequência: diariamente às 03:00

### PWA (instalar como app)
- Acesse pelo Chrome/Edge no celular ou desktop
- Aparecerá botão "Instalar App" no dashboard
- Ou: menu do navegador → "Instalar ClinicApp"

### Telemedicina (Jitsi)
Funciona out-of-the-box. Nenhuma configuração extra. Salas privadas geradas automaticamente.
