 const sensores = [];

  document.querySelector("form").addEventListener("submit", function (e) {
    e.preventDefault(); // Impede o redirecionamento imediato

    const inputs = document.querySelectorAll("input");
    const nome       = inputs[0].value.trim();
    const localizacao = inputs[1].value.trim();
    const data       = inputs[2].value;

    // Validação
    if (!nome || !localizacao || !data) {
      alert("⚠️ Preencha todos os campos antes de cadastrar.");
      return;
    }

    // Salva o sensor
    const sensor = { nome, localizacao, data };
    sensores.push(sensor);

    // Salva no localStorage para usar em outras páginas
    localStorage.setItem("sensores", JSON.stringify(sensores));

    alert(`✅ Sensor "${nome}" cadastrado com sucesso!`);

    // Redireciona após confirmar
    window.location.href = "sensor.html";
  });

  // Destaca o botão do menu ao clicar
  document.querySelector(".item-btn").addEventListener("click", function () {
    document.querySelectorAll(".item-btn").forEach(b => b.style.background = "white");
    this.style.background = "#d1fae5";
  });
  const sensores = JSON.parse(localStorage.getItem("sensores")) || [];
console.log(sensores); // array com todos os cadastros