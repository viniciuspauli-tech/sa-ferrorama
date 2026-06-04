// Destaque visual no card ao clicar (feedback antes de navegar)
  document.querySelectorAll(".card-btn").forEach(function(card) {
    card.addEventListener("click", function(e) {
      e.preventDefault(); // segura um momento

      // Remove destaque anterior
      document.querySelectorAll(".card-btn").forEach(function(c) {
        c.style.background = "white";
        c.style.borderColor = "black";
      });

      // Aplica destaque no clicado
      this.style.background = "#0f172a";
      this.style.color = "white";
      this.style.borderColor = "#0f172a";

      // Redireciona após 300ms (tempo do efeito visual)
      var destino = this.getAttribute("href");
      setTimeout(function() {
        window.location.href = destino;
      }, 300);
    });
  });

  // Destaca o card correspondente se vier de volta pela navegação do browser
  window.addEventListener("pageshow", function() {
    document.querySelectorAll(".card-btn").forEach(function(c) {
      c.style.background = "white";
      c.style.color = "black";
      c.style.borderColor = "black";
    });
  });