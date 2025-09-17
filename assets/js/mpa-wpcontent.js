// 4) JS de interação (ex.: toast simples)
// assets/js/wpcontent.js
jQuery(function ($) {

  // Clique em QUALQUER área do aviso
  $(document).on("click", ".notice.is-dismissible", function (e) {
    // Notice clicked - functionality can be added here if needed
  });

  // Clique especificamente no botão "X" (dismiss)
  $(document).on(
    "click",
    ".notice.is-dismissible .notice-dismiss",
    function (e) {
      // Notice dismiss button clicked - preserving native behavior
    }
  );

  // Clique no parágrafo interno, só para garantir que estamos capturando filhos
  $(document).on("click", ".notice.is-dismissible p", function () {
    // Paragraph within notice clicked - functionality can be added here if needed
  });
});
