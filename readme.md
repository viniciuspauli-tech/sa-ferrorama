# Ferrorama — Sistema de Monitoramento de Trens

## 1. Sobre o projeto

O **Ferrorama** é um sistema web desenvolvido como projeto acadêmico do curso Técnico em Desenvolvimento de Sistemas do SENAI. Seu objetivo é permitir o gerenciamento e o monitoramento de trens e sensores ferroviários por meio de uma interface intuitiva e organizada.

O sistema possui funcionalidades de autenticação de usuários, gerenciamento de registros e visualização de informações relacionadas à operação ferroviária. A proposta também contempla o monitoramento de dados, a consulta de históricos e a geração de relatórios para auxiliar na identificação de falhas e no acompanhamento operacional.

## 2. Objetivos

- Desenvolver uma aplicação web para gerenciamento de informações ferroviárias.
- Permitir o cadastro, a consulta, a edição e a exclusão de registros.
- Controlar o acesso ao sistema por meio de autenticação.
- Organizar informações sobre trens, sensores, rotas e usuários.
- Facilitar a consulta de dados relacionados à operação ferroviária.
- Aplicar boas práticas de desenvolvimento web, segurança e organização de código.

## 3. Funcionalidades do sistema

O projeto contempla as seguintes áreas:

- **Autenticação:** acesso ao sistema por meio de e-mail e senha.
- **Painel principal:** acesso às funcionalidades disponíveis após o login.
- **Gerenciamento de trens:** cadastro, consulta e gerenciamento das informações dos trens.
- **Gerenciamento de sensores:** cadastro e consulta de sensores e de suas informações.
- **Gerenciamento de rotas:** organização das informações relacionadas às rotas ferroviárias.
- **Gerenciamento de usuários:** administração dos registros de usuários.
- **Controle de acesso:** restrição de funcionalidades conforme o perfil do usuário.
- **Monitoramento e relatórios:** funcionalidades previstas para acompanhar dados operacionais e consultar históricos.

> Observação: as funcionalidades descritas nos requisitos abaixo representam o escopo do projeto. Recursos que dependem de integração com sensores físicos, atualização em tempo real ou geração avançada de relatórios devem ser considerados pendentes até que sua implementação seja validada.

## 4. Requisitos funcionais (RF)

Os requisitos funcionais descrevem as funcionalidades que o sistema deve oferecer.

| Código | Descrição |
|---|---|
| RF01 | O sistema deve apresentar uma tela de login com campos para e-mail e senha. |
| RF02 | O sistema deve autenticar o usuário por meio do e-mail e da senha cadastrados. |
| RF03 | Após a autenticação, o sistema deve iniciar uma sessão e direcionar o usuário à tela principal. |
| RF04 | O sistema deve apresentar uma mensagem de erro quando as credenciais forem inválidas. |
| RF05 | O painel administrativo deve identificar o usuário autenticado. |
| RF06 | O sistema deve permitir que o usuário encerre sua sessão por meio da opção de logout. |
| RF07 | O sistema deve disponibilizar acesso às funcionalidades de cadastro de trens e sensores. |
| RF08 | O sistema deve permitir visualizar os sensores cadastrados, incluindo suas informações de identificação, localização e tipo de dado monitorado. |
| RF09 | O sistema deve permitir cadastrar sensores com nome, localização e tipo de dado. |
| RF10 | O sistema deve permitir associar um sensor a um trem específico. |
| RF11 | O sistema deve permitir consultar os sensores cadastrados e acessar suas informações detalhadas. |
| RF12 | O sistema deve solicitar confirmação antes de excluir um sensor. |
| RF13 | O sistema deve impedir a exclusão de sensores que possuam dados registrados, apresentando a mensagem: “Não é possível excluir sensores com dados registrados”. |
| RF14 | O sistema deve permitir monitorar informações dos trens, como velocidade, localização e situação operacional. |
| RF15 | O sistema deve disponibilizar relatórios analíticos com gráficos interativos. |
| RF16 | O sistema deve apresentar relatórios relacionados à operação ferroviária, permitindo analisar gráficos e tendências. |
| RF17 | O sistema deve permitir consultar relatórios históricos e aplicar filtros por período e falhas. |
| RF18 | O sistema deve permitir consultar os detalhes individuais de cada sensor. |
| RF19 | O sistema deve permitir cadastrar trens e registrar suas informações de identificação. |
| RF20 | O sistema deve permitir pesquisar sensores por nome, localização, trem associado ou tipo de dado. |
| RF21 | O sistema deve permitir consultar o histórico de dados coletados pelos sensores. |
| RF22 | O sistema deve registrar a data e a hora de cada dado recebido de um sensor. |
| RF23 | O sistema deve alertar o administrador quando um sensor apresentar falha ou deixar de enviar dados dentro do período esperado. |
| RF24 | O sistema deve permitir consultar falhas registradas em determinado período. |
| RF25 | O sistema deve permitir filtrar as informações de monitoramento por trem, sensor ou período. |

