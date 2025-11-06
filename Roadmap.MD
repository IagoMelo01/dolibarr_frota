# TODO – Módulo Frota (Roadmap 30 dias)

## Semana 1 – Estrutura e Cadastro de Veículos
**Objetivo:** Criar a base para registrar a frota e visualizar dados básicos.

- [X] Criar tabelas no banco:
  - [X] Veículos
  - [X] Tipos/Categorias
  - [X] Leituras de odômetro/horímetro
- [X] Tela de cadastro e edição de veículos:
  - [X] Marca
  - [X] Modelo
  - [X] Ano
  - [X] Tipo
  - [X] Odômetro/Horímetro inicial
- [X] Implementar permissões do módulo:
  - [X] Visualizar
  - [X] Criar/Editar
  - [X] Excluir
- [X] Tela de listagem de veículos com filtros por categoria:
  - [X] Trator
  - [X] Colheitadeira
  - [X] Caminhão
  - [X] Outros
- [X] Estrutura para anexos/documentos:
  - [X] CRLV
  - [X] Notas
  - [X] Fotos do veículo

---

## Semana 2 – Registro de Uso e Manutenções
**Objetivo:** Começar a controlar o uso e registrar serviços feitos.

- [ ] Tela de lançamento de uso por veículo (horas ou km rodados)
- [ ] Histórico de leituras de odômetro/horímetro por data
- [X] Cadastro e edição de manutenções:
  - [X] Tipo
  - [X] Descrição
  - [X] Data
  - [X] Custo
  - [X] Fornecedor/Responsável
- [X] Vincular manutenção ao veículo e armazenar odômetro/horímetro na ocasião
- [X] Visualização do histórico de manutenções por veículo

---

## Semana 3 – Custos e Preventivas
**Objetivo:** Adicionar inteligência financeira e preventiva.

- [X] Registro de custos de manutenção (valores + fornecedor)
- [ ] Cálculo automático de custo por km/hora (com base em uso e custos acumulados)
- [ ] Definir intervalos de manutenção preventiva (por km/hora ou tempo)
- [ ] Tela de manutenções previstas vs realizadas, com status:
  - [ ] Em dia
  - [ ] Atrasada
- [ ] Relatório simples de custos por veículo

---

## Semana 4 – Relatórios e Consolidação
**Objetivo:** Unir tudo em visão gerencial e preparar espaço para futuras expansões.

- [ ] Relatório consolidado por veículo:
  - [ ] Uso total
  - [ ] Custos
  - [ ] Manutenções realizadas
  - [ ] Próximas preventivas
- [ ] Dashboard simples da frota:
  - [ ] Veículos ativos
  - [ ] Veículos em manutenção
  - [ ] Custos totais do mês
- [ ] Exportação de relatórios para Excel/PDF
- [ ] Alertas básicos:
  - [ ] “Trator X precisa de revisão – 10h atrasado”
- [ ] Documentação inicial de uso do módulo

---

## Visão Geral do Roadmap
- **Semana 1:** Base (veículos e categorias)
- **Semana 2:** Operações principais (uso + manutenções)
- **Semana 3:** Custos e preventivas
- **Semana 4:** Consolidação (relatórios + alertas)
