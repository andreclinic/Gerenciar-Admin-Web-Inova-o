console.log("wpcontent.js carregado");
// 4) JS de interação (ex.: toast simples)
// assets/js/wpcontent.js
jQuery(function ($) {
  console.log("wpcontent.js pronto");

  // Clique em QUALQUER área do aviso
  $(document).on("click", ".notice.is-dismissible", function (e) {
    console.log("wpcontent: clique na .notice (alvo real:", e.target, ")");
    alert("wpcontent: notice clicada");
  });

  // Clique especificamente no botão "X" (dismiss)
  $(document).on(
    "click",
    ".notice.is-dismissible .notice-dismiss",
    function (e) {
      console.log(
        "wpcontent: clique no botão de fechar (pode parar propagação no core)"
      );
      // não faz alert aqui para não atrapalhar o comportamento nativo
    }
  );

  // Clique no parágrafo interno, só para garantir que estamos capturando filhos
  $(document).on("click", ".notice.is-dismissible p", function () {
    console.log("wpcontent: clique no <p> dentro do notice");
  });
});