## 5. Requisitos não funcionais (RNF)

Os requisitos não funcionais estabelecem critérios de qualidade, segurança, desempenho e usabilidade.

| Código | Descrição |
|---|---|
| RNF01 | O sistema deve manter a sessão do usuário durante o período autenticado, respeitando as regras de segurança. |
| RNF02 | A interface deve ser responsiva e adaptar-se a diferentes tamanhos de tela. |
| RNF03 | O sistema deve apresentar mensagens claras de sucesso, erro e validação. |
| RNF04 | Os gráficos utilizados nos relatórios devem permitir a visualização clara e interativa dos dados. |
| RNF05 | O código deve ser organizado, indentado e documentado quando necessário. |
| RNF06 | As listagens devem carregar em até dois segundos em condições de teste definidas, considerando até 1.000 registros. |
| RNF07 | A navegação administrativa deve ser intuitiva e facilitar o acesso às funcionalidades. |
| RNF08 | O sistema deve tratar erros de execução e de acesso ao banco de dados adequadamente. |
| RNF09 | As senhas devem ser armazenadas por meio de funções seguras de hash, nunca em texto puro. |
| RNF10 | O acesso às páginas internas deve exigir autenticação quando aplicável. |
| RNF11 | O banco de dados deve preservar a integridade e a consistência das informações. |
| RNF12 | Os dados apresentados na interface devem ser legíveis e organizados. |
| RNF13 | A aplicação deve funcionar nos principais navegadores modernos compatíveis com as tecnologias utilizadas. |
| RNF14 | Os formulários devem validar os dados antes de realizar operações no sistema. |
| RNF15 | O projeto deve possuir uma estrutura que facilite a manutenção e a evolução do código. |
| RNF16 | As consultas ao banco de dados devem utilizar instruções preparadas e parâmetros quando aplicável. |
| RNF17 | O sistema deve respeitar as permissões associadas ao perfil de cada usuário. |
| RNF18 | Usuários comuns não devem conseguir atribuir a si mesmos privilégios administrativos por meio dos formulários. |
| RNF19 | As senhas não devem ser exibidas em consultas, listagens ou formulários de edição. |
| RNF20 | As mensagens de erro apresentadas ao usuário não devem expor informações internas sensíveis da aplicação ou do banco de dados. |

## 6. Regras de negócio (RN)

As regras de negócio definem as condições que devem ser respeitadas durante a utilização do sistema.

