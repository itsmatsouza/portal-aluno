# Ferramentas ELO

O catálogo `database/tools-catalog.json` contém Diagnóstico do Imóvel ELO e INSS de Obra — Planejamento Estratégico. Os HTMLs ficam em `storage/tools/`, fora da raiz pública, e usam as rotas autenticadas existentes. Os arquivos originais da raiz são mantidos como referência.

## Publicação

1. Preserve backup do banco e da versão publicada.
2. Publique esta versão aplicando a migration `012_retire_test_tool.sql`. Ela inativa e exclui logicamente apenas `diagnostico-precificacao`, preservando seu registro e vínculos. Pode ser repetida sem alterar a data da primeira exclusão.
3. Execute `php bin/sync-tools.php import`. O deploy Hostinger já executa esse comando; selecione a opção de aplicar migrations ao publicar.
4. Em **Turmas**, selecione as novas ferramentas para cada turma autorizada. A importação não copia permissões da ferramenta de teste.

O Diagnóstico do Imóvel usa html2canvas e jsPDF pelo CDN cdnjs para exportação de relatórios. Essa dependência já está presente no HTML fornecido. O planejamento de INSS não carrega bibliotecas externas.

## Reversão

Para desfazer a publicação, restaure a versão anterior e o backup correspondente do banco. Restaurar apenas o código não reativa o registro excluído logicamente. Os arquivos originais fornecidos não foram alterados.
