if(localStorage.getItem('token') == null){
    alert("Você precisa estar logado para acessar essa página");
    window.location.href = "./assets/html/sigin.html";
}

let userLogado = JSON.parse(localStorage.getItem("userlogado"));

let logado = document.querySelector("#logado");
logado.innerHTML = 'olá ${userLogado.nome}';


function sair(){
    localStorage.removeItem("token");
    localStorage.removeItem("userLogado");
    window.location.href = ".assets /html/sigin.html"
}