| Código | Descrição |
|---|---|
| RN01 | Somente usuários autenticados e autorizados podem acessar as funcionalidades internas do sistema. |
| RN02 | O sistema deve controlar as sessões de usuários autenticados conforme as regras de acesso definidas. |
| RN03 | Cada sensor deve estar associado a um trem específico, caso essa associação faça parte do modelo de dados adotado. |
| RN04 | Não deve haver duplicidade de sensores com o mesmo nome e localização para um mesmo trem, quando houver associação entre eles. |
| RN05 | Um sensor que possua dados registrados não deve ser excluído. |
| RN06 | Toda exclusão de sensor deve exigir confirmação explícita do usuário. |
| RN07 | A situação operacional de um trem deve ser determinada conforme critérios de monitoramento definidos pelo sistema. |
| RN08 | As informações de monitoramento devem ser atualizadas em intervalos regulares, quando houver integração de dados em tempo real. |
| RN09 | Os relatórios devem permitir filtros por data, trem, sensor e falhas, conforme as funcionalidades implementadas. |
| RN10 | Cada dado coletado de um sensor deve ser armazenado com sua respectiva data e hora. |
| RN11 | As telas internas devem identificar o usuário autenticado quando essa informação for pertinente à interface. |
| RN12 | Ao realizar logout, o sistema deve encerrar a sessão e redirecionar o usuário para a tela de login. |
| RN13 | O cadastro de sensores deve exigir os campos obrigatórios definidos no formulário e no modelo de dados. |
| RN14 | Cada sensor deve possuir um identificador único. |
| RN15 | Cada trem deve possuir um identificador único. |
| RN16 | Um sensor deve estar associado a apenas um trem por vez, caso essa relação seja adotada no banco de dados. |
| RN17 | Cada leitura deve estar vinculada ao sensor responsável por sua coleta. |
| RN18 | Um sensor que deixe de enviar dados pelo período definido deve poder ser identificado como indisponível. |
| RN19 | Uma falha deve ser registrada quando os dados ultrapassarem os limites operacionais estabelecidos. |
| RN20 | A geração de relatórios não deve alterar os registros históricos utilizados na consulta. |

## 7. Tecnologias utilizadas

As tecnologias utilizadas no desenvolvimento do projeto incluem:

- **PHP:** implementação da lógica de aplicação no servidor.
- **MySQL:** armazenamento e gerenciamento dos dados.
- **HTML5:** estrutura das páginas.
- **CSS3:** estilização e organização visual da interface.
- **JavaScript:** implementação de interações no lado do cliente, quando aplicável.
- **Bootstrap 5:** recursos para construção de interfaces responsivas, quando utilizados.
- **XAMPP:** ambiente local para execução do servidor web e do banco de dados.

## 8. Estrutura do projeto

A estrutura pode ser consultada diretamente nas pastas do projeto. De forma geral, os arquivos estão organizados por responsabilidade, separando a conexão e a autenticação, as páginas da aplicação, os estilos e os recursos do banco de dados.

Principais áreas do projeto:

- `infra/` — arquivos de infraestrutura, como conexão com o banco e controle de autenticação.
- `public/` — páginas da aplicação, como login, cadastro e telas do sistema.
- `style/` — arquivos de estilos CSS.
- `data base/` — arquivos relacionados à estrutura do banco de dados, quando presentes.
- `doc/` — documentação complementar do projeto.
- `index.php` — arquivo de entrada, conforme a configuração atual da aplicação.
- `readme.md` — documentação principal do projeto.

Os nomes e caminhos acima devem corresponder aos diretórios existentes na versão atual do repositório.

## 9. Banco de dados

O projeto utiliza o MySQL para armazenar as informações necessárias ao funcionamento da aplicação.

Entre as tabelas previstas ou utilizadas no sistema estão:

- **usuarios:** armazena os dados de acesso e os perfis dos usuários.
- **trens:** armazena os dados de identificação e situação dos trens.
- **sensores:** armazena as informações dos sensores cadastrados.
- **rotas:** armazena as informações das rotas ferroviárias.
- **dados_sensor:** armazena os registros coletados pelos sensores, conforme a estrutura implementada.

A estrutura real das tabelas, seus campos, tipos de dados e relacionamentos deve ser consultada no script SQL utilizado pelo projeto.

