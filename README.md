# ClinicApp — Sistema de Gestão para Clínicas

Sistema web completo em **PHP + MySQL** com frontend em **Vite + Tailwind CSS + PostCSS**, prontuário eletrônico, gestão de pacientes, agenda, módulos por especialidade, emissão de **NFSe Nacional** e gerenciamento multiempresa. Desenvolvido para hospedagem no **Hostinger** e também para ambiente local ou remoto de desenvolvimento com HMR.

---

## 📦 Funcionalidades

### Núcleo
- ✅ Autenticação segura com bcrypt + CSRF + timeout de sessão
- ✅ Multi-perfil: Administrador, Profissional, Recepção
- ✅ Especialidades com experiência visual dedicada por usuário
- ✅ Cores de destaque customizáveis
- ✅ Visual moderno e responsivo com **Tailwind compilado localmente**
- ✅ Build frontend com **Vite**, **hot reload** e bundles por página
- ✅ Chunks compartilhados otimizados para produção

### Pacientes
- ✅ Cadastro completo de pacientes
- ✅ Auto-preenchimento por CEP (ViaCEP)
- ✅ Campos prontos para NFSe
- ✅ Histórico de consultas, prontuários e notas

### Agenda
- ✅ Calendário visual
- ✅ Status de atendimento
- ✅ Bloqueios de agenda
- ✅ Lembretes e comunicação
- ✅ Integração com procedimentos

### Prontuário Eletrônico
- ✅ Anamnese, evolução, hipótese e conduta
- ✅ Formulários estruturados por especialidade
- ✅ Receitas, atestados e PDF
- ✅ Odontograma e planos terapêuticos
- ✅ Anexos clínicos

### NFSe Nacional
- ✅ Cadastro fiscal da empresa
- ✅ Emissão, consulta, cancelamento e auditoria de retorno
- ✅ XML de envio e retorno preservado
- ✅ Configuração por empresa

### Gestão
- ✅ Multiempresa
- ✅ Licenças
- ✅ Logs de auditoria
- ✅ Financeiro
- ✅ Backup e instalação inicial

---

## 🚀 Fluxo rápido de instalação

### Produção
1. Faça upload do ZIP de produção para o servidor
2. Extraia na raiz do projeto
3. Configure `config/config.php`
4. Importe o banco SQL
5. Acesse `install.php` ou `login.php`, conforme o pacote utilizado

### Desenvolvimento local
1. Instale dependências frontend:

```bash
npm install
```

2. Rode o Vite com hot reload:

```bash
npm run dev
```

3. Em outro terminal, sirva o PHP localmente no diretório do projeto:

```bash
php -S 127.0.0.1:8000
```

4. Abra a URL do PHP no navegador.

### Desenvolvimento remoto com HMR
1. Copie o arquivo de exemplo:

```bash
cp .env.example .env
```

2. Ajuste os hosts do Vite/HMR no `.env`
3. Rode `npm run dev`
4. Garanta que a porta do Vite esteja acessível pelo host configurado

> Quando o Vite estiver ativo, o projeto detecta automaticamente o servidor de desenvolvimento pelo arquivo `storage/vite.hot` e injeta HMR nos templates PHP.

---

## 🛠️ Build do frontend

### Comandos
```bash
npm install
npm run build
```

### Resultado
- entradas por página em `resources/entries/`
- fontes em `resources/css/` e `resources/js/`
- build final em `assets/build/`
- manifesto gerado em `assets/build/.vite/manifest.json`

### Bundles por página
- `resources/entries/app.js` → área autenticada
- `resources/entries/auth.js` → login
- `resources/entries/validar.js` → validação pública de documentos

### CSS crítico separado
- `resources/css/auth.css` → login e páginas públicas leves
- `resources/css/app.css` → área autenticada
- `resources/css/legacy.css` → estilos legados da área principal

---

## 🎨 Tailwind local com purge automático

O Tailwind está configurado localmente com scan automático em:
- `*.php`
- `includes/**/*.php`
- `modules/**/*.php`
- `resources/js/**/*.js`
- `resources/entries/**/*.js`

Classes dinâmicas de gradiente usadas pelas especialidades ficam protegidas por `safelist` em `tailwind.config.cjs`.

---

## 📁 Estrutura atual

