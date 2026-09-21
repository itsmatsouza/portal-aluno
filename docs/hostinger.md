# Publicação na Hostinger pelo GitHub

Destino: `aluno.leilabrito.com.br`, usuário SSH `u454601756`, porta `65002`, servidor `185.245.180.134`.

O workflow `.github/workflows/deploy-hostinger.yml` publica somente a branch `master`, por execução manual. Ele não roda em pull requests ou automaticamente em cada push. As alterações precisam estar commitadas e enviadas ao GitHub para fazer parte do pacote.

## 1. PHP e SSH

No hPanel do site **aluno.leilabrito.com.br**, selecione PHP 8.3 e habilite `curl`, `pdo_mysql` e `openssl`, se necessário. Preserve as configurações do domínio principal. O PHP do terminal pode continuar sendo 8.1; o script chama explicitamente `/opt/alt/php83/usr/bin/php`.

Na conexão SSH existente, confira:

```bash
/opt/alt/php83/usr/bin/php -v
ls -l /usr/local/bin/composer2
```

O deploy exige links simbólicos para que a pasta pública aponte para uma versão completa. Depois da primeira publicação, confira o site pelo navegador. Se a hospedagem negar acesso ao link simbólico, ajuste a configuração com o suporte antes de tentar novas publicações.

## 2. Chave exclusiva para GitHub Actions

Execute no seu computador, em outro terminal, fora da conexão SSH:

```bash
ssh-keygen -t ed25519 -C "github-actions-portal" -f ~/.ssh/portal_hostinger_actions -N ""
ssh-copy-id -i ~/.ssh/portal_hostinger_actions.pub -p 65002 u454601756@185.245.180.134
```

Se já existir uma chave nesse caminho, não a sobrescreva. Use outro nome e ajuste os próximos comandos. A chave não tem senha para permitir execução automatizada. A senha SSH da Hostinger é digitada apenas no seu terminal quando `ssh-copy-id` solicitar.

Confira acesso pela chave:

```bash
ssh -i ~/.ssh/portal_hostinger_actions -o IdentitiesOnly=yes -o BatchMode=yes -p 65002 u454601756@185.245.180.134 'whoami'
```

A resposta deve ser `u454601756`, sem pedir senha.

## 3. Secrets no GitHub

Abra o repositório, **Settings > Secrets and variables > Actions > New repository secret**. Cadastre:

- `HOSTINGER_SSH_PRIVATE_KEY`: conteúdo completo de `~/.ssh/portal_hostinger_actions`, incluindo as linhas BEGIN e END. Não use o arquivo `.pub` neste campo.
- `HOSTINGER_SSH_KNOWN_HOSTS`: entrada do servidor já verificada no arquivo `~/.ssh/known_hosts` do computador.

Para localizar a entrada conhecida:

```bash
ssh-keygen -F '[185.245.180.134]:65002'
```

Copie as linhas da chave, sem os comentários iniciados por `#`. Entradas com nome do host ofuscado também são aceitas. Se não houver entrada, estabeleça e verifique a conexão primeiro. Não use `StrictHostKeyChecking=no` nem aceite uma chave obtida por `ssh-keyscan` sem conferir sua impressão digital com uma fonte confiável.

Não envie a chave privada pelo chat, não a adicione ao Git e não a cadastre em “Deploy keys” do repositório. Aqui ela autentica o GitHub na **Hostinger**, não a Hostinger no GitHub. O checkout do código usa o token temporário do próprio Actions. A chave permite acesso à conta de hospedagem; limite quem pode alterar workflows e executar deploy.

## 4. Banco e configuração privada

Crie um banco MySQL novo para o portal pelo hPanel. Preserve o banco do site principal. Na raiz privada do subdomínio, crie:

```text
/home/u454601756/domains/aluno.leilabrito.com.br/.env
```

Configure:

