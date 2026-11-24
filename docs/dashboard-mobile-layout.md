# Largura da dashboard no celular

As dashboards Nova e Multiempresas incluem `mobile_layout.php` no próprio HTML. As regras limitam a largura do conteúdo ao espaço disponível, eliminam margens assimétricas da grade nativa no celular e mantêm as tabelas largas dentro de áreas de rolagem próprias.

O arquivo `dashboard_density.css` usa uma versão calculada pelo SHA-256 do conteúdo. Alterar esse CSS muda automaticamente o endereço solicitado pelo navegador. Reinstalar o pacote não depende de incrementar manualmente uma versão para substituir uma cópia antiga em cache. As regras críticas de largura são renderizadas com a página mesmo quando o navegador ainda possui um CSS antigo.

O instalador já copia todo o diretório `addons/dashboard`, incluindo o novo arquivo. Nenhum ajuste manual no servidor é necessário após atualizar o pacote. O layout Legado não é alterado.

## Verificação

`node scripts/test-dashboard-mobile-layout.cjs` usa fixtures autenticadas, privadas e sem scripts, fornecidas por `MKA_NOVA_FIXTURE` e `MKA_MULTI_FIXTURE`. Os estilos usados pela página devem estar embutidos no HTML. Os testes executam somente o JavaScript local da organização da dashboard, sem operações em clientes ou no financeiro.

São verificados os dois layouts, CSS atual/antigo em cache e dez larguras de 320 a 1360 px. Os testes conferem largura da página, margens equilibradas no celular e rolagem independente das tabelas. Também verificam as referências ao arquivo crítico e à versão baseada no conteúdo. `CHROME_BIN`, `MKA_DASHBOARD_SOURCE` e `MKA_MOBILE_SCREENSHOTS` permitem escolher o navegador, uma árvore de fontes em preparação e o diretório das capturas. Não inclua fixtures com dados de clientes no repositório.