```text
clinicapp/
├── assets/
│   ├── build/                 # build final do Vite
│   ├── img/
│   └── uploads/
├── config/
├── docs/
├── includes/
├── modules/
├── resources/
│   ├── css/
│   │   ├── app.css
│   │   ├── auth.css
│   │   └── legacy.css
│   ├── entries/
│   │   ├── app.js
│   │   ├── auth.js
│   │   └── validar.js
│   └── js/
│       └── app.js
├── sql/
├── storage/
│   └── vite.hot              # criado automaticamente no modo dev
├── .env.example
├── package.json
├── postcss.config.cjs
├── tailwind.config.cjs
├── vite.config.js
├── install.php
├── installer.php
└── sw.js
```

---

## 🔄 Deploy de produção

Para publicar em produção:

```bash
npm install
npm run build
```

Depois envie ao servidor:
- arquivos PHP
- `assets/build/`
- `assets/img/`
- `assets/uploads/` quando necessário
- `config/`, `includes/`, `modules/`, `sql/` e demais arquivos do projeto

**Não é necessário enviar `node_modules/`.**

### Pacote enxuto de produção
O pacote final de deploy pode excluir:
- `node_modules/`
- `resources/`
- `vite.config.js`
- `tailwind.config.cjs`
- `postcss.config.cjs`
- `package.json`
- `package-lock.json`
- `storage/vite.hot`

---

## 🧪 Observações de desenvolvimento

- o hot reload funciona enquanto `npm run dev` estiver ativo
- o projeto alterna automaticamente entre dev e produção
- os assets legados antigos em `assets/css/app.css` e `assets/js/app.js` foram removidos
- o service worker evita pré-cache fixo de bundles antigos
- o CSS do login foi separado para reduzir peso inicial da tela pública
- a build de produção agora pode gerar chunks compartilhados para vendor e Chart.js

---

## 🆘 Suporte

- confira `docs/INSTALACAO.md`
- valide o build com `npm run build`
- se houver erro visual em produção, confirme a presença de `assets/build/`
- se o HMR não carregar, confira `storage/vite.hot` e as variáveis do `.env`

---

© 2026 ClinicApp


## Revisão estrutural — migração v14

- Faça backup dos arquivos e do banco antes de atualizar uma instalação existente.
- Preserve `config/config.php`, `config/github.php`, `config/install.lock`, `.env`, `assets/uploads/` e `storage/`; o ZIP contém configurações de exemplo, não as credenciais do seu servidor.
- Em banco existente atualizado até v13, importe somente `sql/upgrade_v14_integridade_multiempresa.sql`. **Não reimporte `database.sql` nem os seeds de demonstração em produção.** A v14 alinha a empresa de registros clínicos à empresa do paciente. É idempotente e não exclui registros; revise os vínculos dos pacientes antes de executar.
- Instalação nova: `install.php` inclui os upgrades até v15. Requer MariaDB 10.3+ para as instruções `ADD COLUMN IF NOT EXISTS` existentes nos upgrades; a compatibilidade dos upgrades com MySQL não foi validada.
- Build: Node.js 20.19+ (ou 22.12+), `npm ci`, `npm run build`. Os assets compilados estão incluídos. Não publique `node_modules/` nem `storage/vite.hot` em produção.
- Apache: mantenha os `.htaccess`. Anexos e resultados são servidos por `modules/arquivos/download.php`, com autenticação e validação de empresa. Em Nginx configure bloqueios equivalentes para pastas internas e `assets/uploads/{prontuarios,anexos,exames}`.
- Reconfigure as credenciais do GitHub em Empresa > Atualizações. Foi removido um token antes embutido; **revogue esse token no GitHub** e gere outro com permissões mínimas, se necessário.
- NFSe Nacional, SMTP, Jitsi e demais integrações externas dependem de credenciais reais e homologação. A auditoria local não comprova emissão fiscal real nem entrega de e-mail.
- Validação pública de receitas exige hash com pelo menos 16 caracteres. Links antigos com 12 caracteres devem ser reemitidos pela tela de impressão da receita.


## SQLs consolidados para NOVA instalação (até v15)

**Não utilizar em banco existente de produção.** Os arquivos não são atualizações incrementais.

