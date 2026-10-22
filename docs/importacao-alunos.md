# Importação inicial dos alunos da Hotmart

O comando `bin/hotmart-import-students.php` cadastra compradores, transações e matrículas a partir de vendas atualmente `APPROVED` ou `COMPLETE`. Não importa compras canceladas, reembolsadas, contestadas ou ainda não aprovadas como direitos de acesso. O vencimento continua sendo a data de aprovação original mais os dias configurados na turma.

## Preparação na Hostinger

1. Publique o código com o novo comando e serviço. Esta funcionalidade não exige migration adicional; o banco precisa das migrations anteriores até a 011.
2. Faça backup do banco antes da carga inicial.
3. Configure credenciais OAuth e `HOTMART_COURSE_SUBDOMAINS` no `.env` privado.
4. Sincronize cursos com `hotmart-courses.php` e cadastre as turmas com seus IDs reais, duração e ferramentas.
5. Mantenha o webhook funcionando para capturar alterações de compra ocorridas durante e depois da importação.

Entre por SSH e use a versão publicada:

```bash
cd /home/u454601756/domains/aluno.leilabrito.com.br
cd "$(dirname "$(readlink -f public_html)")"
```

Execute primeiro uma carga limitada, substituindo e-mail e período pelos dados desejados:

```bash
/opt/alt/php83/usr/bin/php bin/hotmart-import-students.php --from=2026-01-01 --to=2026-10-01 --email=aluno@example.com
/opt/alt/php83/usr/bin/php bin/hotmart-sync-classes.php --email=aluno@example.com
```

Depois, importe todos os compradores do período. A data inicial abaixo é apenas exemplo: informe uma data anterior à primeira venda que deseja importar. Informe a data atual como final. Ambas são inclusivas, no horário de Brasília; a data final não pode estar no futuro.

```bash
/opt/alt/php83/usr/bin/php bin/hotmart-import-students.php --from=2020-01-01 --to=2026-10-01
/opt/alt/php83/usr/bin/php bin/hotmart-sync-classes.php --pending
```

O filtro opcional `--product-id=5856194` usa o ID numérico do produto Hotmart. O importador consulta o catálogo para relacionar esse ID ao ucode usado pelo portal. E-mails retornados pela API são comparados por igualdade antes de importar.

## Comportamento e repetição

- O período é dividido em meses e todas as páginas de cada status são percorridas. O comando não depende do período ou status padrão da API.
- O catálogo é apenas consultado. O importador não cria nem reativa cursos. Curso inativo já cadastrado pode receber matrículas, conforme a regra de acesso existente. Curso ausente gera pendência no resumo; sincronize o catálogo e repita. Produtos inativos ausentes continuam sem cadastro, conforme a regra de importação de cursos.
- O nome é normalizado e `buyer.ucode` é armazenado. Contas existentes mantêm nome, senha, papel e bloqueios. Ucodes conflitantes interrompem a compra e desfazem suas alterações.
- Cada compra é gravada em uma transação do banco. Reexecutar o período não duplica usuários, compras ou matrículas.
- Transações já conhecidas são preservadas integralmente, inclusive reembolsos e chargebacks recebidos por webhook. Este comando é uma carga inicial, não uma reconciliação dos estados financeiros já registrados.
- Uma compra antiga importada depois de uma renovação não substitui a data de compra mais recente. Várias transações ficam separadas, com uma matrícula por aluno e curso.
- Matrículas sem transação ou com outra transação ainda não conciliada são preservadas e sinalizadas para revisão. Matrículas antigas não ativas da mesma transação não são reativadas.
- Matrículas novas ou alteradas aguardam confirmação no Club. Turma desconhecida permanece pendente, mesmo que a compra esteja aprovada. Uma repetição não limpa turma já confirmada.
- O comando não envia e-mails e não altera dados na Hotmart. O aluno usa recuperação de senha para definir sua senha inicial.

Se houver interrupção, registros concluídos permanecem salvos. Corrija a causa e repita o mesmo período; o processo ignora transações existentes. Um bloqueio no banco impede duas importações simultâneas. Não apague matrículas para tentar novamente.

## Resultado

O resumo conta compras importadas e transações existentes, não alunos únicos. Curso ou produto ausente e matrícula preservada para revisão resultam em código de saída 1. Falhas de API, paginação, identidade ou formato interrompem a execução com erro. Nenhum payload, senha ou lista de e-mails é impresso.

Após importar, execute a sincronização do Club e confira **Usuários**, **Acessos**, turma, data de compra e vencimento. Configure os crons regulares depois da carga inicial. A carga não cobre compras fora do período informado nem alunos gratuitos/importados no Club sem compra correspondente.

## Simulação local sem gravação

Use o mesmo importador com `--dry-run`:

```bash
php bin/hotmart-import-students.php --from=2026-06-01 --to=2026-06-30 --email=aluno@example.com --dry-run
```

O comando usa o mesmo catálogo, filtros, paginação e validação de compras da importação. Também consulta o Club pelo mesmo método usado pela sincronização, incluindo `class_id` e status. A conexão SQL fica em modo somente leitura; nenhum aluno, compra ou matrícula é gravado.

A saída padrão contém um JSON; o progresso sai em stderr. `sale` contém a compra, `club.response` contém o aluno retornado pelo Club e `local` contém os registros atuais do portal, incluindo a turma correspondente. `pending_reason` informa falhas de consulta ou ausência de curso/turma. `purchase_expires_at` calcula a validade de cada compra usando a duração da turma atual; é nulo para vitalício ou turma desconhecida (consulte `local.class`).

Essa resposta mostra os dados de entrada e o estado local, não promete substituir matrículas existentes: a importação preserva transações conhecidas e acessos manuais e escolhe a compra ativa mais recente. A sincronização continua sendo uma etapa separada. O Club informa a turma atual, não a turma histórica de cada compra. `complete: true` indica que a coleta terminou; confira também as pendências individuais. Falhas gerais retornam `complete: false` e código de saída 1.

Para salvar o JSON, redirecione stdout para um arquivo privado fora de `public/`. A resposta contém dados pessoais. Remover `--dry-run` executa a importação real.
