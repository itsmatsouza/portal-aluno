# Integração Hotmart

A integração usa o webhook 2.0.0 para atualizar alunos e acessos. O cliente OAuth consulta vendas pela API. As credenciais da API são independentes do Hottok e não são necessárias para receber webhooks.

## Instalação

1. Faça backup do banco e publique os arquivos. A raiz pública do servidor deve ser `public/`.
2. Copie as variáveis de `config/hotmart.env.example` para o `.env` existente, sem substituir as demais configurações.
3. Execute `php bin/migrate.php` na raiz do projeto. Isso aplica as migrations pendentes, incluindo `005_create_hotmart_events.sql`. Não execute simultaneamente em dois processos. A migration não foi executada durante a implementação.
4. Se o banco foi criado manualmente, confira a tabela `migrations` antes de executar: as migrations 001 a 004 já aplicadas precisam estar registradas pelo nome completo. Não registre migrations que ainda não foram aplicadas. DDL do MySQL não é transacional; após falha parcial, confira o esquema antes de repetir.
5. Configure `APP_URL` com a URL HTTPS do portal e mantenha as configurações SMTP existentes (`MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`).

## Cursos e duração

Em **Administração > Cursos**, edite cada curso:

- Informe o `data.product.ucode` no identificador Hotmart. Não use o ID numérico do produto.
- Informe a duração em dias, por exemplo `365`. Deixe vazia para acesso vitalício.
- Ative o curso e associe suas ferramentas normalmente.

O prazo começa em `data.purchase.approved_date`. Na ausência desse campo, utiliza a data do evento. Alterar a duração do curso só afeta novas transações. O evento `PURCHASE_COMPLETE` e o reenvio de uma aprovação não reiniciam o prazo. A data de garantia/reembolso da Hotmart não é usada como vencimento do acesso.

O prazo configurado é único por curso. Ofertas com durações diferentes para o mesmo produto, vencimento em data fixa, combos e ciclo de cancelamento de assinaturas precisam de regras adicionais; não estão automatizados nesta versão. Compras recorrentes recebidas como aprovação seguem o prazo do curso por transação. Não configure curso recorrente como vitalício esperando bloqueio por cancelamento da assinatura.

## Webhook na Hotmart

Em Ferramentas > Webhook, crie uma configuração para cada produto integrado:

- URL: `https://SEU-DOMINIO/webhooks/hotmart`
- Versão: `2.0.0`
- Copie o Hottok da Hotmart para `HOTMART_HOTTOK` no `.env`.
- Selecione os eventos abaixo.

| Evento | Efeito na transação |
| --- | --- |
| `PURCHASE_APPROVED`, `PURCHASE_COMPLETE` | Libera acesso |
| `PURCHASE_REFUNDED` | Reembolso; bloqueia a transação |
| `PURCHASE_CHARGEBACK` | Chargeback; bloqueia a transação |
| `PURCHASE_CANCELED` | Cancela a transação |
| `PURCHASE_PROTEST`, `PURCHASE_DELAYED` | Suspende a transação |
| `PURCHASE_EXPIRED` | Expira a transação |

Eventos não listados retornam `ignored_event` e não alteram acesso. Boleto emitido não libera curso. O endpoint exige o cabeçalho `X-HOTMART-HOTTOK`; não aceita segredo pela URL ou pelo JSON.

O processamento grava evento, compra, aluno e acesso na mesma transação do banco. O ID do evento evita duplicação. Eventos anteriores ao estado registrado são ignorados. Reembolso e chargeback impedem reativação posterior da mesma transação. Uma nova compra pode liberar acesso novamente.

Se houver outra compra válida para o mesmo aluno e curso, um reembolso não remove esse acesso. Acesso manual, sem transação Hotmart, é preservado. Vínculos anteriores à integração com outra transação ainda não registrada são preservados e precisam de conciliação manual. Bloqueios e exclusões de usuários e cursos continuam valendo.

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

1. Configure curso com o ucode do produto de homologação e duração de um dia.
2. Envie uma aprovação pela ferramenta de teste da Hotmart. O e-mail do comprador precisa ser acessível por você. Se o teste usa produto fictício, cadastre o ucode desse payload no curso de homologação.
3. Confira HTTP 200 com `processed`, o aluno em `/admin/users` e o acesso em `/admin/access`.
4. Solicite recuperação de senha e entre no portal. Abra curso e ferramenta associada.
5. Reenvie o mesmo evento. Espere `duplicate`, sem novo aluno, vínculo ou alteração de vencimento.
6. Envie reembolso com novo ID de evento, data posterior e a mesma transação. O curso e suas ferramentas devem ficar indisponíveis.
7. Reenvie a aprovação antiga com outro ID. Espere `ignored_stale`, sem reativação.
8. Aprove outra transação para o mesmo comprador. O acesso deve voltar. Reembolsar a transação antiga não deve bloquear a nova.
9. Para conferir vencimento sem esperar, altere `access_expires_at` do vínculo de homologação em `user_courses` para uma data passada. Recarregue portal, curso e ferramenta: acesso deve ser negado. A expiração é verificada na consulta; não depende de cron, nem muda automaticamente o status armazenado para `EXPIRED`.
10. Repita com outro curso de duração vazia. Acesso deve ficar sem vencimento.
11. Envie uma requisição com Hottok incorreto. Espere HTTP 401, sem alteração no banco.
12. Execute a consulta da API com credenciais sandbox e confira a transação retornada.

## Diagnóstico

- `401`: cabeçalho Hottok ausente ou incorreto.
- `400`: JSON, versão ou campos obrigatórios inválidos.
- `413`: corpo maior que 1 MiB.
- `503`: integração sem segredo, produto não cadastrado, migrations pendentes ou falha de processamento. Corrija e reenvie pela Hotmart; eventos com falha não são marcados como concluídos.
- `200 ignored_event`: tipo sem regra nesta versão.
- `200 ignored_stale`: evento antigo ou tentativa de reativar uma transação reembolsada.

Os registros mínimos ficam em `hotmart_events` e `hotmart_purchases`; não armazenamos payload bruto nem Hottok. Falhas internas registram somente a classe do erro no log PHP. Consulte o histórico de envios da Hotmart para reenviar após corrigir a configuração.

## Referências oficiais

- [Webhook de compras 2.0.0](https://developers.hotmart.com/docs/pt-BR/2.0.0/webhook/purchase-webhook/)
- [Autenticação OAuth](https://developers.hotmart.com/docs/pt-BR/start/app-auth/)
- [Histórico de vendas](https://developers.hotmart.com/docs/es/v1/sales/sales-history)