1. Crie/selecione um banco vazio e importe `sql/clinicapp_banco_geral.sql` (46 tabelas, estrutura final, sem dados).
2. Importe `sql/clinicapp_seeds_completos.sql` para carregar todos os seeds compatíveis e unificados: **uma única empresa**, Clínica Demo MultiEspecialidades, 10 profissionais (um por especialidade), administrador, recepção e desenvolvedor.
3. Alternativamente, use `install.php` e escolha a base de exemplo: o instalador executa automaticamente os dois arquivos. Não é necessário importar os upgrades individualmente.

O modo sem demonstração utiliza a mesma estrutura + `sql/seed_instalacao_minima.sql` (carga mínima interna do instalador). Não importe a carga mínima e a demo juntas. Ambos os seeds recusam bancos com dados para impedir mistura/reimportação acidental; não são seeds idempotentes de atualização.

- Conta demo: `admin.demo@clinicapp.com`, senha `123456`.
- Profissionais: `medico.demo`, `dentista.demo`, `psicologia.demo`, `psiquiatria.demo`, `pediatria.demo`, `go.demo`, `ortopedia.demo`, `cardiologia.demo`, `oftalmo.demo`, `neuro.demo` (todos com `@clinicapp.com`, senha `123456`).
- Recepção: `recepcao.demo@clinicapp.com`. Desenvolvedor: `dev.demo@clinicapp.com`. Senha demo: `123456`. Troque todas as senhas antes de expor o ambiente.
- Base mínima: `admin@clinicapp.com` / `admin123`, como no instalador anterior.
- Referências duplicadas foram consolidadas. Os seeds antigos ficam disponíveis para referência, mas NÃO devem ser importados adicionalmente à carga consolidada.
- Códigos fiscais, conselhos, nomes, contatos, datas e informações clínicas são exclusivamente fictícios. Registros de backups/anexos sem arquivos reais e imagens de assinatura inválidas não foram incluídos na nova carga.
- Ícones PNG locais (192 e 512), fontes Bootstrap Icons e caminhos de imagens foram conferidos. QR Codes de segurança e de receitas emitidas dependem de serviço externo; não são imagens locais do pacote.


## Pacote único — instalação corrigida

O ZIP contém aplicativo, `install.php`, `installer.php`, estrutura consolidada, seeds e assets já compilados. Não é necessário baixar/importar SQL separadamente nem executar npm no servidor.

1. Crie um banco vazio e um usuário no painel da hospedagem; vincule o usuário ao banco.
2. Extraia o ZIP na pasta do domínio/subdomínio.
3. Garanta escrita em `config/`, `assets/uploads/` e `storage/`.
4. Acesse `https://seu-dominio/install.php`, informe as credenciais e escolha sem demonstração ou demo com uma empresa/10 especialidades.
5. Ao concluir, troque as senhas iniciais e remova `install.php` e `installer.php`. A trava `config/install.lock` também impede reinstalação.

O instalador conecta primeiro ao banco existente, sem exigir permissão CREATE DATABASE. Confere extensões e arquivos SQL, cria as pastas necessárias e impede execuções simultâneas. Não permite instalação em banco com tabelas. Caso uma tentativa antiga tenha criado tabelas parcialmente, utilize outro banco vazio; não exclua dados de produção.


## Instalador com escolha de seeds e conferência de upgrades

Em `install.php` escolha:

- **Instalação mínima**: estrutura completa, uma empresa, administrador/desenvolvedor, licença e configurações. Sem pacientes, catálogos, protocolos, modelos ou profissionais demo.
- **Banco de exemplo**: carga consolidada completa, uma empresa e 10 profissionais, com admin.demo@clinicapp.com / 123456.
- **Personalizada**: carga mínima + os grupos marcados: profissionais/recepção; catálogos; modelos clínicos; protocolos; pacientes; atendimentos (agenda, prontuários, receitas, exames, odontograma, cuidados e financeiro).

A demonstração de atendimentos inclui automaticamente seus pré-requisitos: profissionais, catálogos e pacientes. Selecionar um checkbox ativa o modo personalizado. Mínima/personalizada utilizam admin@clinicapp.com / admin123; profissionais opcionais usam contas demo e senha 123456.