```dotenv
APP_ENV=production
APP_URL=https://aluno.leilabrito.com.br
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nome_exato_do_banco_novo
DB_USERNAME=usuario_exato_do_banco_novo
DB_PASSWORD="senha_do_banco_novo"
```

Use o host informado pelo painel se diferente de `localhost`. Copie também todas as variáveis `MAIL_*` funcionais do `.env` local. Configure `HOTMART_HOTTOK` quando cadastrar o webhook e as credenciais OAuth quando for usar consultas da API. Veja `config/hotmart.env.example`.

O arquivo `.env` é uma configuração inicial do servidor, não um arquivo a reenviar a cada alteração de código. O deploy nunca substitui esse arquivo e ajusta sua permissão para `600`. HTTPS precisa estar ativo para o portal funcionar com cookies seguros.

## 5. Primeira publicação

1. Envie o workflow, scripts e demais alterações do portal para `master` no GitHub.
2. Abra **Actions > Publicar portal na Hostinger > Run workflow**.
3. Selecione `master`.
4. Para o banco novo, marque a opção de aplicar migrations pendentes.
5. Execute e acompanhe o log.

O workflow instala dependências com `composer install` conforme `composer.lock`, sem dependências de desenvolvimento. Verifica extensões, conexão e tabelas antes de ativar a versão. Não envia `.git`, testes, `.env` local ou credenciais.

As versões ficam em `releases/`. `public_html` torna-se um link para `releases/IDENTIFICADOR/public`. O `default.php` inicial é preservado em `public_html.initial`. O código privado, o `.env` e as ferramentas protegidas continuam fora da pasta pública. A primeira execução recusa uma pasta pública que contenha outros arquivos além de `default.php`.

As migrations criam o esquema, mas não criam admin ou aluno. Provisione as duas contas de teste após essa etapa. A recuperação de senha deve ser conferida no servidor, mesmo que o SMTP já funcione localmente.

Confira manualmente `/login`, os arquivos CSS/JS, acesso administrativo, recuperação de senha e ferramentas protegidas. O script não verifica envio SMTP nem dispara eventos Hotmart. Uma falha HTTP após ativação requer diagnóstico ou retorno à versão anterior.

## 6. Atualizações e retorno de versão

Altere localmente, faça commit e push, depois execute o workflow. Marque migrations somente quando houver alterações de esquema pendentes e após backup. O deploy usa uma única execução por vez e preserva versões anteriores. Não existe limpeza automática de versões ou pacotes enviados; acompanhe espaço em disco.

Para retornar o código à versão anterior, na Hostinger liste versões:

```bash
ls -1 /home/u454601756/domains/aluno.leilabrito.com.br/releases
readlink /home/u454601756/domains/aluno.leilabrito.com.br/public_html
```

Escolha uma versão compatível com o banco e, substituindo `ID_DA_VERSAO` pelo diretório correto, execute:

```bash
cd /home/u454601756/domains/aluno.leilabrito.com.br
test -f releases/ID_DA_VERSAO/public/index.php && test -f releases/ID_DA_VERSAO/vendor/autoload.php && ln -s "$PWD/releases/ID_DA_VERSAO/public" .public-rollback && mv -Tf .public-rollback public_html
```

Essa operação troca apenas o código. Não desfaz migrations, compras ou contas. Migrations MySQL podem aplicar parcialmente antes de falhar; confira o esquema antes de repetir uma execução com falha. Versões que falharam durante preparação também podem constar em `releases/`; escolha uma publicação anteriormente concluída e validada.

## Referências

- [Execução de workflows](https://docs.github.com/en/actions/how-tos/write-workflows/choose-when-workflows-run/trigger-a-workflow)
- [Configuração de secrets](https://docs.github.com/en/actions/how-tos/write-workflows/choose-what-workflows-do/use-secrets)
- [Composer e PHP no terminal da Hostinger](https://www.hostinger.com/support/5792082-how-to-solve-common-composer-issues-at-hostinger/)

Durante a preparação local, nenhum deploy, migration ou teste de integração foi executado.
