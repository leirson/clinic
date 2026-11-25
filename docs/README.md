# ClinicApp — Sistema de Gestão para Dentistas e Médicos

Sistema web completo em **PHP + MySQL** com seleção de tema (Médico/Dentista), prontuário eletrônico, gestão de pacientes, agendamento, emissão de NFSe via **Asten** e gerenciamento de usuários. Desenvolvido para hospedagem no **Hostinger** (compartilhada ou Business).

---

## 📦 Funcionalidades

### Núcleo
- ✅ Autenticação segura com bcrypt + CSRF + timeout de sessão
- ✅ Multi-perfil: Administrador, Profissional, Recepção
- ✅ Seleção de tema (Médico 🩺 / Odontologia 🦷) por usuário
- ✅ Cores de destaque customizáveis
- ✅ Visual moderno e 100% responsivo (TailwindCSS)

### Pacientes
- ✅ Cadastro completo (dados pessoais, endereço, dados clínicos)
- ✅ Auto-preenchimento por CEP (ViaCEP)
- ✅ Campos prontos para NFSe (CPF/CNPJ, endereço, código IBGE)
- ✅ Histórico de consultas, prontuários e NFSe

### Agenda
- ✅ Calendário visual (mensal / semanal / diário)
- ✅ Status: agendado, confirmado, realizado, faltou, cancelado
- ✅ Lembretes via **e-mail** e **WhatsApp** (link wa.me)
- ✅ Bloqueio de horários (feriados, almoço, folgas)
- ✅ Detecção de conflitos automática
- ✅ Integração com procedimentos cadastrados

### Prontuário Eletrônico
- ✅ Anamnese, queixa principal, exame físico, hipótese, conduta, evolução
- ✅ **Odontograma interativo** (notação FDI, todos os dentes permanentes)
- ✅ Receitas/prescrições com impressão em PDF
- ✅ Atestados médicos com dias de afastamento e CID
- ✅ Anexos múltiplos (exames, raio-x, fotos) — JPG, PNG, PDF, DOC

### NFSe (Asten)
- ✅ Cliente SOAP/REST padrão ABRASF
- ✅ Cadastro completo da empresa prestadora (CNPJ, IM, alíquota, regime, código IBGE)
- ✅ Emissão de RPS e envio para webservice da prefeitura via Asten
- ✅ Ambientes de **homologação** e **produção**
- ✅ Consulta, cancelamento e reimpressão
- ✅ XML de envio/retorno preservado para auditoria
- ✅ PDF da NFSe pronto para impressão

### Gestão
- ✅ CRUD completo de usuários (admin)
- ✅ Configuração da empresa
- ✅ Logs de auditoria
- ✅ Procedimentos com valor e duração padrão

---

## 🚀 Instalação no Hostinger

### 1. Banco de Dados
1. Acesse o **hPanel** do Hostinger
2. Vá em **Bancos de Dados → MySQL**
3. Crie um novo banco (ex: `u123456789_clinicapp`)
4. Anote: nome do banco, usuário, senha e host
5. Em **phpMyAdmin**, importe o arquivo `sql/database.sql`

### 2. Arquivos
1. Acesse **Gerenciador de Arquivos** ou via FTP/SFTP
2. Faça upload do conteúdo do ZIP para `/public_html/` (ou subdomínio)
3. Garanta permissões `755` em pastas e `644` em arquivos
4. A pasta `assets/uploads/` deve ter permissão de escrita (`755` ou `775`)

### 3. Configuração
Edite o arquivo `config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_clinicapp');
define('DB_USER', 'u123456789_admin');
define('DB_PASS', 'SuaSenhaForte');

define('APP_URL', 'https://seudominio.com.br');
```

### 4. Login inicial
- **URL:** `https://seudominio.com.br/login.php`
- **Login:** `admin@clinicapp.com`  
  > O sistema entra com **e-mail**, não com nome de usuário
- **Senha:** `admin123`

> ⚠️ **Altere a senha imediatamente após o primeiro login** em "Meu Perfil".

Se você já instalou uma versão anterior e o login não funcionar, execute no **phpMyAdmin**:

