# Acesso por turma

Cada aluno possui uma matrícula por curso e uma única turma atual por matrícula. O `class_id` recebido do Club corresponde exatamente ao ID Hotmart cadastrado na turma, dentro do mesmo curso. O ID interno é gerado pelo banco. Ferramentas e expiração pertencem exclusivamente à turma.

O cadastro exige uma duração de 1 a 36500 dias ou a escolha explícita de acesso vitalício. O vencimento individual é `purchased_at + access_days`, preservando o horário da compra no fuso `America/Sao_Paulo`. A data de cadastro da turma não participa do cálculo. Uma compra em 10/03/2026 às 14:00, numa turma de 365 dias, vence em 10/03/2027 às 14:00. Na renovação, prevalece a compra ativa mais recente pela data de compra, não pela ordem de chegada dos webhooks. Alterações de duração ou ferramentas afetam imediatamente todos os alunos da turma.

## Preparação

1. Faça backup do banco e publique numa janela sem processos da versão anterior. Não execute webhooks ou sincronizadores antigos em paralelo com esta versão.
2. Execute `php bin/migrate.php`. As migrations 006–008 criam turmas e vínculos. As migrations 009–010 adicionam duração em dias e atualizam a view de autorização. Não convertem permissões antigas. Conforme definido para este ambiente de testes, não há migração automática de matrículas antigas para turmas.
3. Configure as credenciais Hotmart existentes e o mapeamento de subdomínios abaixo no `.env`.
4. Para cadastrar cursos antes da primeira compra, execute `php bin/hotmart-courses.php`. O comando consulta todas as páginas do catálogo. Somente produtos com status `ACTIVE` criam cursos. Outros status inativam cursos já cadastrados, sem criar novos. Produtos que voltam a `ACTIVE` reativam o curso. Descrições e turmas são preservadas; produtos ausentes da resposta não são presumidos inativos.
5. Em **Turmas**, cadastre nome, curso, ID Hotmart, dias ou vitalício e selecione ferramentas. Curso e ID Hotmart ficam fixos após o cadastro para impedir transferência acidental de matrículas para outra identidade.
6. Agende a reconciliação do Club. Sem esse agendamento, compras novas permanecem pendentes.

O mapeamento usa `product.ucode` como chave. A descrição continua sendo o único campo editável na tela de cursos:

```dotenv
HOTMART_COURSE_SUBDOMAINS='{"UCODE_DO_CURSO":"formacaoestrategistalegaldeobr"}'
```

Acrescente uma entrada por curso, usando seu contexto correto no Club. Não use o ID numérico do produto ou o nome público do produtor como substituto do subdomínio.

O catálogo valida `items`, `name`, `ucode` e paginação. Os documentos fornecidos ainda não contêm captura real desse endpoint; valide essa consulta na conta. Contratos inesperados interrompem a importação sem inventar identificadores. A importação é repetível e não cria turmas. `hotmart-sales.php` continua sendo uma consulta de vendas; não importa matrículas históricas.

## Reconciliação

```bash
php bin/hotmart-sync-classes.php --pending
php bin/hotmart-sync-classes.php
php bin/hotmart-sync-classes.php --email=aluno@example.com
```

O primeiro comando consulta pendências. O segundo consulta todas as matrículas e detecta troca de turma, renovação e mudanças de status no Club. O filtro opcional `--email` restringe a execução ao comprador informado, permitindo testar contas reais sem misturar matrículas fictícias locais. Exemplo de cron, ajustando caminho e executável PHP:

```cron
* * * * * cd /caminho/portal-aluno && php bin/hotmart-sync-classes.php --pending
*/5 * * * * cd /caminho/portal-aluno && php bin/hotmart-sync-classes.php
```

Um bloqueio no banco impede execuções simultâneas. Uma execução concorrente termina com código 1 e será retomada no próximo agendamento. A consulta percorre todas as páginas e exige igualdade do e-mail, pois o filtro da API pode aceitar correspondência parcial. Resultados anteriores a outro webhook ou a outra reconciliação concluída não sobrescrevem a matrícula.

O webhook registra a compra sem esperar pela rede do Club. Marca a matrícula para reconciliação e bloqueia acesso até a consulta. Preserva idempotência e proteção contra eventos antigos. Reembolso ou chargeback de uma transação não elimina outra compra ativa do mesmo aluno e curso; o Club também precisa confirmar acesso ativo.

