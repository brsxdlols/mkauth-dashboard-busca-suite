# Seleção de região com Espaço e Esc — integração Leaflet

Implementado e homologação inicial no Visual Net, addon `rel_cli_offline`.

## Comportamento para reproduzir em outra integração

1. Mostrar uma instrução permanente sobre o mapa: “Segure o espaço para arrastar seleção para a região dentro do mapa”.
2. Ao pressionar Espaço, desabilitar temporariamente `map.dragging`, guardar o estado anterior e trocar o cursor para mira. Ignorar Espaço em inputs, textarea, select, botões e contenteditable.
3. Capturar `pointerdown`, `pointermove` e `pointerup` na superfície do mapa, em fase de captura, somente durante a seleção. Usar `setPointerCapture` para continuar recebendo eventos fora da superfície. Converter posições por `map.mouseEventToLatLng`.
4. Desenhar um `L.rectangle` não interativo, com preenchimento translúcido, atualizado por `setBounds(L.latLngBounds(inicio, fim))`. Só confirmar arrastos maiores que 5 pixels.
5. Ao soltar o mouse, guardar os limites geográficos. Filtrar a lista por `bounds.contains(marker.getLatLng())` antes da busca e da ordenação. Remover do mapa apenas os marcadores externos, sem apagar os registros nem perder suas referências.
6. Os clientes sem coordenadas ficam fora da região selecionada; ao limpar a região, eles reaparecem na lista.
7. Mostrar “Pressione Esc para finalizar a seleção e mostrar todos os clientes”. Manter também botão “Limpar área · Esc”. Esc limpa o retângulo, restaura os marcadores e a lista; preserva o texto de busca e a ordenação escolhida. Dentro de um modal, interceptar Esc em captura para limpar a seleção antes de fechar o modal.
8. Ao soltar Espaço, restaurar a navegação do mapa ao estado anterior. Em `blur` e `pointercancel`, cancelar o arrasto incompleto e liberar o modo de seleção. Uma seleção já confirmada continua até Esc ou Limpar área.
9. Clicar numa casa seleciona o cartão correspondente, destaca em azul e rola apenas a lista até ele. Se a busca ocultar esse cartão, limpar a busca para revelá-lo.

## Arquivos de referência

- `addons/rel_cli_offline/offline-map.js`: variáveis `areaBounds` e `areaRectangle`; funções `areaMessage`, `applyArea`, `drawArea`, `finishArea`, `releaseSpace`, `clearArea`; handlers de teclado e pointer; filtros em `renderClients`.
- `addons/rel_cli_offline/mapa.php`: instrução com `role=status`/`aria-live`, botão de limpeza e referências aos recursos com versão atualizada.
- `addons/rel_cli_offline/offline-map.css`: destaque sobre o mapa, estado ativo azul e cursor de seleção.
- `scripts/test-offline-map.js`: testes de arrasto, filtragem, restauração com Esc, proteção de campos de texto e funcionalidades anteriores.

A seleção funciona inteiramente no navegador sobre os clientes já carregados: não modifica dados ou sessões e não adiciona consultas por movimento do mouse. A biblioteca Leaflet, ícones e arquivos da tela ficam no próprio addon. O instalador copia a pasta completa sem editar a página de mapa nativa do MK-AUTH.

## Atualização de dados

Atualmente o contador de duração avança a cada 30 segundos no navegador. Isso não é uma nova consulta ao RADIUS: o status online/offline e a seleção de clientes são obtidos no carregamento da tela. Não apresentar o contador como monitoramento de conectividade em tempo real.
