# ClinicApp v2.0 — Changelog

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