Todos os modos criam a MESMA estrutura com os upgrades v2 até v15. `sql/install_manifest.json` relaciona database.sql e todos os upgrades presentes no pacote, hashes e tabelas/colunas/índices/relacionamentos esperados. O instalador compara os arquivos e a estrutura criada antes dos seeds e só grava a trava após o sucesso. Um upgrade novo não consolidado, um arquivo alterado ou um componente estrutural ausente interrompe a instalação com mensagem. Não execute novamente os upgrades por cima da estrutura consolidada.

Os SQLs de `sql/seeds_instalacao/` são partes internas do instalador; não os importe junto com o seed completo. O legado `seed_instalacao_basica.sql` não é utilizado pelo novo fluxo.


## Especialidades por empresa — painel do desenvolvedor

Acesse **Painel do Desenvolvedor > Gerenciar Especialidades**, selecione a empresa, marque ou desmarque as especialidades e salve. O botão também fica na linha de cada empresa. Apenas desenvolvedores podem alterar essa configuração, com proteção CSRF e registro em log.

As escolhas são armazenadas em `configuracoes`, na chave `especialidades_habilitadas`, com `empresa_id`. Não há alteração de estrutura nem SQL adicional para quem já está na versão com upgrades até v14. Empresas sem essa configuração mantêm todas as especialidades habilitadas.

Desabilitar remove opções dos seletores, aplica uma especialidade visual habilitada no lugar de preferências desativadas e bloqueia URLs dos módulos exclusivos (Psicologia, planos odontológicos, odontograma e páginas clínicas de especialidades). Novos atendimentos não podem solicitar uma especialidade desabilitada. Os módulos gerais, profissionais, pacientes e prontuários existentes não são excluídos. Nenhuma especialidade marcada deixa apenas as funções gerais disponíveis; a identidade visual neutra não reabilita a Clínica Médica. O desenvolvedor continua com acesso ao painel para reabilitar os módulos.

Atualização existente: faça backup, substitua os arquivos do pacote preservando `config/config.php`, `config/github.php`, uploads e armazenamento do seu servidor. Não execute novamente o instalador nem os seeds em produção. Para instalação nova, use `install.php`; a estrutura consolidada e os seeds selecionáveis continuam no mesmo ZIP.


## Pesquisa em todos os seletores de paciente

Os formulários e filtros com seleção de paciente agora recebem o campo **Pesquisar paciente**, com filtragem por nome sem diferenciar maiúsculas, minúsculas e acentos. A busca aceita várias partes do nome, possui botão Limpar, contador e mensagem quando não há resultados. Digitar nunca troca o paciente atual nem envia o formulário; escolha explicitamente o paciente no seletor. Se a seleção atual não corresponde à busca, ela continua visível em um grupo separado.

Cobertura: criação/edição de agenda, fila de espera, novo prontuário, NFSe, financeiro, planos odontológicos, exames, formulários e filtros de escalas/planos de Psicologia e cuidados/alertas. O odontograma possui pesquisa na lista de pacientes. Na telemedicina, a pesquisa filtra os agendamentos elegíveis pelo nome do paciente, preservando o ID do agendamento e sem limitar a lista aos primeiros 50.

Implementação em `resources/js/patient-search.js`, integrada ao bundle Vite. É uma busca local na lista de opções já autorizadas pelo servidor; não envia dados a terceiros. Seletores `select[name="paciente_id"]` e `select[data-patient-search]` são aprimorados automaticamente, inclusive quando inseridos dinamicamente. Para outros nomes de campo, use `data-patient-search`. Sem JavaScript, os seletores originais continuam utilizáveis. Não há migração de banco adicional. O ZIP inclui os assets compilados.


## Área do paciente — upgrade v15

**Ativação:** Empresa > Área do paciente. O administrador ou desenvolvedor habilita a função por empresa, define os dias da semana, início/fim do expediente online e duração de 15, 20, 30, 40 ou 60 minutos. O recurso começa desabilitado e exige ciência explícita sobre as limitações da autenticação. Habilitar faz aparecer o botão Área do paciente no login. O endereço público é `/portal/index.php`; em ambiente multiempresa o paciente escolhe a clínica habilitada.

