# Pesquisa sobre APIs

Projeto: Ferrorama — monitoramento de trens
Equipe: Arthur B, Bruno, Vinicius | Data: 19/10/2026

## 1. O que é uma API e para que serve

**API** é um "intermediário" que permite que um programa peça dados ou ações a outro programa.

Pense num restaurante: você (cliente) faz o pedido ao garçom (API), o garçom leva à cozinha (sistema) e traz o prato. Você não precisa saber cozinhar nem entrar na cozinha.

**Para que serve:**
- Fazer sistemas diferentes conversarem entre si.
- Usar a mesma lógica em vários lugares (site, aplicativo, sensores).
- Proteger o banco de dados: ninguém mexe nele direto, só pelo que a API permite.

**No Ferrorama:** a tela de monitoramento poderia pedir à API a situação dos trens, e os sensores poderiam enviar suas leituras para ela.

## 2. Como os sistemas se comunicam

Tudo funciona como **pergunta e resposta**:

1. O **cliente** (navegador, app, sensor) envia uma **requisição**.
2. O **servidor** (a API) processa, consulta o banco e devolve uma **resposta**.

```text
Cliente  --- "me dê a lista de trens" --->  API  --->  Banco de dados
Cliente  <--- "aqui está (em JSON)" -------  API  <---  Banco de dados
```

A conversa usa o protocolo **HTTP** (ou HTTPS, que é a versão segura) e os dados normalmente vêm em **JSON**.

## 3. O que é uma API REST

**REST** é um conjunto de boas regras para montar APIs na web. Uma API que segue essas regras é uma **API REST**. Ideias principais:

- Tudo é um **recurso** com um endereço: trens, rotas, sensores.
- As ações são feitas pelos **métodos HTTP** (GET, POST...).
- Cada requisição é **independente**: o servidor não "lembra" da anterior, então a identificação vai em toda chamada.
- Os dados costumam vir em **JSON**.

## 4. Métodos HTTP

O método diz **o que você quer fazer**. Combina com o CRUD:

| Método | O que faz | Exemplo no Ferrorama |
|---|---|---|
| `GET` | **Busca** dados | Listar os trens |
| `POST` | **Cria** algo novo | Cadastrar um trem |
| `PUT` | **Substitui** tudo de um item | Reenviar todos os dados do trem |
| `PATCH` | **Altera só uma parte** | Mudar só o status do trem |
| `DELETE` | **Apaga** | Excluir um trem |

**Exemplos de requisição e resposta:**

**GET** (listar):
```http
GET /api/trens
```
```http
200 OK
[
  { "id": 1, "nome": "Locomotiva Alfa", "status": "operando" },
  { "id": 2, "nome": "Locomotiva Beta", "status": "manutencao" }
]
```

**POST** (criar):
```http
POST /api/trens
{ "nome": "Locomotiva Gama", "status": "operando" }
```
```http
201 Created
{ "id": 3, "nome": "Locomotiva Gama", "status": "operando" }
```

**PUT** (substituir tudo):
```http
PUT /api/trens/3
{ "nome": "Locomotiva Gama II", "status": "manutencao" }
```
```http
200 OK
{ "id": 3, "nome": "Locomotiva Gama II", "status": "manutencao" }
```

**PATCH** (mudar só um campo):
```http
PATCH /api/trens/3
{ "status": "operando" }
```
```http
200 OK
{ "id": 3, "nome": "Locomotiva Gama II", "status": "operando" }
```

**DELETE** (apagar):
```http
DELETE /api/trens/3
```
```http
204 No Content
```

> **PUT x PATCH:** o PUT troca o item inteiro; o PATCH muda só o que foi enviado.

## 5. Requisições e respostas HTTP

**Requisição** (o pedido) tem:
- o **método** e o **endereço** (`GET /api/trens`);
- **cabeçalhos** (informações extras, como o formato dos dados ou a credencial);
- o **corpo** (os dados enviados, usado em POST, PUT e PATCH).

**Resposta** (o retorno) tem:
- um **código de status** (um número que diz se deu certo);
- **cabeçalhos**;
- o **corpo** (normalmente em JSON).

**Códigos de status mais comuns:**

| Código | Significado |
|---|---|
| 200 | Deu certo |
| 201 | Criado com sucesso |
| 204 | Deu certo, sem conteúdo para devolver |
| 400 | Pedido mal feito |
| 401 | Não identificado (sem login ou token) |
| 403 | Identificado, mas sem permissão |
| 404 | Não encontrado |
| 500 | Erro no servidor |

Exemplo de erro:
```http
404 Not Found
{ "erro": "Trem não encontrado" }
```

## 6. O que são endpoints

**Endpoint** é o **endereço (URL) de uma função da API**. O método + o endpoint formam uma ação.

| Método | Endpoint | O que faz |
|---|---|---|
| GET | `/api/trens` | Lista todos os trens |
| GET | `/api/trens/2` | Mostra o trem de id 2 |
| POST | `/api/trens` | Cadastra um trem |
| PATCH | `/api/trens/2` | Altera o trem 2 |
| DELETE | `/api/trens/2` | Exclui o trem 2 |
| GET | `/api/rotas` | Lista as rotas |

Repare que o endereço é o mesmo (`/api/trens`) e o que muda a ação é o método.

## 7. JSON: o formato dos dados

**JSON** é um texto simples e organizado para representar dados. É fácil de ler para pessoas e para programas, e funciona em qualquer linguagem.

