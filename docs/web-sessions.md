# Usuários conectados e sessões administrativas

O cabeçalho mostra dois números distintos: **Usuários conectados** conta os usuários
diferentes da lista administrativa nativa; **Minhas sessões** conta somente os
acessos do operador atual. Esses indicadores não são conexões de clientes PPPoE.

O primeiro atalho abre `web_users.php`, com uma linha por usuário e sua quantidade
de sessões. O segundo abre a lista nativa com `scope=mine`, filtrada no servidor
pelo usuário da sessão autenticada, sem aceitar um nome de usuário pela URL.
A ação Deslogar continua nativa para outras sessões desse mesmo usuário.

O contador usa o mesmo diretório, decodificador e chave `MKA_Usuario` da lista
nativa. Atualiza a cada 30 segundos enquanto a página estiver visível e ao voltar
para a janela. Os números refletem as sessões mantidas pelo MK-Auth; a expiração
continua sob controle nativo. Quando a leitura não está disponível, não é exibido
um número fictício como 01.

A atualização SSE nativa de sessões do operador atual não sobrescreve esse total.
O popup usa URL absoluta e versão de atualização para evitar uma página antiga
em cache. O campo Usuário é produzido pelo controlador nativo com o complemento
instalado pela suíte. Nenhuma sessão é removida ou encerrada para contar acessos.