Turma desconhecida, e-mail ausente ou ambíguo, erro de API e falta de configuração não concedem acesso vitalício nem mantêm a autorização da turma anterior. **Acessos** mostra a pendência, o ID observado e a última consulta concluída. Quando o Club já confirmou uma turma desconhecida, cadastrar seu ID resolve o vínculo. Erros de consulta exigem nova reconciliação.

Transferências no Club entram em vigor na próxima reconciliação completa. A expiração local é verificada em cada autorização e não depende do cron. Status diferente de `ACTIVE` no Club bloqueia acesso mesmo para uma turma vitalícia.

`sync-tools.php` importa e exporta somente ferramentas globais. Não cria relações com cursos ou turmas. Selecione ferramentas em **Turmas**.

## Validação manual

- Cadastre duas turmas do mesmo curso com ferramentas e prazos diferentes. Confira curso obrigatório, ID duplicado e escolha obrigatória de duração.
- Receba uma compra aprovada e sincronize. Confira turma, ferramentas e prazo no aluno e em Acessos.
- Troque a turma no Club e sincronize. Ferramentas exclusivas da turma anterior devem deixar de abrir, inclusive por URL direta.
- Compre outro curso para o mesmo aluno. As matrículas devem permanecer independentes.
- Confira turma desconhecida e depois cadastre seu ID no curso correto. Não deve haver vínculo com turma de outro curso.
- Confira compra mais dias já vencida, vitalício, falha de API, aluno inativo no Club e ferramenta globalmente inativa.
- Reenvie um webhook e envie um evento antigo. Confira ausência de duplicações e preservação da situação mais recente.
- Confira reembolso quando existe outra compra ativa e quando não resta compra ativa.
- Tente alterar nome, status ou identificador do curso por POST. Apenas a descrição deve mudar.

## Reversão e recuperação

Tabelas e colunas antigas foram preservadas, mas não autorizam mais acesso. A view `enrollment_access` centraliza situação efetiva e prazo. Não volte isoladamente ao código antigo: ele não conhece turmas e pode interpretar prazos antigos nulos como acesso vitalício. Para rollback, interrompa tráfego e jobs e restaure código e backup do banco da mesma versão juntos.

As migrations são registradas individualmente. A 006 usa `IF NOT EXISTS`; a 007 adiciona campos e chave estrangeira num único `ALTER TABLE`; a 008 usa `CREATE OR REPLACE VIEW`. Se houver interrupção entre o DDL e seu registro na tabela `migrations`, confira o esquema antes de repetir a 007. Não ignore erro de coluna duplicada nem apague dados para contorná-lo.

Na Hostinger, aplique todas as migrations pendentes antes de usar esta versão. Verificação de sintaxe não comprova contratos reais de APIs nem execução do SQL na versão de MySQL da hospedagem.


## Transição de data fixa para dias

A coluna antiga `course_classes.expires_at` é preservada para histórico, mas não participa mais do acesso. Turmas vitalícias continuam vitalícias. Uma turma antiga por data fixa precisa ter seus dias definidos pelo admin: não há conversão automática, pois a data fixa não informa a duração pretendida. Enquanto faltar duração ou data da compra, o acesso finito permanece pendente.

As migrations 009 e 010 podem ser repetidas após falha parcial: a primeira verifica existência da coluna antes do ALTER e a segunda recria a view. A 009 adapta a remoção da restrição antiga a MySQL e MariaDB.

## Dados fictícios no ambiente local

Produtos `TEST_UCODE_001` e `TEST_UCODE_002` e turma `teste` não representam vínculos reais do Club. Cadastrar uma turma não inscreve automaticamente todos os alunos do curso. Para testar integração real, use compras verificadas da API, o produto correspondente do catálogo e o `class_id` real do comprador. A execução da sincronização informa a causa da falha, incluindo o produto sem subdomínio configurado.


## Status do curso na Hotmart

Execute `php bin/hotmart-courses.php` periodicamente para refletir mudanças de status. Curso inativo impede somente novas turmas, inclusive em requisições POST diretas. Turmas existentes continuam editáveis e os alunos mantêm acesso conforme matrícula, status do Club e duração individual. A inativação não altera data de compra, prazo ou ferramentas. Exclusão lógica continua sendo uma condição diferente de inativação.

Webhooks não informam o status do catálogo e não criam ou reativam cursos. Se o curso ainda não existe, sincronize o catálogo e reenvie o evento. Cursos existentes inativos continuam recebendo eventos de compra e reconciliação normalmente. Respostas com status ausente ou inválido interrompem a importação.
