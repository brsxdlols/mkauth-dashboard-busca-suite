# Usuários conectados e sessões administrativas

O cabeçalho mostra dois números distintos: **Usuários conectados** conta os usuários
diferentes da lista administrativa nativa; **Minhas sessões** conta somente os
acessos do operador atual. Esses indicadores não são conexões de clientes PPPoE.

O primeiro atalho abre os cartões nativos com `scope=all`, mostrando os acessos de
todos os usuários com sistema, navegador, horário e IP. O segundo usa o mesmo
layout com `scope=mine`, filtrado no servidor
pelo usuário da sessão autenticada, sem aceitar um nome de usuário pela URL.
A ação Deslogar continua nativa em ambas as listas. O contador de usuários é
distinto, embora uma pessoa com vários acessos tenha mais de um cartão na lista.

O contador usa o mesmo diretório, decodificador e chave `MKA_Usuario` da lista
nativa. Atualiza a cada 30 segundos enquanto a página estiver visível e ao voltar
para a janela. Os números refletem as sessões mantidas pelo MK-Auth; a expiração
continua sob controle nativo. Quando a leitura não está disponível, não é exibido
um número fictício como 01.

A atualização SSE nativa de sessões do operador atual não sobrescreve esse total.
O popup usa URL absoluta e versão de atualização para evitar uma página antiga
em cache. O campo Usuário é produzido pelo controlador nativo com o complemento
instalado pela suíte. Nenhuma sessão é removida ou encerrada para contar acessos.

O botão Deslogar pede confirmação com o nome do usuário antes de seguir para a
ação nativa. Cancelar mantém a página e não envia a requisição de encerramento.
