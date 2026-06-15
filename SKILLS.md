# SKILLS.md

Playbook local de habilidades para desenvolver o módulo Frota Agrícola.

## Skill: Dolibarr Module Engineering

- Ler `core/modules/modFrota.class.php`, a classe de domínio e o SQL relacionado antes de editar.
- Preferir `CommonObject`, `Form`, `FormProduct`, permissões, traduções e helpers nativos.
- Para objeto novo, criar classe, tabela, índices, menu, permissão, lista, cadastro e traduções.
- Preservar compatibilidade com Dolibarr 23 e PHP 8.2.

## Skill: Stock-Safe Fleet Operations

- Centralizar baixas em `class/frotastockservice.class.php`.
- Validar produto físico e gerenciado em estoque.
- Validar armazém aberto e saldo suficiente mesmo se estoque negativo estiver habilitado globalmente.
- Usar `MouvementStock::setOrigin()` e `MouvementStock::livraison()`.
- Persistir `fk_stock_movement` no registro de origem.
- Confirmar/concluir dentro de transação e impedir repetição.

## Skill: Fleet Costing

- Abastecimento calcula `total_amount`, custo unitário e consumo desde a leitura anterior.
- Manutenção soma mão de obra e linhas consumidas.
- Uso calcula horas por `horimetro_end - horimetro_start`.
- Custo horário estimado usa custos confirmados/concluídos divididos pelas horas registradas.
- Não apresentar estimativa como custo contábil definitivo.

## Skill: Agro Seed

- Seeds ficam em `class/frotaseeder.class.php`.
- Usar refs fixas, buscar antes de criar e poder executar novamente sem duplicar.
- Categorizar todos os produtos padrão.
- Não criar saldo inicial automaticamente.

## Skill: QA Frota

- Rodar lint PHP em todos os arquivos.
- Executar `php tests/run.php`.
- Conferir que todas as tabelas próprias possuem campos de auditoria.
- Conferir que nenhuma baixa ocorre em rascunho.
- Conferir que confirmação/conclusão repetida não gera outro movimento.