```sql
UPDATE usuarios
SET email = 'admin@clinicapp.com',
    senha = '$2y$10$ekT0zttODYatIwICOij20.TY6wJibbqTHYjIVYsqTpL9IibaC0DSa',
    ativo = 1
WHERE email = 'admin@clinicapp.com';
```

Se não existir o registro, rode também:

```sql
INSERT INTO usuarios (empresa_id, nome, email, senha, perfil, tema_preferido, cor_tema, ativo)
VALUES (1, 'Administrador', 'admin@clinicapp.com', '$2y$10$ekT0zttODYatIwICOij20.TY6wJibbqTHYjIVYsqTpL9IibaC0DSa', 'admin', 'medico', 'blue', 1);
```

---

## 🧾 Configuração Asten NFSe

1. Acesse **Empresa** (menu lateral)
2. Preencha:
   - Razão Social, CNPJ, Inscrição Municipal
   - Endereço completo + **Código IBGE do município**
   - Regime tributário, alíquota ISS, item da Lista de Serviços (4.01 médico, 4.12 dentista)
3. Na seção **Integração Asten**:
   - **Ambiente:** Homologação (para testes) ou Produção
   - **URL Webservice:** fornecida pela sua prefeitura ou Asten
   - **Usuário/Senha/Token:** credenciais Asten
   - **Série RPS** e **Próximo Nº RPS** (controla numeração)

### Códigos de serviço padrão (LC 116)
| Categoria | Item |
|-----------|------|
| Medicina | 4.01 |
| Análises clínicas | 4.02 |
| Hospital/clínica | 4.03 |
| Fisioterapia | 4.08 |
| Odontologia | 4.12 |

---

## 🔐 Segurança

- ✅ Senhas com `password_hash` (bcrypt cost 10)
- ✅ Token CSRF em todos os formulários
- ✅ Prepared Statements PDO (proteção contra SQL injection)
- ✅ `htmlspecialchars` em todas as saídas (XSS)
- ✅ `.htaccess` bloqueia acesso direto a `config/`, `includes/`, `sql/`
- ✅ Uploads não executáveis (bloqueio de `.php` na pasta de uploads)
- ✅ Sessão com timeout (2h)

---

## 📁 Estrutura

```
clinicapp/
├── config/
│   └── config.php          # Configurações + conexão PDO
├── includes/
│   ├── functions.php       # Funções utilitárias, auth, CSRF
│   ├── header.php          # Layout topo + sidebar
│   ├── footer.php          # Layout rodapé
│   └── asten.php           # Cliente NFSe Asten (SOAP/ABRASF)
├── modules/
│   ├── pacientes/          # CRUD pacientes
│   ├── agenda/             # Calendário + bloqueios
│   ├── prontuario/         # Evoluções, odontograma, receitas, atestados
│   ├── nfse/               # Emissão NFSe Asten
│   ├── empresa/            # Configurações
│   ├── usuarios/           # Gestão de usuários
│   └── temas/              # Seletor de tema
├── assets/
│   ├── css/app.css
│   ├── js/app.js
│   └── uploads/            # Anexos de prontuários
├── sql/
│   ├── database.sql        # Script de criação completo
│   └── seed_admin.php      # Recriar senha admin (apagar após uso)
├── dashboard.php
├── login.php
├── logout.php
├── index.php
└── .htaccess
```

---

## 🆘 Suporte

- Confira o arquivo `docs/INSTALACAO.md` para passo a passo detalhado
- Logs em `logs` (tabela MySQL) — registra ações de auditoria
- Em caso de erro 500: ative `display_errors` em `config/config.php`

---

## 📝 Próximos Passos (Roadmap sugerido)

- ✅ PHPMailer para SMTP autenticado já integrado no login 2FA, lembretes da agenda e telemedicina
- ✅ Tela de teste SMTP no painel com validação de conexão, envio de e-mail de teste e erro amigável
- 📊 Relatórios financeiros mensais
- 📅 Integração com Google Calendar
- 🔔 Notificações push / cron de lembretes
- 🧾 Certificado digital A1 (assinatura XML NFSe)
- 📱 PWA (instalável como app)

---

© 2025 ClinicApp · MIT License
