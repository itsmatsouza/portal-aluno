# Retornos das APIs Hotmart

## Objetivo e origem

Este documento complementa [hotmartWebhookTestResponses.md](hotmartWebhookTestResponses.md). Registra as respostas e limitações verificadas durante a investigação, antes da implementação de turmas no portal.

Consolidação: 30/09/2026. Os exemplos abaixo vêm dos resultados registrados na conversa. O arquivo temporário que reunia Club e cinco vendas não estava mais disponível ao criar este documento; portanto, não reproduzimos suas cinco transações nem inventamos os campos ausentes. Nome, email, identificadores pessoais e transação do exemplo de vendas foram substituídos. Tokens e credenciais não estão incluídos.

## APIs previstas

| Recurso | Uso | Situação |
| --- | --- | --- |
| OAuth | Obter access token para as consultas | Autenticação validada; token não documentado |
| Histórico de vendas | Compradores, produtos, ofertas e transações; carga inicial e reconciliação | HTTP 200 confirmado |
| Alunos do Club | Turma atual e estado do aluno no contexto consultado | HTTP 200 confirmado para o subdomínio do ELO |
| Catálogo de produtos | Cadastrar também produtos sem vendas no período importado | Complementar; retorno real ainda não coletado |
| Assinaturas | Complementar o ciclo de assinaturas, caso incluído no escopo | Retorno real ainda não coletado |

A API é consultada pelo portal; os webhooks enviam notificações ao portal. A integração não deve confundir essas duas formas de atualização.

## 1. Autenticação OAuth

`POST https://api-sec-vlc.hotmart.com/security/oauth/token`

Credenciais locais: `HOTMART_CLIENT_ID`, `HOTMART_CLIENT_SECRET` e `HOTMART_BASIC`. O serviço existente obtém o access token e usa `Authorization: Bearer <access_token>` nas consultas. `HOTMART_HOTTOK` é destinado à validação dos webhooks, não à substituição desse token.

Resposta ilustrativa de estrutura, não uma captura real:

```json
{
  "access_token": "<omitido>",
  "token_type": "bearer",
  "expires_in": 172799,
  "scope": "read write",
  "jti": "<omitido>"
}
```

Não depender do valor ilustrativo de `expires_in`: usar o valor recebido em cada autenticação.

Referência: https://developers.hotmart.com/docs/pt-BR/start/app-auth/

## 2. Histórico de vendas

`GET https://developers.hotmart.com/payments/api/v1/sales/history`

Primeira consulta registrada: `max_results=1`. Ela não tinha filtro de aluno ou produto. O item abaixo corresponde ao comprador posteriormente consultado no Club.

### Resposta real anonimizada

```json
{
  "page_info": {
    "results_per_page": 1,
    "next_page_token": "eyJwYWdlIjoyLCJyb3dzIjoxfQ==",
    "total_results": 40
  },
  "items": [
    {
      "producer": {
        "name": "Produtor exemplo",
        "ucode": "00000000-0000-4000-8000-000000000001"
      },
      "buyer": {
        "name": "Aluno exemplo",
        "ucode": "00000000-0000-4000-8000-000000000002",
        "email": "aluno@example.com"
      },
      "product": {
        "name": "Formação Estrategista Legal de Obras - ELO",
        "id": 5856194
      },
      "purchase": {
        "tracking": {
          "source_sck": "aberturarelampago"
        },
        "order_date": 1788010023000,
        "is_subscription": false,
        "price": {
          "currency_code": "BRL",
          "value": 288.65
        },
        "payment": {
          "installments_number": 9,
          "type": "CREDIT_CARD",
          "method": "CREDIT_CARD_VISA"
        },
        "approved_date": 1788010026000,
        "offer": {
          "payment_mode": "UNIQUE_PAYMENT",
          "code": "53bxa2hu"
        },
        "hotmart_fee": {
          "fixed": 1,
          "total": 23.8,
          "percentage": 7.9,
          "base": 288.65,
          "currency_code": "BRL"
        },
        "status": "COMPLETE",
        "warranty_expire_date": 1788566400000,
        "recurrency_number": 4,
        "transaction": "HP_EXEMPLO_001",
        "commission_as": "PRODUCER"
      }
    }
  ]
}
```

