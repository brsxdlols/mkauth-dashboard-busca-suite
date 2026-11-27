# Addon de contratos na instalacao completa

A dashboard chama `scripts/install-contract-addon.sh`, que baixa e executa o instalador oficial de `brsxdlols/mkauth-addon-contratos`. Instalacoes e atualizacoes usam o mesmo commit estavel indicado em `installers/stable-ref` naquele repositorio. Uma falha nessa dependencia interrompe a conclusao da instalacao e mostra o log privado no backup.

O addon atualiza codigo e menu, preservando configuracao, modelos editados, PDFs, anexos, assinaturas e historico. Os modelos iniciais sao inseridos apenas com proveniencia segura de primeira instalacao; um modelo anteriormente registrado e depois removido nao e recriado. Em bases antigas sem identificadores reconheciveis, a ausencia e preservada em vez de criar uma possivel duplicata.

A previa da Busca prefere o renderizador do addon oficial instalado. O fallback local continua atendendo instalacoes antigas; nao e usado para reescrever documentos assinados.

Para instalar somente o addon:

```sh
curl -fsSL https://raw.githubusercontent.com/brsxdlols/mkauth-addon-contratos/main/installers/github-install.sh | sh
```

Nao execute o utilitario de modelos separadamente. A conexao local e reaproveitada e nenhuma credencial deve ser colocada no comando.

Publicacao: validar o addon, publicar o commit de codigo e depois apontar `installers/stable-ref` para esse SHA. A dashboard e o comando avulso passarao a instalar a mesma revisao sem duplicar o pacote.