**Acesso solicitado:** CPF e telefone/celular com DDD exatamente conforme o cadastro de um único paciente ativo na clínica. Aceita pontuação e prefixo brasileiro +55. CPF duplicado na mesma empresa impede acesso e deve ser corrigido pela recepção. Não há cadastro público automático nem recuperação que revele se o CPF existe. Os limites de tentativas são persistidos no banco (por CPF e IP, janela de 15 minutos), sem gravar esses identificadores em texto aberto na tabela de limites. A sessão tem cookie separado da equipe, proteção CSRF, troca de identificador após login, expiração por 20 minutos de inatividade e limite absoluto de 2 horas. Desativar a função, inativar o paciente ou alterar CPF/telefones invalida o acesso na próxima requisição.

**Limitação de segurança importante:** CPF e telefone são dados de conhecimento, não prova de posse do telefone. Este modo de acesso tem baixa garantia de autenticação e não implementa envio de OTP por SMS/WhatsApp. A clínica deve avaliar sua política de privacidade e risco antes de habilitar. Para aumentar a proteção, a evolução recomendada é integrar um provedor de código de uso único e verificar o código antes de mostrar a agenda. Exija HTTPS em produção. Não publique este portal como se tivesse autenticação forte ou verificação de telefone.

**O paciente pode:** consultar seus agendamentos e o resultado das suas solicitações, escolher uma especialidade habilitada, um profissional ativo da mesma especialidade/empresa, uma data até 90 dias à frente e um horário disponível. Agenda e solicitações mostram até 100 registros recentes. Não são exibidos prontuários, observações clínicas, documentos, links de salas ou dados dos demais pacientes. Respostas privadas usam no-store e não são armazenadas offline pelo service worker.

**Disponibilidade:** usa o expediente online comum configurado para a empresa; não pressupõe jornadas individuais dos profissionais. Cadastre bloqueios por profissional ou gerais para almoço, ausência, feriados e exceções. Exclui horários bloqueados/ocupados e mantém antecedência mínima de 1 hora. Rejeita especialidades desabilitadas, profissionais de outra empresa, datas inválidas e horários forjados. Cada paciente pode ter até cinco solicitações pendentes, sem pedidos sobrepostos. Solicitações pendentes NÃO reservam o horário.

**Análise pela clínica:** Agenda > Solicitações do portal. Recepção, administrador ou desenvolvedor confirma ou recusa. A confirmação verifica novamente empresa, profissional, especialidade, paciente ativo, expediente, duração e disponibilidade, cria um agendamento `agendado` (confirmação de comparecimento ainda pendente) e vincula a solicitação. A operação é transacional e bloqueia linhas para evitar confirmação duplicada. O paciente acompanha o resultado no portal; não foi implementado envio automático de SMS/WhatsApp/e-mail. A lista interna mostra até 200 solicitações, priorizando as pendentes.

**Atualização de sistema existente:** faça backup do banco e dos arquivos; envie o novo pacote preservando `config/config.php`, `config/github.php`, uploads e armazenamento do servidor. Se o banco já tem v14, importe SOMENTE `sql/upgrade_v15_portal_paciente.sql`. Não reimporte o banco geral nem seeds e não execute o instalador. Se sua base for anterior à v14, aplique primeiro as migrações faltantes na ordem. Depois habilite e configure a função em Empresa. A migração v15 é idempotente, cria duas tabelas e não apaga dados.

**Instalação nova:** o mesmo ZIP contém `install.php`, banco geral com 46 tabelas, manifesto até v15 e todos os seeds selecionáveis. Em qualquer modo, inclusive demonstração, o portal começa desligado. O SQL de upgrade separado está dentro do ZIP e pode ser baixado à parte.


## Página da Web — site modular da clínica

O menu **Página da Web** é visível exclusivamente para usuários com perfil **admin**. Recepção, profissionais e desenvolvedor não podem abrir o editor, salvar configurações, visualizar rascunhos ou imagens de pré-visualização. Cada administrador gerencia somente a própria empresa; o parâmetro de empresa enviado pelo navegador não altera esse contexto.

### Publicação e página inicial

O site inicia despublicado, mantendo o comportamento anterior da entrada: visitantes seguem para login e usuários autenticados para o dashboard. No editor, preencha os dados, salve como rascunho, utilize **Visualizar versão salva** e marque **Publicar página da clínica** para disponibilizar o site. A pré-visualização mostra apenas a versão salva, não as alterações ainda não enviadas no formulário. Desmarcar a publicação tira imediatamente a página e suas imagens públicas do ar.