### Consulta posterior do mesmo aluno e curso

Parâmetros usados: `buyer_email=<email do aluno>`, `product_id=5856194`, `max_results=100`. Resultado observado: **cinco vendas, em uma página**. Os corpos completos dessas cinco vendas precisam ser coletados novamente se forem necessários para comparação detalhada.

As consultas usaram período e status padrão da API. Os 40 resultados da primeira consulta não representam necessariamente todos os compradores da conta, e cinco vendas não significam cinco matrículas.

### Interpretação

- `buyer.ucode` e `buyer.email` identificam o comprador no retorno de vendas.
- `product.id` identifica o produto; o retorno observado não contém seu `ucode`.
- `purchase.transaction` identifica a transação. Preservar várias transações do mesmo aluno e produto.
- `purchase.offer.code` identifica a oferta; não é o identificador da turma.
- `purchase.status` representa o estado da compra.
- `approved_date` e `order_date` são datas da compra. O exemplo contém timestamps de 13 dígitos.
- `warranty_expire_date` é a data de garantia, não de vencimento do acesso.
- `is_subscription=false` não significa vitalício.
- O retorno observado não contém turma nem vencimento da matrícula.
- Usar `page_info.next_page_token` para continuar a paginação. Na carga inicial, definir período e estados explicitamente.

Referência: https://developers.hotmart.com/docs/en/v1/sales/sales-history/

## 3. Alunos do Club

`GET https://developers.hotmart.com/club/api/v1/users`

Parâmetros validados:

```text
subdomain=formacaoestrategistalegaldeobr
email=<email do mesmo aluno da consulta de vendas>
```

Resultado observado: **HTTP 200, um aluno com email correspondente, sem próxima página**.

### Trecho real do item retornado

Este bloco é um recorte do aluno, não o corpo completo da resposta:

```json
{
  "type": "BUYER",
  "status": "ACTIVE",
  "purchase_date": 1780016044000,
  "class_id": "pRON0X1EeP"
}
```

Campos registrados no item completo:

```text
progress, engagement, is_deletable, locale, role, type, status,
user_id, purchase_date, first_access_date, email, name,
last_access_date, access_count, plus_access, class_id
```

A estrutura da listagem utiliza `items` e `page_info`. O exemplo de documentação com usuários “Hotmart Example User” não é uma captura da conta e não deve ser usado como prova de dados reais.

### Interpretação

- `class_id` permite relacionar o aluno à turma Hotmart.
- O admin deverá associar esse identificador à turma local, no contexto correto de curso/subdomínio.
- `status=ACTIVE` informa estado atual; não define duração nem acesso vitalício.
- `type=BUYER` distingue esse vínculo de outros tipos, como importados e gratuitos.
- O item observado não contém `product_id`, nome da turma ou vencimento do acesso.
- Não presumir que `user_id` do Club seja igual a `buyer.ucode` das vendas.
- O filtro `email` pode aceitar correspondência parcial; conferir igualdade do email antes de associar registros.
- Não promover usuários a administradores do portal com base no papel retornado pelo Club.
- O subdomínio público `leilabrito` falhou; `formacaoestrategistalegaldeobr` funcionou. Registrar o mapeamento por curso, sem presumir que todos os produtos usem o mesmo identificador.

Referência: https://developers.hotmart.com/docs/en/v1/club/get-users-club/

## 4. Comparação das datas observadas

| Campo | Valor registrado |
| --- | --- |
| Club: `purchase_date` | `1780016044000` |
| Venda exemplificada: `order_date` | `1788010023000` |
| Venda exemplificada: `approved_date` | `1788010026000` |

