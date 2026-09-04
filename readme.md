# Ferrorama - Monitoramento de Trens


## Descrição

O **Ferrorama** é um sistema web para monitoramento de trens, permitindo que os usuários cadastrem, monitorem e gerenciem trens de forma remota e em tempo real.

## Requisito funcional
  

## Requisitos Funcionais
RF 1
O sistema deve possuir uma tela de Login com campos de e-mail e senha.

RF 2
O sistema deve autenticar o usuário comparando e-mail e senha.

RF 3
Após a autenticação, o sistema deve criar uma sessão e redirecionar o usuário para a tela principal.

RF 4
Em caso de informações de login incorretas, o sistema deve retornar à tela de login e informar que as credenciais são inválidas.

RF 5
A tela principal do administrador deve exibir o nome do administrador autenticado.

RF 6
A tela principal deve conter um botão de logout.

RF 7
A tela principal deve conter um botão para acessar o cadastro de sensores e trens.

RF 8
A tela principal deve listar todos os sensores cadastrados, exibindo ID, localização, tipo de dado monitorado, botão excluir e botão visualizar detalhes.

RF 9
O sistema deve permitir o cadastro de sensores, contendo nome do sensor, localização e tipo de dado monitorado.

RF 10
O sistema deve permitir vincular o sensor cadastrado a um trem específico.

RF 11
O sistema deve permitir a listagem de sensores da ferrovia com opções de exclusão e visualização de detalhes.

RF 12
O sistema deve permitir a exclusão de sensores após confirmação do usuário.

RF 13
O sistema não deve permitir a exclusão de um sensor que tenha registros de dados associados, exibindo a mensagem: “Não é possível excluir sensores com dados registrados”.

RF 14
O sistema deve possuir uma tela de monitoramento em tempo real mostrando a velocidade do trem, localização em mapa e status operacional.

RF 15
O sistema deve apresentar relatórios analíticos com gráficos interativos para visualização de desempenho e identificação de falhas.

RF 16
O sistema deve permitir a geração de relatórios sobre a operação ferroviária, incluindo gráficos e análises de tendências.

RF 17
O sistema deve permitir a visualização de relatórios gerados anteriormente, com filtros por período e tipo de falha.

RF 18
O sistema deve permitir a visualização dos detalhes individuais de cada sensor cadastrado.

RF 19
O sistema deve permitir o cadastro e a identificação dos trens monitorados pela ferrovia.

RF 20
O sistema deve permitir pesquisar sensores pelo nome, localização, trem ou tipo de dado monitorado.

RF 21
O sistema deve permitir consultar o histórico dos dados coletados por cada sensor.

RF 22
O sistema deve registrar automaticamente a data e a hora de cada dado recebido dos sensores.

RF 23
O sistema deve informar ao administrador quando um sensor apresentar uma falha ou deixar de enviar dados.

RF 24
O sistema deve permitir visualizar as falhas detectadas durante determinado período.

RF 25
O sistema deve permitir filtrar os dados de monitoramento por trem, sensor e período.

## Requisitos Não Funcionais
RNF 1
O sistema deve manter o usuário autenticado por meio de sessão enquanto estiver logado.

RNF 2
A interface deve ser responsiva e adaptável a diferentes tamanhos de tela.

RNF 3
O sistema deve apresentar mensagens claras de sucesso e erro ao usuário.

RNF 4
Os gráficos nos relatórios devem ser interativos e de fácil compreensão.

RNF 5
O sistema deve respeitar boas práticas de desenvolvimento, com organização de código, comentários e indentação.

RNF 6
O sistema deve carregar listagens em até 2 segundos para até 1000 registros.

RNF 7
O sistema deve ser intuitivo e fácil de navegar para o administrador.

RNF 8
Deve haver tratamento de erros para situações como falha na conexão com o banco de dados ou dados inválidos.

RNF 9
As senhas dos usuários devem ser armazenadas de forma segura, utilizando mecanismos adequados de criptografia ou hash.

RNF 10
O sistema deve garantir que informações restritas sejam acessíveis somente a usuários autenticados e autorizados.

RNF 11
O sistema deve manter a integridade dos dados armazenados no banco de dados.

RNF 12
O sistema deve apresentar os dados de monitoramento de forma legível e organizada.

RNF 13
O sistema deve possuir compatibilidade com os principais navegadores atuais.

RNF 14
O sistema deve realizar validações nos campos dos formulários antes de efetuar o cadastro.

RNF 15
O sistema deve possuir arquitetura que permita futuras manutenções e inclusão de novas funcionalidades.

## Regras de Negócio
RN 1
Apenas usuários cadastrados como administradores podem acessar o sistema. O acesso é realizado exclusivamente por e-mail e senha.

RN 2
O sistema permite apenas uma sessão ativa por usuário.

RN 3
Todo sensor cadastrado deve estar obrigatoriamente vinculado a um trem específico da ferrovia.

RN 4
Não é permitido cadastrar dois sensores com o mesmo nome e mesma localização para o mesmo trem.

RN 5
Um sensor não pode ser excluído se possuir qualquer registro de dados coletados.

RN 6
Toda exclusão de sensor deve ser precedida de uma tela ou modal de confirmação explícita por parte do administrador.

RN 7
O status do trem deve ser calculado automaticamente com base nos dados dos sensores.

RN 8
A tela de monitoramento deve atualizar as informações de velocidade, localização e status em intervalos regulares.

RN 9
Os relatórios devem permitir filtragem por data, trem específico, tipo de sensor e tipo de falha.

RN 10
Todos os dados coletados pelos sensores, como velocidade, temperatura e falhas, devem ser armazenados com data e hora para permitir análises históricas.

RN 11
Em todas as telas internas do sistema, deve ser exibido o nome completo do administrador que está autenticado.

RN 12
Ao clicar em “Sair”, o sistema deve destruir completamente a sessão e redirecionar o usuário para a tela de login.

RN 13
Os campos Nome do Sensor, Localização, Tipo de Dado Monitorado e Trem são obrigatórios.

RN 14
Cada sensor deve possuir um identificador único dentro do sistema.

RN 15
Cada trem deve possuir um identificador único dentro do sistema.

RN 16
Um sensor só pode estar vinculado a um trem por vez.

RN 17
Os dados recebidos de um sensor devem ser associados ao sensor responsável pela coleta.

RN 18
Quando um sensor deixar de enviar dados dentro do período esperado, o sistema deve considerá-lo como indisponível.

RN 19
Uma falha deve ser registrada quando os dados recebidos ultrapassarem os limites definidos para o monitoramento.

RN 20
Os registros históricos dos sensores não devem ser alterados durante a geração dos relatórios.

### Autenticação
- Cadastro de nova conta
- Login com conta existente
- Armazenamento de informações do usuário

### Após o Login
O usuário é redirecionado para o dashboard, onde pode escolher entre:
- **Cadastro de itens** (trens)
- **Monitoramento de itens**
- **Manutenção de itens**


### Monitoramento em Tempo Real
- Visualização remota do trem
- Posição atual no mapa
- Rota prevista
- Horários precisos de partida e chegada

### Sistema de Ajuda
- Relatar problemas manualmente
- Seleção de problemas pré-cadastrados

## Tecnologias Utilizadas
- HTML5, CSS3 e JavaScript
- Bootstrap 5
- LocalStorage (para demonstração)
- Font Awesome

## Próximos Passos
- Implementar backend (para substituir o LocalStorage)
- Conexão com banco de dados
- Melhorar o mapa interativo
- Sistema de notificações em tempo real



**Projeto em desenvolvimento**