A página é publicada em `index.php?clinica=SEU-ENDERECO`. O endereço é exclusivo entre empresas. Com apenas uma clínica publicada, ela ocupa a raiz; com várias, a raiz mostra um seletor de clínicas, ou utiliza o domínio principal cadastrado da empresa quando há correspondência única com o host. O domínio precisa estar apontado para a hospedagem e ter HTTPS configurado; este recurso não configura DNS automaticamente. O login da equipe permanece em `login.php` e a área do paciente em `portal/index.php`.

### Áreas independentes e personalização

As sete áreas — **destaque inicial, sobre a clínica, especialidades, profissionais, avaliações, contato e rodapé** — possuem habilitação independente, título, texto e ordem de 1 a 99. Áreas menores na ordem aparecem primeiro. Os links de navegação acompanham as seções visíveis; seções de cartões sem itens públicos não são renderizadas. O acesso da equipe continua disponível mesmo com o rodapé desligado.

A identidade permite nome público, frase da marca, logotipo, foto de destaque, cor principal, fundo, grade/lista de cartões, título e descrição para buscadores. O contato permite telefone, WhatsApp, e-mail, endereço, horários, perfil do Instagram, texto legal e botão de atendimento com destino para área do paciente, WhatsApp, ligação ou seção de contato. Quando o portal não está habilitado, seu botão principal volta ao contato se essa seção estiver visível; o link Área do paciente do cabeçalho aparece somente quando o portal está habilitado para aquela clínica.

**Profissionais:** até 24 cartões com nome, função/especialidade pública, registro opcional, apresentação, foto e habilitação individual. Os cartões são editoriais e não importam automaticamente usuários internos, evitando publicar dados de colaboradores sem intenção explícita. Adicione, remova, suba ou desça cada cartão.

**Especialidades:** até 30 cartões com título/descrição próprios e vínculo com uma especialidade do sistema. O cartão só aparece se estiver marcado para publicação e a especialidade continuar habilitada para a empresa no painel do desenvolvedor. O texto público é independente da documentação clínica interna.

**Avaliações:** até 30 depoimentos, cada um com nome de exibição, nota de 1 a 5, texto, habilitação e confirmação de autorização. São depoimentos cadastrados manualmente pela clínica, não avaliações importadas ou verificadas pelo Google/outra plataforma. Nenhuma avaliação de exemplo é publicada automaticamente. Publique somente conteúdo verdadeiro e autorizado, observando suas obrigações profissionais e de privacidade.

### Imagens e segurança

Use somente imagens próprias ou licenciadas em PNG, JPEG ou WebP. Limites: 2 MB por imagem, 5000 pixels por lado; SVG e formatos executáveis são recusados. Uploads são armazenados em `storage/site/<empresa_id>/` com nomes aleatórios. Essa pasta deve permitir escrita pelo processo PHP (normalmente 775 com proprietário/grupo corretos; não use permissões 777 indiscriminadamente). O instalador cria a pasta. Em atualização existente, ajuste sua propriedade/permissões se o editor informar falha ao gravar imagens. Envie poucas imagens por vez para respeitar o `post_max_size` e o `max_file_uploads` da hospedagem.

As imagens são servidas pela rota `site_media.php`, que valida clínica, publicação e vínculo da imagem ao conteúdo visível. Rascunhos só podem ser vistos pelo administrador da própria empresa. Nenhum arquivo clínico ou imagem de cartão desativado é disponibilizado por essa rota. Textos são escapados, URLs/cores validados, a gravação exige CSRF e revisões concorrentes não sobrescrevem alterações de outra aba. Imagens antigas sem referência permanecem no armazenamento e no backup; o editor não faz limpeza automática destrutiva.

### Atualização e instalação

Este módulo usa chaves `website_config` e `website_slug` na tabela de configurações já existente; **não exige nova migração SQL para quem está na v15**. Faça backup e envie o ZIP completo preservando `config/config.php`, `config/github.php`, uploads e toda a pasta `storage`, incluindo `storage/site` após começar a usar. Não execute novamente o instalador nem seeds sobre o banco existente. Para nova instalação, o ZIP mantém instalador, estrutura com upgrades até v15 e seeds selecionáveis. O site público tem bundle CSS próprio, sem carregar gráficos ou scripts administrativos.


