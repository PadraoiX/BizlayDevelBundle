# SANSIS - DEVELBUNDLE

O DevelBundle é um bundle criado especificamente para trabalhar com o banco de dados Oracle. Ele complementa
alguns itens que faltam à reversa da doctrine, ajusta as entidades para o formato de trabalho da arquitetura
do SanSIS - Sistemas Inteligentes, e cria ainda estrutura para Cruds no formato requerido para uso da Bizlay.

## Configuração:

Adicione o Bundle na lista de Bundles varridos pelo JMS/DI no arquivo config_dev.yml

```yml
jms_di_extra:
  locations:
    all_bundles: false
    bundles: [CrudBundle, DevelBundle, CoreBundle, MainBundle]
```

## Comandos disponíveis:

Você poderá listar os schemas disponíveis no banco de dados, e as tabelas para cada schema,
além, claro, de realizar a reversa do banco de dados de um determinado schema dentro de um
bundle específico.

### sansis:reverse:listschemas

Lista os schemas com permissão de acesso

### sansis:reverse:listschematables

Lista as tabelas de um schema ao qual se tenha permissão de acesso.

### sansis:reverse:entities

Realiza a reversa do banco de dados, de acordo com a permissão de acesso.

### sansis:generate:crud

Gera a estrutura de pastas e arquivos para a criação de Cruds.