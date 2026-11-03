# Ferramentas ELO

O catálogo `database/tools-catalog.json` contém seis ferramentas, nesta ordem:

1. **01 - Controle do Projeto Legal ELO** — `controle-projeto-legal`
2. **02 - Diagnóstico do Imóvel ELO** — `diagnostico-imovel`
3. **03 - Precificadora ELO** — `precificadora`
4. **04 - INSS de Obra · Planejamento Estratégico** — `inss-obra-planejamento-estrategico`
5. **05 - Simular Cadastrar Aferir INSS de Obra** — `inss-obra-simular-cadastrar-aferir`
6. **06 - REURB ELO** — `reurb`

Os HTMLs ficam em `storage/tools/`, fora da raiz pública, e usam as rotas autenticadas existentes. Os arquivos originais da raiz são mantidos como referência. As novas ferramentas aproveitam a tela cheia do visualizador e a ordenação numérica do dashboard.

## Publicação

1. Preserve backup do banco e da versão publicada.
2. Publique esta versão aplicando a migration `012_retire_test_tool.sql`. Ela inativa e exclui logicamente apenas `diagnostico-precificacao`, preservando seu registro e vínculos. Pode ser repetida sem alterar a data da primeira exclusão.
3. Execute `php bin/sync-tools.php import`. O deploy Hostinger já executa esse comando; selecione a opção de aplicar migrations ao publicar.
4. Em **Turmas**, selecione as novas ferramentas para cada turma autorizada. A importação não copia permissões da ferramenta de teste.

O Diagnóstico do Imóvel usa html2canvas e jsPDF pelo CDN cdnjs para exportação de relatórios. Essa dependência já está presente no HTML fornecido. A Precificadora usa ExcelJS 4.4.0, também pelo cdnjs, para exportação de planilhas. Controle do Projeto Legal, planejamento de INSS, Simular/Cadastrar/Aferir e REURB não carregam bibliotecas externas.

## Reversão

Para desfazer a publicação, restaure a versão anterior e o backup correspondente do banco. Restaurar apenas o código não reativa o registro excluído logicamente. Os arquivos originais fornecidos não foram alterados.


## Nome e ordem no dashboard

Use o número no início do campo `name` do catálogo, por exemplo `02 - Diagnóstico do Imóvel ELO`. O dashboard exibe esse nome completo. O `<title>` do HTML e o nome do catálogo devem ser mantidos consistentes ao adicionar novas ferramentas; mudar apenas o HTML não atualiza o cadastro no banco.

A listagem de ferramentas para alunos e administradores usa a ordem numérica do prefixo (`2`, `04`, `10`), inclusive quando reúne vários cursos. Ferramentas sem prefixo numérico aparecem depois, por nome. Empates usam o nome e o ID da ferramenta. Não é necessário preencher lacunas na sequência nem alterar slugs, vínculos ou permissões.

Após atualizar o catálogo, execute `php bin/sync-tools.php import` no ambiente desejado. O deploy já executa essa importação, preservando os IDs e vínculos das ferramentas existentes.
