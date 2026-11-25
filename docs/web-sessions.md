# Usuários conectados e sessões administrativas

O cabeçalho mostra dois números distintos: usuários diferentes e sessões web da
lista administrativa nativa. Um usuário com três sessões conta como um usuário e
três sessões. Esses indicadores não são conexões de clientes PPPoE.

O contador usa o mesmo diretório, decodificador e chave `MKA_Usuario` da lista
nativa. Atualiza a cada 30 segundos enquanto a página estiver visível e ao voltar
para a janela. Os números refletem as sessões mantidas pelo MK-Auth; a expiração
continua sob controle nativo. Quando a leitura não está disponível, não é exibido
um número fictício como 01.

A atualização SSE nativa de sessões do operador atual não sobrescreve esse total.
O popup usa URL absoluta e versão de atualização para evitar uma página antiga
em cache. O campo Usuário é produzido pelo controlador nativo com o complemento
instalado pela suíte. Nenhuma sessão é removida ou encerrada para contar acessos.
