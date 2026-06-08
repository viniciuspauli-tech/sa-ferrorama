1. O que é o XAMPP?
O XAMPP é uma distribuição do Apache totalmente gratuita e fácil de instalar, que tem o banco de dados MariaDB, o PHP e o Perl, é um pacote de software que transforma um computador comum em um servidor web local.
O nome é um acrônimo onde:
X: Multiplataforma (roda em Windows, Linux e macOS)
A: Apache (Servidor Web)
M: MariaDB / MySQL (Banco de Dados)
P: PHP (Linguagem de Programação)
P: Perl (Linguagem de Programação)

2. Principais Componentes e suas Finalidades
Para que o sistema do Ferrorama funcione, cada engrenagem do XAMPP tem um papel específico.
Apache - É o responsável por receber as requisições do navegador (ex: quando o usuário digita http://localhost/ferrorama) e entregar a página web de volta, ele "serve" os arquivos HTML, CSS e imagens para o usuário.
MySQL / MariaDB - É o sistema de gerenciamento de banco de dados relacional, no projeto Ferrorama, é aqui que ficarão salvas as tabelas de Cadastro de Sensores, logs de ativação, horários e dados capturados na pista.
PHP - É a linguagem de programação que roda no backend, o PHP vai ser responsável por conectar o HTML no Banco de Dados, processar o formulário de cadastro de sensores, validar os dados e salvar tudo no MariaDB.
phpMyAdmin - É uma interface gráfica web para gerenciar o banco de dados MySQL/MariaDB pelo navegador, em vez de usar linhas de comando complexas para criar as tabelas de sensores, a gente usa o phpMyAdmin de forma visual e intuitiva.

3. Instalação e Configuração Básica
Download: Baixar o instalador oficial no site da Apache Friends, escolhendo a versão compatível com o sistema operacional e com a versão do PHP desejada.
Instalação: No Windows, a pasta padrão de instalação será C:\xampp.
Painel de Controle: Para rodar a aplicação PHP e o banco de dados, é só abrir o painel e clicar em "Start" do lado do Apache e do MySQL.
A Pasta Ouro (htdocs): Todos os arquivos do projeto do Ferrorama (arquivos .php, .html, .css) devem ser salvos dentro da pasta C:\xampp\htdocs\ferrorama (ou o nome que escolherem para o projeto).
Acesso no Navegador: Para testar, é só abrir o navegador e digitar:
Para o projeto: http://localhost/ferrorama/
Para o banco de dados: http://localhost/phpmyadmin/

4. Importância do Ambiente para Desenvolvimento Local
Segurança e Privacidade: Os alunos podem errar, criar bugs e testar scripts de cadastro de sensores sem o risco de expor dados na internet ou derrubar um site que está no ar.
Velocidade: Como o servidor está rodando na própria memória do computador, o carregamento e os testes das páginas PHP são instantâneos, sem depender da velocidade da internet.
Custo Zero: Permite criar aplicações robustas e completas sem a necessidade de contratar serviços pagos de hospedagem web durante a fase de aprendizado.
Autonomia: Simula com fidelidade o comportamento de um servidor de produção real, preparando o aluno para o mercado de trabalho.