# Frota Agrícola para Dolibarr

MVP de gestão de frota agrícola integrado aos produtos, categorias, armazéns,
estoque, projetos e tarefas nativos do Dolibarr 23.

## Escopo do MVP

- veículos e implementos;
- tipos agrícolas de veículo e operação;
- abastecimento com baixa de estoque;
- manutenção preventiva/corretiva com agenda, periodicidade e consumo de produtos;
- apontamento operacional por projeto/tarefa;
- dashboard básico e seeds idempotentes.

## Instalação

1. Habilite Produtos, Estoque, Categorias e Projetos.
2. Habilite o módulo **Frota Agrícola**.
3. A ativação cria as tabelas e executa o seed inicial.
4. Dê entrada de saldo nos armazéns antes de confirmar consumos.

O módulo não cria saldo inicial e não duplica seeds existentes.

Nos abastecimentos, a referência é gerada automaticamente no formato
`ABS-AAAAMMDD-000123`. Veículo, produto físico controlado em estoque e armazém
aberto são selecionados pelos campos relacionais pesquisáveis do Dolibarr.

Nas manutenções, a referência também é automática no formato
`MAN-AAAAMMDD-000123`, usando a data prevista ou, quando ausente, a data de
início/criação.

## Planejamento de manutenção

- cada manutenção pertence exclusivamente a um veículo ou a um implemento;
- a agenda prioriza registros vencidos por data prevista, horímetro ou odômetro;
- manutenções preventivas podem repetir por dias, horas e/ou quilômetros;
- manutenções de implementos, como plantadeiras, podem ser periódicas por dias;
  critérios de horímetro e odômetro permanecem exclusivos dos veículos;
- ao concluir uma manutenção periódica, o sistema gera uma única próxima
  ocorrência vinculada e copia os produtos planejados;
- a ocorrência futura não movimenta estoque; a baixa continua ocorrendo somente
  quando cada manutenção é concluída;
- limites futuros de horímetro/odômetro são separados das leituras reais usadas
  para atualizar o veículo.

## Validação local

```powershell
.\build\ci\checks.ps1
```

Com uma instância local configurada, o teste transacional de integração pode ser
executado com `php tests/integration.php`. Ele cria dados temporários, valida os
fluxos de estoque e encerra com rollback.