### Página inicial: acessos e profissionais cadastrados

Acesse **Página da Web** com perfil **admin**. Na área **Página inicial com o site desativado**, marque login para profissionais/equipe, login de pacientes ou ambos e salve. Para pacientes, primeiro habilite **Empresa → Área do Paciente**. Desmarque **Publicar página da clínica** para usar a tela de acessos; ao publicar novamente, o site modular volta a ocupar a entrada inicial. As preferências controlam apenas os atalhos da página inicial, não bloqueiam login.php nem as permissões do portal. Pelo menos um acesso deve ser escolhido quando o site estiver desativado. Se o portal for desativado depois, seu link desaparece automaticamente; em modo somente pacientes, é exibido aviso de indisponibilidade.

Em **Profissionais**, adicione um cartão, selecione **Profissional cadastrado**, personalize os campos, envie uma foto pública se desejar e marque **Publicar este cartão**. Nome, especialidade e registro são preenchidos na seleção. O conteúdo textual fica como uma apresentação personalizável, sem sincronização automática das edições posteriores do cadastro. O vínculo é revalidado a cada acesso: profissionais inativos, de outra empresa ou com especialidade desabilitada não são publicados. Cartões personalizados sem vínculo continuam disponíveis. Não são expostos e-mail, telefone privado ou credenciais do profissional. Fotos do cadastro interno não são copiadas automaticamente; envie uma foto autorizada para publicação.

Atualização desta versão: preserve config/config.php, demais configurações específicas, assets/uploads e storage. Substitua os arquivos da aplicação, incluindo assets/build, pelos arquivos deste ZIP. **Não é necessário SQL adicional em instalações já na v15.** Não rode instalador ou seeds sobre o banco em uso. Em novas instalações, use install.php e os seeds selecionáveis. Se a raiz não abrir em hospedagem compartilhada, confirme que o ZIP foi extraído no diretório público correto e que DirectoryIndex aponta para index.php; remova páginas index.html antigas somente após backup e confirmação de que não são usadas. Domínios e APP_URL devem corresponder à instalação. Cada clínica pode usar index.php?clinica=seu-endereco-curto; o domínio cadastrado tem precedência na entrada inicial.


### Identidade visual médica da página pública

O site usa um layout institucional próprio para clínicas: cabeçalho com acessos separados, destaque de agendamento, especialidades com ícones vetoriais locais, equipe com fotos maiores, seção institucional, avaliações autorizadas, contato por canal e rodapé. O menu no celular usa o componente HTML nativo details/summary, funcionando sem JavaScript. Não há fontes remotas, fotos de banco de imagens, números de pacientes ou certificações fictícias adicionados por este layout.

A personalização continua em **Página da Web** (somente admin). Envie uma **imagem do destaque inicial** para usar uma foto autorizada da clínica; sem imagem, aparece um painel institucional com a identidade e as especialidades publicadas. As fotos dos profissionais continuam sendo enviadas individualmente nos cartões. Cores, textos e habilitação/ordem das seções são preservados. O redesenho não sobrescreve configurações existentes e não publica cartões automaticamente.

Para atualizar, envie os arquivos do ZIP incluindo **assets/build**; preserve as configurações específicas, uploads e storage do servidor. O pacote contém os estilos compilados, sem exigir Node no servidor. Não há SQL novo para instalações já atualizadas até a v15; não reexecute o instalador no banco existente. Se o visual antigo continuar aparecendo, atualize a página sem cache e verifique se o novo manifest e o CSS do site foram enviados juntos.


### Carregar exemplo completo da página inicial

**Opção recomendada — pelo editor:** entre com o perfil admin na empresa desejada, abra **Página da Web**, localize **Exemplo completo da página inicial**, marque a confirmação e clique em **Carregar exemplo preenchido**. Depois abra **Visualizar versão salva**. O carregamento substitui os campos da página por um exemplo completo e a mantém **não publicada**. A primeira configuração anterior fica preservada na chave **website_example_backup** da tabela configuracoes (JSON com config/config_present e slug/slug_present). Faça também backup do banco antes de substituir conteúdo importante. Não há restauração automática pela interface nesta versão.

