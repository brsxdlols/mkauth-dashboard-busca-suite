# Cabeçalho e menu administrativo mobile

O instalador integra admin_mobile.js nos carregadores administrativos existentes (addon.js e scripts/mk-auth.js), em bloco gerenciado e idempotente; preserva os demais bytes e menus personalizados. O módulo carrega CSS compartilhado e ícones nativos quando ausentes.

O nome do provedor quebra dentro do cabeçalho em telas estreitas, sem ocultar o overflow da página. O botão do menu usa SVG independente da fonte de ícones e controles acessíveis. Abrir/fechar e submenus funcionam nas páginas nativas e nos addons. Os handlers mobile evitam alternância duplicada com os handlers nativos. As cores do navbar da dashboard passam a vir do tema nativo, sem a paleta alternativa anterior.

Revisão feita com HTML autenticado das telas Dashboard, Configurações, Dados do Provedor, Tema, Ramais, Planos nativos, Busca e addon de Planos, nas larguras 390, 768 e 1360. Conferidos largura do documento/cabeçalho, nome longo, cor do menu e abrir/fechar/submenus. A revisão não equivale a testar todas as telas e operações do MK-AUTH.
