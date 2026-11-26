# PGCORTE DUAL STACK 1.3.1 na instalação e atualização

O fluxo comum `install.sh`, também utilizado pelo bootstrap `install-from-github.sh` nas atualizações, executa o patch depois da configuração do banco, do FreeRADIUS e dos hooks nativos. A etapa é obrigatória e não é ignorada em reinstalações. Não existe marcador de “já instalado” que dispense a reaplicação após uma atualização nativa.

`scripts/install-pgcorte-dual.py` exige root e baixa o ZIP e seu arquivo `.sha256` destes endereços:

- https://raw.githubusercontent.com/brsxdlols/mkauth-ipv6-addon/main/patches/pgcorte-dual/mkauth-pgcorte-dual-v1.3.1.zip
- https://raw.githubusercontent.com/brsxdlols/mkauth-ipv6-addon/main/patches/pgcorte-dual/mkauth-pgcorte-dual-v1.3.1.zip.sha256

O SHA256 é comparado antes de abrir ou extrair o ZIP. Download inválido, checksum divergente, pacote incompleto e caminhos ZIP impróprios interrompem a etapa sem executar um pacote antigo como substituto. Os arquivos são extraídos mantendo `/opt/mkauth-patches/mkauth-pgcorte-dual`. Arquivos extras preexistentes nessa pasta são preservados.

Depois de publicar os arquivos verificados, o integrador executa exatamente:

```sh
sh /opt/mkauth-patches/mkauth-pgcorte-dual/install.sh
cd /opt/mkauth-patches/mkauth-pgcorte-dual
python3 patch.py check
python3 native_pgcorte.py check
python3 native_pgcorte.py check --admin
```

As três verificações são executadas mesmo se o instalador do patch retornar erro, permitindo registrar o estado de cada componente. Somente os **quatro códigos 0** produzem sucesso. O código 4 do instalador/componente RADIUS é identificado como ativação pendente e também impede sucesso. Outras falhas continuam sendo falhas, mesmo quando um componente diferente informa sucesso.

Qualquer erro retorna código diferente de zero para `install.sh`, que é interrompido antes de anunciar a conclusão geral. O bootstrap e o monitor de progresso mantêm esse código. A mensagem “Patch PGCORTE não concluído” aponta para o log completo em `<backup-da-suíte>/pgcorte-dual/`; o JSON ao lado registra os códigos e o SHA256. Logs e relatórios são privados (`0600` dentro de pasta `0700`). Não há senhas embutidas nem execução automática de `.rsc` ou conexão com os MikroTik.

O pacote oficial administra suas alterações, backups, configuração RADIUS e verificações. O integrador não edita fontes protegidas nem substitui suas regras de rollback. A ativação do RADIUS é a que o próprio `install.sh` oficial realiza. A integração visual — PGCORTE DUAL STACK e popup PPPoE do ramal aberto — pode levar **até cinco minutos** para aparecer devido ao cache do PHP; as três verificações não são substituídas por uma espera estimada.

Testes isolados, sem aplicar o patch real:

```sh
python3 scripts/test-pgcorte-integration.py
python3 scripts/test-install-progress.py
bash -n install.sh
```

Os testes usam ZIPs e comandos de fixture, cobrem códigos de falha em cada uma das quatro chamadas, ativação pendente, checksum incorreto, download indisponível, extração inválida, repetição e propagação do resultado. No Linux, também executam processos filhos reais de fixture, sem banco, RADIUS ou roteadores. O ZIP oficial pode ser inspecionado e ter sua integridade validada sem executar seu instalador.
