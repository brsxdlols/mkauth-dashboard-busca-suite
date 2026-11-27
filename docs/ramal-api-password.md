# Senha de API do ramal

A ajuda identifica a conta `mkauth`, orienta a usar a mesma senha no cadastro do MK-Auth e em System → Users do MikroTik e informa o IP de origem selecionado pela rota. Quando a consulta de rota nao e permitida no PHP, usa a origem verificada pelo instalador e identifica essa origem como registrada na instalacao. Em redes com NAT, deve ser autorizado o IP traduzido observado no MikroTik. Nenhum comando e aplicado ao MikroTik automaticamente.

O olho exige as permissoes nativas `perm_servidor` e `perm_usuarios`, confirmadas no banco, e a senha do operador autenticado. A senha do operador nao e a senha da API nem a senha SSH. A resposta com a credencial somente existe depois dessa validacao; o HTML inicial recebe um marcador opaco, sem a credencial salva. POST exige CSRF e o limite e de cinco tentativas por operador em quinze minutos.

A instalacao reconhece os controladores nativos, preserva originais com hash e reaplica os wrappers nas atualizacoes. A gravacao continua nativa: o marcador e resolvido no ponto do argumento de senha, depois dos controles nativos. Uma senha digitada e tratada como alteracao explicita; sem edicao, a senha atual e mantida. A confirmacao de gravacao usa o mesmo handler `.hhvm` para invalidar somente os wrappers reconhecidos no OPcache e impedir que uma versao antiga do controlador grave o marcador.

O instalador nao muda senhas de ramais. Estruturas desconhecidas sao preservadas com aviso de incompatibilidade; nenhuma protecao e anunciada como concluida nesse caso. Modelos, PDFs e assinatura digital sao independentes dessa integracao.

Testes: `php scripts/test-ramal-api-password.php addons/shared`, `node scripts/test-ramal-api-password-browser.cjs` e `node scripts/test-ramal-sync-help.cjs`. Usam dados ficticios e nao alteram nenhum ramal de producao.
