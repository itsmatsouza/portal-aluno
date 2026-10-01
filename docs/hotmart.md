# Integração Hotmart

A integração usa o webhook 2.0.0 para atualizar alunos e acessos. O cliente OAuth consulta vendas, produtos e alunos do Club. As credenciais da API são independentes do Hottok: o webhook registra a compra, mas a liberação exige reconciliação pelo Club.

## Instalação

1. Faça backup do banco e publique os arquivos. A raiz pública do servidor deve ser `public/`.
2. Copie as variáveis de `config/hotmart.env.example` para o `.env` existente, sem substituir as demais configurações.
3. Execute `php bin/migrate.php` na raiz do projeto. Isso aplica as migrations pendentes, incluindo as migrations 005–011. Não execute simultaneamente em dois processos. A migration não foi executada durante a implementação.
4. Se o banco foi criado manualmente, confira a tabela `migrations` antes de executar: as migrations 001 a 004 já aplicadas precisam estar registradas pelo nome completo. Não registre migrations que ainda não foram aplicadas. DDL do MySQL não é transacional; após falha parcial, confira o esquema antes de repetir.
5. Configure `APP_URL` com a URL HTTPS do portal e mantenha as configurações SMTP existentes (`MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`).

## Cursos, turmas e expiração

Siga [Acesso por turma](turmas.md) para cadastrar turmas, configurar os subdomínios e agendar reconciliação. Cursos vêm do catálogo Hotmart: apenas `ACTIVE` cria cursos; demais status inativam existentes. Curso inativo bloqueia somente novas turmas, não o acesso de alunos. Apenas a descrição é editável no admin. Ferramentas e expiração são definidos nas turmas, com uma turma atual por aluno e curso. A data de garantia da compra não define vencimento do acesso.

## Webhook na Hotmart

Em Ferramentas > Webhook, crie uma configuração para cada produto integrado:

- URL: `https://SEU-DOMINIO/webhooks/hotmart`
- Versão: `2.0.0`
- Copie o Hottok da Hotmart para `HOTMART_HOTTOK` no `.env`.
- Selecione os eventos abaixo.

| Evento | Efeito na transação |
| --- | --- |
| `PURCHASE_APPROVED`, `PURCHASE_COMPLETE` | Registra compra ativa e solicita reconciliação da turma |
| `PURCHASE_REFUNDED` | Reembolso; bloqueia a transação |
| `PURCHASE_CHARGEBACK` | Chargeback; bloqueia a transação |
| `PURCHASE_CANCELED` | Cancela a transação |
| `PURCHASE_PROTEST`, `PURCHASE_DELAYED` | Suspende a transação |
| `PURCHASE_EXPIRED` | Expira a transação |

Eventos não listados retornam `ignored_event` e não alteram acesso. Boleto emitido não libera curso. O endpoint exige o cabeçalho `X-HOTMART-HOTTOK`; não aceita segredo pela URL ou pelo JSON.

O processamento grava evento, compra, aluno e acesso na mesma transação do banco. O ID do evento evita duplicação. Eventos anteriores ao estado registrado são ignorados. Reembolso e chargeback impedem reativação posterior da mesma transação. Uma nova compra pode liberar acesso novamente.

Se houver outra compra ativa do mesmo aluno e curso, um reembolso não elimina esse direito de compra. A matrícula aguarda reconciliação do Club e só libera ferramentas com turma cadastrada e dentro do prazo. Bloqueios de usuários e exclusões continuam valendo. A inativação de um curso bloqueia somente novas turmas.

## Primeiro acesso do aluno

A aprovação cria uma conta `ALUNO` pelo e-mail do comprador, com senha aleatória não divulgada. Contas existentes mantêm senha, papel e bloqueios.

Oriente o comprador a abrir `https://SEU-DOMINIO/password/forgot`, informar o e-mail da compra e definir a senha pelo link recebido. Não existe disparo automático de boas-vindas nesta versão. A recuperação de senha já existente envia o e-mail quando solicitada.

## API de vendas

Em Ferramentas > Credenciais Developers, gere `client_id`, `client_secret` e Basic. Preencha `HOTMART_CLIENT_ID`, `HOTMART_CLIENT_SECRET` e `HOTMART_BASIC`. O Basic pode ser informado com ou sem o prefixo `Basic `.

Use `HOTMART_API_ENV=production` para produção ou `sandbox` com credenciais próprias de sandbox. Habilite a extensão PHP cURL. O cliente mantém o token em memória durante o processo e tenta renovar uma vez quando recebe HTTP 401.

Consulte uma transação:

```bash
php bin/hotmart-sales.php --transaction=HP123456789
```

Consulte uma página de vendas:

```bash
php bin/hotmart-sales.php --product_id=1234567 --transaction_status=APPROVED --max_results=50
```