## 10. Instalação e execução

Para executar o projeto em ambiente local:

1. Instale e inicie o XAMPP.
2. Inicie os serviços Apache e MySQL.
3. Coloque a pasta do projeto no diretório de documentos do servidor web, normalmente `htdocs`.
4. Acesse o phpMyAdmin pelo navegador.
5. Crie o banco de dados com o nome esperado pelo arquivo de conexão.
6. Importe o script SQL do projeto, caso esteja disponível, ou crie as tabelas conforme a estrutura utilizada pela aplicação.
7. Confira as credenciais e o nome do banco no arquivo `infra/connect.php`.
8. Abra o projeto pelo navegador utilizando o endereço local correspondente à pasta instalada.

Exemplo de endereço local, caso a pasta do projeto se chame `Ferrorama`:

`http://localhost/Ferrorama/`

**Importante:** o nome do banco, as credenciais e os caminhos de acesso devem corresponder à configuração real do ambiente. Antes de importar um script SQL, confira se ele corresponde à versão atual do projeto.

## 11. Usuário administrador

O acesso administrativo deve ser realizado por uma conta cadastrada com o perfil apropriado no banco de dados.

A criação do primeiro administrador deve respeitar o formato de senha utilizado pela aplicação. Se o login utiliza `password_verify()` do PHP, a senha armazenada precisa ser gerada por uma função compatível, como `password_hash()`.

Não armazene senhas em texto puro nem publique credenciais reais no repositório.

## 12. Segurança

As medidas de segurança a serem aplicadas e verificadas incluem:

- Autenticação antes do acesso às páginas protegidas.
- Verificação de permissões para operações administrativas.
- Armazenamento de senhas com hash seguro.
- Utilização de consultas preparadas para reduzir riscos de SQL Injection.
- Validação dos dados recebidos por formulários.
- Proteção das operações de exclusão e alteração.
- Encerramento adequado da sessão durante o logout.
- Tratamento de erros sem exposição de credenciais ou detalhes internos.

A presença dessas medidas deve ser confirmada no código antes de considerar cada uma delas concluída.

## 13. Limitações e funcionalidades futuras

As seguintes funcionalidades fazem parte do escopo planejado e devem ser validadas de acordo com o estado atual do projeto:

- Integração com sensores físicos ou fontes reais de telemetria.
- Atualização automática dos dados de monitoramento em tempo real.
- Exibição da localização dos trens em mapa.
- Geração de gráficos e relatórios analíticos completos.
- Alertas automáticos para falhas ou ausência de dados.
- Consulta de históricos com filtros avançados.
- Aplicação de regras automáticas para determinar a situação operacional dos trens.

Esses itens não devem ser considerados concluídos sem que exista implementação funcional e testada.

## 14. Estado do projeto

O Ferrorama está em desenvolvimento acadêmico. Seu objetivo é construir uma aplicação web para gerenciar informações ferroviárias, com autenticação, organização de dados e funcionalidades administrativas.

Os requisitos funcionais, não funcionais e as regras de negócio documentados neste arquivo representam o escopo definido para o projeto. O estado de cada requisito deve ser acompanhado conforme sua implementação e validação.

Sugestão de classificação durante os testes:

- **Implementado:** a funcionalidade está disponível e foi testada.
- **Parcialmente implementado:** existe uma implementação incompleta ou sem todos os comportamentos previstos.
- **Pendente:** a funcionalidade ainda precisa ser desenvolvida.

## 15. Considerações finais

O projeto Ferrorama busca aplicar os conhecimentos adquiridos no curso Técnico em Desenvolvimento de Sistemas, envolvendo desenvolvimento web, banco de dados, autenticação, organização de código e construção de interfaces.

A documentação acompanha os objetivos e requisitos do sistema e pode ser atualizada conforme novas funcionalidades forem implementadas, testadas e integradas ao projeto.
