<!-- Script para controlar o menu em dispositivos móveis -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
    $(document).ready(function() {
        // Botão de alternância para mostrar/ocultar o menu
        $("#btnToggleMenu").click(function() {
            $("#menu").slideToggle();
        });

        // Fechar o menu se um item for clicado (opcional)
        $("#menu li a").click(function() {
            $("#menu").slideUp();
        });
    });

    // Ocultar o menu ao redimensionar a janela (opcional)
    $(window).resize(function() {
        var larguraJanela = $(window).width();
        if (larguraJanela > 768) {
            $("#menu").removeAttr("style");
        }
    });
</script>