As datas diferem. A consulta posterior encontrou cinco vendas do mesmo comprador e produto. Isso exige examinar as transações antes de atribuir a matrícula a uma compra específica. Não foi comprovado se a diferença resulta de recompra, renovação ou outro comportamento da plataforma.

Os exemplos de documentação vistos anteriormente usam timestamps de 10 dígitos em alguns campos; o retorno real acima usa 13. Validar a unidade de cada campo antes de converter, sem aplicar uma unidade global a todas as APIs.

## 5. Catálogo de produtos e assinaturas: coleta pendente

### Produtos

`GET https://developers.hotmart.com/products/api/v1/products`

Uso proposto: obter o catálogo completo, inclusive produtos sem vendas no período, e conferir o relacionamento entre ID numérico e ucode. Não há resposta real coletada neste documento. Não tratar módulos de conteúdo como produtos.

Referência: https://developers.hotmart.com/docs/en/v1/product/product-list/

### Assinaturas

`GET https://developers.hotmart.com/payments/api/v1/subscriptions`

Uso complementar, se houver assinaturas no escopo: consultar os vínculos de assinatura e reconciliar seu estado. Não há resposta real coletada neste documento. Não interpretar automaticamente próxima cobrança como vencimento de toda matrícula.

Referência: https://developers.hotmart.com/docs/pt-BR/v1/subscription/get-subscribers/

## 6. Consultas investigadas que não sustentam o fluxo atual

### Módulos e páginas

- `GET /club/api/v1/modules?subdomain=...&is_extra=false`: módulos de conteúdo.
- `GET /club/api/v2/modules/{module_id}/pages?product_id=...`: páginas e configurações de liberação/expiração de conteúdo.

A consulta de módulos com `subdomain=leilabrito` retornou HTTP 404, código `0006`, com a mensagem `Subdomain leilabrito does not exist`. Neste registro, ainda não há coleta de módulos com o subdomínio corrigido.

Os exemplos de páginas incluem turmas em `dripping_configs.classes`, mas não comprovam uma listagem completa de turmas. Expiração de uma página não deve ser aplicada automaticamente à matrícula inteira. Esses endpoints não são necessários para definir ferramentas, pois essa configuração será administrativa no portal.

### Endpoint usado pelo painel

`GET https://api-club-class.cb.hotmart.com/v5/classes/`

Header de contexto usado: `club: formacaoestrategistalegaldeobr`.

Resultados observados, apresentados como resumo dos campos relevantes:

| Credencial enviada como Bearer | HTTP | `error` |
| --- | --- | --- |
| Access token OAuth | 401 | `invalid_token` |
| HOTMART_HOTTOK | 401 | `invalid_token` |

Nenhuma resposta de sucesso foi obtida. Não incluir esse endpoint como dependência da integração nem armazenar token de sessão do painel como solução permanente.

## 7. Responsabilidades propostas no portal

1. Importar compradores, produtos e transações pelas APIs, com paginação e período explícito.
2. Consultar o Club para obter turma e estado do aluno, preservando o contexto do curso.
3. Permitir ao admin cadastrar turmas, associar `class_id`, definir vencimento e ferramentas.
4. Manter vínculos com turma desconhecida pendentes de configuração; ausência de prazo não significa vitalício.
5. Usar webhooks de compra para atualizar transações e solicitar reconciliação do aluno.
6. Consultar periodicamente o Club para detectar mudança de turma. Não foi confirmado webhook específico de transferência de turma.
7. Preservar configurações administrativas de ferramentas e vencimento durante sincronizações.
8. Separar transação e matrícula: o reembolso de uma compra não deve invalidar outro direito de acesso ainda válido.

Este documento registra evidências e decisões propostas. Não significa que a sincronização, a carga inicial ou o modelo de turmas já estejam implementados.
