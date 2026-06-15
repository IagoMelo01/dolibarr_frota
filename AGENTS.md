# AGENTS.md

Diretivas para agentes e desenvolvedores que trabalharem no módulo Frota Agrícola.

## Contexto

Este repositório é um módulo customizado para Dolibarr 23 em `htdocs/custom/frota`.
O MVP controla máquinas, implementos, abastecimentos, manutenções e uso operacional,
reutilizando produtos, categorias, armazéns e movimentos de estoque nativos.

## Prioridades

1. Integridade de estoque e rastreabilidade antes de conveniência de tela.
2. APIs e padrões Dolibarr antes de SQL ou abstrações próprias.
3. Operações idempotentes: confirmar/concluir duas vezes nunca pode duplicar estoque.
4. Multi-entidade em todo dado próprio do módulo.
5. Escopo enxuto: não reintroduzir aluguel, seguro, reservatório ou compra de combustível no MVP.

## Regras de domínio

- `Veiculo` e `Implemento` são cadastros centrais e devem permanecer compatíveis com `CommonObject`.
- Abastecimento só movimenta estoque ao confirmar.
- Manutenção só movimenta estoque ao concluir.
- Registro confirmado/concluído não pode ser editado de forma que altere estoque.
- Nunca apagar ou alterar diretamente um movimento nativo em `llx_stock_mouvement`.
- Toda baixa deve usar `MouvementStock::livraison()` e registrar origem e movimento gerado.
- Validar produto físico, armazém aberto, entidade e saldo antes de baixar estoque.
- Leituras de horímetro/odômetro nunca podem reduzir o valor atual do veículo.
- Produtos, categorias e armazéns de seed usam referências fixas e criação idempotente.
- Projeto representa a safra/talhão no Dolibarr; tarefa representa o recorte operacional.

## Convenções técnicas

- Classes em `class/`, SQL em `sql/`, traduções em `langs/`, páginas com `main.inc.php`.
- Tabelas próprias usam `entity`, `fk_user_creat`, `fk_user_modif`, `datec` e `tms`.
- Use `price2num()` para quantidades e valores recebidos da interface.
- Ações de escrita exigem permissões do módulo e POST com token Dolibarr.
- Consultas devem filtrar `entity` ou usar as entidades compartilhadas do objeto nativo.
- Não adicionar dependência direta do módulo Safra; integrar por `fk_project` e `fk_task`.
- Não criar tabelas próprias para combustível, peça, filtro, lubrificante ou armazém.

## Qualidade

- Rodar `build/ci/checks.ps1` antes de encerrar mudanças.
- Adicionar teste de regressão para regras de confirmação/conclusão e idempotência.
- Manter diagramas em `diagram_new_module.mmd` e `diagrams/` alinhados ao schema.
- Documentar decisão que altere estoque, custos ou integração com projetos.