Regras básicas:
- Objetos ficam entre `{ }`, listas entre `[ ]`.
- Os dados são pares `"chave": valor`.
- Textos usam **aspas duplas**.

Exemplo:
```json
{
  "id": 2,
  "nome": "Locomotiva Beta",
  "em_manutencao": false,
  "velocidade_kmh": 62.5,
  "sensores": [
    { "tipo": "temperatura", "valor": 71.2 },
    { "tipo": "velocidade", "valor": 62.5 }
  ]
}
```

Em PHP, duas funções resolvem tudo:
```php
$json  = json_encode($array);      
$array = json_decode($json, true); 
```

## 8. API x sistema web tradicional

| | Sistema web tradicional | API |
|---|---|---|
| Quem usa | Pessoas, no navegador | Outros programas |
| O que devolve | Página HTML pronta | Só os dados (JSON) |
| Exemplo | `trens.php` mostra a tabela na tela | `api/trens.php` devolve a lista em JSON |

Em resumo: o sistema web entrega a **página pronta**; a API entrega os **dados**, e quem recebeu decide como mostrar. Os dois podem existir no mesmo projeto.

## 9. Como o PHP consome uma API

"Consumir" é fazer o PHP **chamar** uma API e usar a resposta. A ferramenta mais comum é o **cURL**:

```php
<?php
$ch = curl_init('https://ferrorama.local/api/trens');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // devolver o resultado como texto
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

$resposta = curl_exec($ch);                      // faz a chamada
$status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status === 200) {
    $trens = json_decode($resposta, true);       // JSON -> array
    foreach ($trens as $trem) {
        echo $trem['nome'] . ' - ' . $trem['status'] . '<br>';
    }
} else {
    echo 'Erro ao consultar a API (status ' . $status . ')';
}
```

Passo a passo: prepara a chamada, executa, confere o status, converte o JSON e usa os dados.

## 10. Como uma API disponibiliza dados para outros sistemas

A API em PHP lê o pedido, consulta o banco e **responde em JSON**:

```php
<?php
header('Content-Type: application/json; charset=utf-8'); // avisa que a resposta é JSON

$pdo = new PDO('mysql:host=localhost;dbname=ferrorama;charset=utf8mb4', $usuario, $senha);

$stmt = $pdo->query('SELECT id, nome, status FROM trens');
$trens = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($trens);                                  // devolve a lista
```

Dessa forma, qualquer sistema (site, app, sensor) consegue acessar os trens, **sem acesso direto ao banco**. A API também escolhe o que mostrar: por exemplo, nunca devolve senhas.

## 11. Autenticação, autorização e segurança

- **Autenticação:** provar **quem você é** (login e senha, token).
- **Autorização:** definir **o que você pode fazer** (o admin exclui rotas; o usuário comum só visualiza).

Sem isso, qualquer pessoa poderia ver ou apagar dados.

**Como o cliente se identifica:** normalmente enviando um **token** em cada requisição:
```http
GET /api/rotas
Authorization: Bearer <token>
```

**Cuidados básicos:**
- Usar **HTTPS**, para os dados não trafegarem abertos.
- **Validar** tudo que chega (nunca confiar no cliente).
- Usar **prepared statements** contra SQL Injection.
- Guardar senhas com **hash** (`password_hash`).
- Checar a **permissão no servidor**, não só na tela.
- Não deixar **tokens e senhas** escritos no código do repositório.
- Não mostrar erros técnicos detalhados ao usuário.

## 12. APIs em sistemas empresariais

| Onde | Para quê |
|---|---|
| Pagamentos | A loja envia a cobrança ao gateway (cartão, Pix) e recebe a confirmação |
| Correios/transportadoras | Calcular frete e rastrear encomendas |
| Consulta de CEP | Preencher o endereço automaticamente |
| Mapas | Exibir rotas e calcular distâncias |
| Notas fiscais | Emitir nota fiscal eletrônica |
| ERP | Trocar dados de pedidos e estoque com o e-commerce |
| Login com Google | Entrar sem criar nova senha |
| Ferrorama | Sensores enviam leituras à API, que alimenta o painel de monitoramento |

## 13. Fontes consultadas

1. MDN Web Docs. **HTTP**. <https://developer.mozilla.org/pt-BR/docs/Web/HTTP>
2. MDN Web Docs. **Métodos de requisição HTTP**. <https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Methods>
3. MDN Web Docs. **Códigos de status HTTP**. <https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status>
4. IETF. **RFC 9110 — HTTP Semantics**. <https://www.rfc-editor.org/rfc/rfc9110>
5. FIELDING, Roy. **Tese sobre o estilo REST (cap. 5)**. <https://ics.uci.edu/~fielding/pubs/dissertation/rest_arch_style.htm>
6. JSON.org. **Introdução ao JSON**. <https://www.json.org/json-pt.html>
7. PHP Manual. **cURL**. <https://www.php.net/manual/pt_BR/book.curl.php>
8. PHP Manual. **JSON**. <https://www.php.net/manual/pt_BR/book.json.php>
9. PHP Manual. **PDO**. <https://www.php.net/manual/pt_BR/book.pdo.php>
10. PHP Manual. **password_hash**. <https://www.php.net/manual/pt_BR/function.password-hash.php>
11. OWASP. **API Security Top 10**. <https://owasp.org/API-Security/>