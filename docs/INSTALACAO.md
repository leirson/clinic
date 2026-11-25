# Guia de Instalação e Build — ClinicApp

## Pré-requisitos
- PHP 7.4 ou superior, recomendado 8.1+
- MySQL 5.7+ ou MariaDB 10.3+
- Node.js 20+
- npm 10+
- domínio ou ambiente local/remoto configurado

---

## 1. Instalação em produção

### Banco de dados
1. Crie o banco MySQL
2. Importe o SQL principal de instalação
3. Confirme a criação das tabelas

### Arquivos
1. Faça upload do pacote ZIP
2. Extraia o conteúdo na raiz do projeto
3. Garanta permissão de escrita em `assets/uploads/`

### Configuração
Edite `config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_clinicapp');
define('DB_USER', 'u123456789_admin');
define('DB_PASS', 'SuaSenhaSegura123!');
define('APP_URL', 'https://seudominio.com.br');
```

### Build obrigatório para produção
Antes do deploy final, gere os assets compilados:

```bash
npm install
npm run build
```

Envie a pasta `assets/build/` junto com o projeto.

---

## 2. Ambiente local com hot reload

### Passo 1 — instalar dependências
```bash
npm install
```

### Passo 2 — iniciar o Vite
```bash
npm run dev
```

O servidor frontend sobe na porta `5173` com HMR.

### Passo 3 — iniciar o PHP
Em outro terminal:

```bash
php -S 127.0.0.1:8000
```

### Passo 4 — abrir o sistema
Abra a URL servida pelo PHP, por exemplo:

```text
http://127.0.0.1:8000/login.php
```

Quando o Vite está ativo, o sistema detecta automaticamente `storage/vite.hot` e injeta os bundles de desenvolvimento com hot reload.

---

## 3. Ambiente remoto com Vite/HMR

### Arquivo de exemplo
O projeto inclui `.env.example` com as variáveis principais.

### Exemplo de uso
```bash
cp .env.example .env
```

### Variáveis disponíveis
```dotenv
VITE_DEV_SERVER_URL=https://dev.seudominio.com.br:5173
VITE_DEV_HOST=dev.seudominio.com.br
VITE_HMR_HOST=dev.seudominio.com.br
VITE_HMR_PROTOCOL=wss
VITE_HMR_PORT=5173
```

### Quando usar
Use esse modo quando o PHP estiver em um host e o Vite/HMR em outro host público ou reverso por proxy.

---

## 4. Estrutura do frontend

### Entradas por página
- `resources/entries/app.js` → páginas autenticadas
- `resources/entries/auth.js` → login
- `resources/entries/validar.js` → validação pública

### Fontes do frontend
- `resources/css/app.css`
- `resources/css/auth.css`
- `resources/css/legacy.css`
- `resources/js/app.js`

### Saída compilada
- `assets/build/`
- `assets/build/.vite/manifest.json`

### Otimizações
- chunks compartilhados para dependências externas
- chunk dedicado para `chart.js`
- CSS crítico do login separado da área autenticada

---

## 5. Tailwind local com PostCSS

O projeto usa:
- `tailwind.config.cjs`
- `postcss.config.cjs`
- `vite.config.js`

O purge automático analisa:
- `*.php`
- `includes/**/*.php`
- `modules/**/*.php`
- `resources/js/**/*.js`
- `resources/entries/**/*.js`

As classes dinâmicas de gradiente das especialidades ficam na `safelist`.

---

## 6. Comandos úteis

### Desenvolvimento
```bash
npm run dev
```

### Build de produção
```bash
npm run build
```

### Preview opcional
```bash
npm run preview
```

---

## 7. Deploy e atualização

Sempre que houver alteração no frontend:
1. rode `npm run build`
2. envie `assets/build/` atualizado
3. envie os arquivos PHP alterados
4. limpe cache do navegador e, se necessário, reinicie PHP-FPM/Apache

### Pacote enxuto de produção
No pacote final de deploy, podem ser excluídos:
- `node_modules/`
- `resources/`
- `package.json`
- `package-lock.json`
- `vite.config.js`
- `tailwind.config.cjs`
- `postcss.config.cjs`
- `storage/vite.hot`

---

## 8. Solução de problemas

### O CSS não carrega em produção
- confirme a existência de `assets/build/`
- confirme a existência de `assets/build/.vite/manifest.json`
- gere novamente com `npm run build`

### O hot reload não funciona
- confirme se `npm run dev` está ativo
- confirme se `storage/vite.hot` foi criado
- se estiver acessando por outro host, ajuste `VITE_DEV_SERVER_URL`, `VITE_DEV_HOST`, `VITE_HMR_HOST`, `VITE_HMR_PROTOCOL` e `VITE_HMR_PORT`

### Classes dinâmicas sumiram
- adicione a classe à `safelist` em `tailwind.config.cjs`
- gere novo build

---

## 9. Observações

- os assets legados `assets/css/app.css` e `assets/js/app.js` foram removidos
- o carregamento de assets agora é resolvido pelo manifesto do Vite em produção
- em desenvolvimento, o projeto usa injeção automática do cliente HMR
- o login e as páginas públicas usam CSS separado para reduzir peso inicial

---

© 2026 ClinicApp
