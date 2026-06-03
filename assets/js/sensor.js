// Dados fixos de cada trem
  const dados = {
    "Trem 01": { sensor: "Sensor de Velocidade",  local: "Trilho Norte",    status: "Ativo",   posicao: 20 },
    "Trem 02": { sensor: "Sensor de Temperatura", local: "Estação Central", status: "Ativo",   posicao: 50 },
    "Trem 03": { sensor: "Sensor de Presença",    local: "Ponte Sul",       status: "Inativo", posicao: 75 },
  };

  // Adiciona transição suave ao trem e sensor
  document.getElementById("mapaTrem").style.transition   = "left 0.6s ease";
  document.getElementById("mapaSensor").style.transition = "left 0.6s ease";

  function mostrarSensor(nomeTrem, sensor, local, status) {
    const d = dados[nomeTrem];
    if (!d) return;

    // Preenche painel
    document.getElementById("tremNome").innerText    = nomeTrem;
    document.getElementById("sensorNome").innerText  = d.sensor;
    document.getElementById("sensorLocal").innerText = d.local;

    // Status com cor
    const elStatus = document.getElementById("sensorStatus");
    elStatus.innerText        = d.status;
    elStatus.style.color      = d.status === "Ativo" ? "green" : "red";
    elStatus.style.fontWeight = "bold";

    // Rótulo do trem no mapa
    document.getElementById("mapaTrem").innerText = nomeTrem;

    // Posições seguras: trem 5–80%, sensor sempre à frente, máximo 92%
    const posTrem   = Math.max(5,  Math.min(80, d.posicao));
    const posSensor = Math.min(92, posTrem + 12);

    document.getElementById("mapaTrem").style.left   = posTrem   + "%";
    document.getElementById("mapaSensor").style.left = posSensor + "%";

    // Cor do sensor reflete status
    document.getElementById("mapaSensor").style.backgroundColor =
      d.status === "Ativo" ? "green" : "red";
  }