Para continuar, repita os filtros com `--page_token=TOKEN` usando `page_info.next_page_token` da resposta. Também são aceitos `buyer_email`, `start_date` e `end_date` (datas em milissegundos Unix). Sem filtro de status ou transação, a API retorna vendas aprovadas e completas por padrão.

Esse comando apenas consulta; não importa vendas nem modifica acessos. A resposta contém dados pessoais: não a publique. Para compras antigas, use o reenvio dos eventos na Hotmart após cadastrar o produto. Uma importação em massa exige conciliação própria, porque a API de vendas identifica produto pelo ID numérico e o portal usa ucode.

## Validação manual

Nenhum teste automatizado foi executado. Use banco de homologação e endereço HTTPS acessível pela Hotmart. `localhost` precisa de um túnel HTTPS para receber eventos externos.

Use o roteiro de [validação manual de turmas](turmas.md#validação-manual). Webhooks fictícios não identificam necessariamente um aluno real no Club; nesses casos, a matrícula deve permanecer pendente.

Confira também Hottok incorreto (HTTP 401), reenvio do evento (`duplicate`), evento antigo (`ignored_stale`) e consulta de vendas com credenciais do ambiente correto.

## Diagnóstico

- `401`: cabeçalho Hottok ausente ou incorreto.
- `400`: JSON, versão ou campos obrigatórios inválidos.
- `413`: corpo maior que 1 MiB.
- `503`: integração sem segredo, migrations pendentes ou falha de processamento. Corrija e reenvie pela Hotmart; eventos com falha não são marcados como concluídos.
- `200 ignored_event`: tipo sem regra nesta versão.
- `200 ignored_stale`: evento antigo ou tentativa de reativar uma transação reembolsada.

Os registros mínimos ficam em `hotmart_events` e `hotmart_purchases`; não armazenamos payload bruto nem Hottok. Falhas internas registram somente a classe do erro no log PHP. Consulte o histórico de envios da Hotmart para reenviar após corrigir a configuração.

## Referências oficiais

- [Webhook de compras 2.0.0](https://developers.hotmart.com/docs/pt-BR/2.0.0/webhook/purchase-webhook/)
- [Autenticação OAuth](https://developers.hotmart.com/docs/pt-BR/start/app-auth/)
- [Histórico de vendas](https://developers.hotmart.com/docs/es/v1/sales/sales-history)


## Inspeção do payload sem processar compras

Para conferir os dados enviados, publique a rota `POST /webhooks/hotmart/preview`.
No `.env` do servidor, configure `HOTMART_HOTTOK` com o segredo da Hotmart e
`HOTMART_PREVIEW_ENABLED=true`. Essa rota é separada do processamento normal.
Ela valida o Hottok, o limite do corpo e o JSON, mas não cadastra alunos, não
registra transações, não envia e-mails e não altera acessos. A infraestrutura
normal do portal ainda precisa estar disponível.

Crie uma configuração Hotmart de teste usando a URL
`https://aluno.leilabrito.com.br/webhooks/hotmart/preview`, versão 2.0.0.
Não use a URL `/webhooks/hotmart` para essa inspeção: ela processa compras.
Envie um teste pela Hotmart e confira o payload nos detalhes da notificação,
na aba Histórico. A resposta esperada é HTTP 200 com
`{"status":"preview_received","processed":false}`.
O portal não armazena nem disponibiliza uma cópia do payload. Se o teste não
aparecer no Histórico, confira os detalhes do resultado do teste na Hotmart.

O teste serve para conhecer a estrutura do JSON; valores fictícios não comprovam
configurações reais de vencimento do produto. Não publique Hottok ou dados de
compradores ao compartilhar o JSON. Depois da inspeção, desative essa configuração
na Hotmart e defina `HOTMART_PREVIEW_ENABLED=false`. Eventos enviados ao preview
não serão processados automaticamente depois: precisam ser reenviados para a
rota normal quando a integração estiver configurada.


## Identificador do comprador

O campo `users.hotmart_buyer_ucode` armazena `data.buyer.ucode` dos webhooks de compra, equivalente ao `buyer.ucode` da API de vendas. Não use o `user_id` do Club nesse campo. O cadastro preenche o ucode quando disponível, inclusive em contas existentes cujo campo está vazio. Eventos sem ucode não apagam um valor já conhecido. Divergências e identificadores associados a outra conta interrompem a operação, sem fundir contas ou substituir identidade.

A migration 011 renomeia `hotmart_buyer_id` e seu índice único, preservando valores. Publique código e migration em uma janela sem processos antigos: consultas da versão anterior usam a coluna antiga. Na Hostinger, execute `php bin/migrate.php` antes de reabrir o portal. Para rollback, interrompa tráfego e jobs e restaure código e banco da mesma versão a partir do backup. Não preencha dados antigos com identificadores presumidos; obtenha o ucode de uma resposta de compra verificada.