A página apresenta textos para destaque, sobre, especialidades, equipe, avaliações, contato e rodapé. Inclui todos os profissionais ativos da empresa cujas especialidades estejam habilitadas (a base demo contém 10, um por especialidade). Não inclui admin, recepção, desenvolvedor, usuários inativos ou profissionais de outras empresas. O editor atual aceita até 24 profissionais; se a clínica exceder esse limite, o carregamento pelo editor é interrompido antes de substituir qualquer dado. Novos profissionais cadastrados depois não são incorporados automaticamente: adicione-os pelo editor ou carregue novamente o exemplo com confirmação. O botão pode ser usado por admins de outras empresas, sempre restrito à própria empresa.

Nome, função e registro dos profissionais vêm dos cadastros, enquanto a descrição é explicitamente ilustrativa. Fotos já vinculadas e imagens institucionais são mantidas pelo carregamento via editor; não há geração ou importação automática de fotos nem cópia dos avatares privados dos usuários. Sem foto, o layout usa as iniciais e os ícones locais.

**Alternativa — SQL separado:** em um banco instalado, faça backup e importe **sql/seed_pagina_web_exemplo.sql**. O SQL encontra uma única empresa com nome_fantasia **Clínica Demo MultiEspecialidades**, sem depender de um ID fixo. Se a empresa não existir ou o nome estiver duplicado, não altera nada e informa isso no resultado. Importe os profissionais demo antes, caso queira a equipe inteira. O SQL preenche a página em rascunho, usa o endereço exemplo-clinica-ID, mantém o primeiro backup e não cria empresa, pacientes ou profissionais. Por usar o marcador website_example_seed_applied, novas importações preservam as edições realizadas depois da primeira importação. O SQL não é execução obrigatória do instalador e não deve ser confundido com upgrade de estrutura. Validação realizada em MariaDB. Diferentemente do botão do editor, o SQL não reaproveita as imagens atuais; o backup preserva sua configuração anterior.

O telefone (00) 0000-0000, o endereço da Rua Exemplo de Saúde e o e-mail com domínio .example são fictícios. WhatsApp e Instagram ficam vazios para não direcionar visitantes a números ou perfis reais de terceiros. Os três depoimentos de demonstração aparecem apenas na pré-visualização e permanecem bloqueados no site público, mesmo se os campos de autorização forem alterados. Para depoimentos reais, adicione novos cartões ou substitua o nome e texto fictícios e registre a autorização. Um aviso de demonstração fica visível até o admin revisar os dados e marcar a opção correspondente no editor.

Revise todos os dados, registros e serviços reais antes de publicar. O exemplo não habilita o portal, especialidades desabilitadas, recebimento de solicitações ou integrações de atendimento automaticamente. Para atualizar os arquivos da aplicação, preserve configurações, uploads e storage. Não é necessário upgrade SQL para este recurso, e não se deve reexecutar o instalador no banco em uso.


### Página pública otimizada para celular

A navegação usa o botão **Menu**, com rótulo **Fechar** quando aberto, em telas de até 960px. A lista ocupa a largura disponível dentro do cabeçalho, oferece rolagem interna e exibe apenas as seções habilitadas com conteúdo. Se não houver seções navegáveis, o botão não aparece. O menu fecha ao selecionar uma seção, tocar/clicar fora, pressionar Esc, mudar o foco para fora ou ampliar a janela para o modo desktop. A navegação por teclado leva o foco ao título da seção escolhida; Esc retorna ao botão. O componente nativo details permite abrir a lista mesmo sem JavaScript.

O cabeçalho permanece acessível durante a rolagem em celulares e tablets, mas deixa de ser fixo em telas de pouca altura para não ocupar a orientação paisagem. Os ajustes incluem áreas de toque de pelo menos 44px nos controles principais, textos maiores, espaços para recortes/notches, imagens proporcionais, equipe e contatos em coluna no celular e transições reduzidas quando essa preferência está ativa. A imagem principal continua priorizada no carregamento; fotos dos profissionais usam lazy loading e decodificação assíncrona. Não foram adicionados frameworks, fontes remotas ou gráficos ao site público.

Após atualizar, envie também **assets/build** completo: o manifest aponta para o novo CSS e para o novo script da navegação pública. Preserve configurações, uploads e storage. Não há SQL adicional, nem necessidade de recarregar o exemplo ou executar o instalador. Faça uma atualização sem cache se o navegador continuar mostrando os assets antigos.
