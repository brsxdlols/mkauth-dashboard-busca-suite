# Reconciliação de planos RADIUS e filas PPPoE

Ferramentas administrativas opcionais para executar no servidor MK-AUTH. Não são executadas pelo instalador da dashboard e não criam tarefa recorrente.

## O que é detectado e como o ajuste chega ao MikroTik

A referência do plano é o banco do MK-AUTH. `reconciliar-radius.php` procura vínculos cujo primeiro grupo RADIUS não possui um plano com Mikrotik-Rate-Limit válido e propõe o plano vigente do cadastro. Corrige duplicatas/vínculos legados dentro do escopo revisado. Para adicionais, aceita um plano existente do cadastro ou uma única correspondência comercial entre um nome legado, como 70MB_FIBRA, e um plano ADICIONAIS de 70 MB. Não escolhe por preço nem aproxima uma velocidade arbitrária.

Depois, `atualizar-filas-coa.py` consulta o accounting aberto e os atributos RADIUS no MK-AUTH e envia **somente Mikrotik-Rate-Limit por CoA** ao NAS informado. A sessão não precisa reconectar. A ferramenta exige confirmação CoA-ACK e a leitura final da fila no MikroTik.

**A descoberta ainda não é integralmente automática:** é necessário fornecer o NAS de destino e um inventário recente das sessões RADIUS afetadas. As ferramentas não entram nos roteadores para procurar filas ausentes, não descobrem todos os NAS e não certificam que uma sessão é RADIUS apenas porque aparece no accounting. O campo `radius: true` deve vir da flag R observada no MikroTik. Sessões locais devem ser excluídas do escopo.

## Requisitos e limites

- PHP CLI 7.1+ com mysqli; Python 3.7+; mysql CLI; radclient; permissão administrativa local.
- Configuração SQL em `/etc/freeradius/3.0/mods-enabled/sql`, com os campos server/login/password/radius_db entre aspas. Esquema padrão das tabelas utilizadas; verificar compatibilidade antes de aplicar em outra versão.
- A configuração usa conexão MySQL local padrão. Instalações com socket ou porta personalizados precisam de adaptação.
- MikroTik com CoA já habilitado e acessível; endereço NAS e segredo corretos na tabela nas. As ferramentas não habilitam portas nem mudam firewall.
- Validar suporte transacional do mecanismo das tabelas. A reversão do banco não reverte automaticamente as sessões abertas.
- Estados de bloqueio, turbo e redução não são reconciliados automaticamente. Grupos cujo primeiro vínculo já é válido são ignorados; a ferramenta não é uma auditoria universal de todas as velocidades.
- Não corrige a origem de uma rotina externa que volte a gerar vínculos inconsistentes. Não há agendamento automático.

## Fluxo em outro MK-AUTH

1. Ler PPP ativos e Simple Queues no MikroTik. Separar RADIUS de local. Identificar RADIUS sem fila e conferir seus planos e estados no MK-AUTH. Fazer backup dos grupos e cadastros envolvidos.
2. Auditar, sem escrever:

```sh
php reconciliar-radius.php
```

3. Salvar `escopo.json` com os logins RADIUS revisados, por exemplo `["cliente@exemplo"]`. Conferir e aplicar explicitamente:

```sh
php reconciliar-radius.php --users=escopo.json
php reconciliar-radius.php --users=escopo.json --apply
```

Antes da escrita, o script trava e revalida os registros, grava backup direcionado e confere o resultado antes do COMMIT. Uma repetição após a correção não altera registros já válidos. Casos inseguros no escopo impedem aplicação. Não reutilizar listas de outro provedor.

4. Ler novamente as sessões no MikroTik e criar `sessoes.json` somente com as RADIUS afetadas:

```json
{
  "cliente@exemplo": {
    "session": "01234567",
    "ip": "192.0.2.10",
    "radius": true
  }
}
```

O ID deve ser o observado no MikroTik, sem 0x. O IP e ID precisam coincidir com uma única sessão de accounting aberta no NAS escolhido. Endereços acima são exemplos de documentação.

5. Para cada destino, primeiro conferir e aplicar em um cliente, depois validar a fila e seguir com o restante:

```sh
python3 atualizar-filas-coa.py --sessions=sessoes.json --nas=192.0.2.1 --port=3799
python3 atualizar-filas-coa.py --sessions=sessoes.json --nas=192.0.2.1 --port=3799 --apply
```

Trocar o IP e a porta pelos valores reais já habilitados. A ferramenta para se a sessão divergir, houver ambiguidade ou faltar CoA-ACK. Não usa Disconnect-Request como alternativa. Um lote CoA não é transação: se parar, os clientes anteriores podem já ter sido ajustados; consultar o arquivo result.jsonl e ler as filas antes de continuar.

6. Conferir no MikroTik fila dinâmica, limite equivalente ao plano e mesmo ID de sessão. O total de filas pode ser menor que o PPP se houver sessões locais sem limite.

## Backup e reversão

Credenciais são lidas da configuração local, nunca incorporadas ao código. O segredo CoA é enviado ao radclient por arquivo temporário protegido. Não publicar backups, escopos, inventários reais nem saídas de auditoria: contêm dados dos clientes.

O reconciliador grava backup direcionado em `/opt/mk-auth/backups/codex-queues/`, com permissões restritas. Para reverter o banco:

```sh
php reconciliar-radius.php --rollback=/caminho/targeted-rollback.json
```

Essa operação altera o banco. Recusa execução se os vínculos ou planos adicionais diferirem do estado registrado após a aplicação; não restaura tabelas inteiras. Validar restauração em ambiente isolado antes da aplicação em novo servidor.

O fluxo CoA registra as sessões e os resultados em diretório próprio de backup. Destina-se aos clientes que estavam **sem limite/fila**. Uma reversão por CoA com limite 0/0 deve ser preparada exclusivamente para o mesmo ID de sessão e conferida; não foi ensaiada em todas as versões e pode não remover o objeto de fila. Não usar esse backup para restaurar um limite anterior diferente, que não foi capturado pelo script. Nunca reverter por IP reaproveitado nem desconectar como alternativa.

## Validação realizada

Reconciliação aplicada a 46 clientes em servidor com RouterOS 6.49.18; 46 CoA-ACK, filas dinâmicas criadas e IDs preservados. Reexecução do reconciliador sem mudanças. PHP verificado no servidor e ferramenta CoA reutilizável conferida em modo de leitura. Compatibilidade com outros esquemas e versões precisa ser verificada no destino.

[FreeRADIUS SQL: grupos e Fall-Through](https://wiki.freeradius.org/modules/Rlm_sql) · [MikroTik: CoA e Mikrotik-Rate-Limit](https://help.mikrotik.com/docs/spaces/ROS/pages/328097/RADIUS).
