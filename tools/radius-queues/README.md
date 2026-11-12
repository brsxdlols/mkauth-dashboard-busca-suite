# Sincronização de filas RADIUS no MK-AUTH

Descobre os NAS cadastrados no MK-AUTH, lê as sessões PPPoE e Simple Queues dos MikroTik por SSH, compara com o plano vigente no banco e corrige diferenças por CoA. Sessões locais são ignoradas.

## Instalação

Executar no **Bash do servidor MK-AUTH**, como root:

```sh
curl -fsSL -H 'Cache-Control: no-cache' https://raw.githubusercontent.com/brsxdlols/mkauth-dashboard-busca-suite/main/tools/radius-queues/install-queues.sh | bash
```

O instalador baixa uma versão fixada no commit atual, verifica sintaxe e executa testes com dados fictícios. Instala em `/opt/mk-auth/tools/radius-queues` e cria o comando `mkauth-queues`. Preserva versões anteriores. Não altera clientes, não executa correções e não cria cron. É independente do instalador da dashboard.

Dependências: Bash, curl, Python 3.7+, PHP CLI 7.1+ com mysqli, OpenSSH cliente, sshpass, radclient e ferramentas padrão GNU. Não instala ou atualiza pacotes automaticamente; indica dependências ausentes. Não exige biblioteca Python externa nem mysql CLI na nova versão.

## Usar

Auditoria automática dos NAS com SSH ativo no cadastro:

```sh
mkauth-queues
```

Depois de revisar o relatório, corrigir os casos encontrados:

```sh
mkauth-queues --apply
```

Restringir um primeiro teste a um MikroTik e um cliente:

```sh
mkauth-queues --nas=192.0.2.1 --user=cliente@exemplo
mkauth-queues --nas=192.0.2.1 --user=cliente@exemplo --apply
```

Os endereços e nomes acima são fictícios. Não é necessário informar a lista de sessões, os IDs ou as filas: o programa consulta o MikroTik e cruza os IDs e IPs com o accounting aberto do mesmo NAS no MK-AUTH.

O programa executa uma varredura por chamada. **Instalar não cria monitoramento permanente.** Para refletir uma futura edição do plano, executar novamente a auditoria/aplicação. Não há agendamento automático embutido.

## Credenciais e chaves SSH

Utiliza os endereços, portas SSH, senha SSH e segredo RADIUS do cadastro `nas`; o usuário SSH padrão é `mkauth`, ajustável com `--ssh-user`. Lê a conexão SQL local em `/etc/freeradius/3.0/mods-enabled/sql`. Não imprime senhas nem segredos.

A chave SSH deve ser conhecida em `/root/.ssh/known_hosts`, ou fornecida por fingerprint validado. Um host desconhecido é apresentado com seu fingerprint e não recebe autenticação até a validação. Depois de comparar a chave por uma fonte confiável, executar, por exemplo:

```sh
mkauth-queues --nas=192.0.2.1 --host-key='192.0.2.1=SHA256:FINGERPRINT_VALIDADO'
```

Manter a mesma opção na aplicação ou registrar a chave no known_hosts do servidor após validá-la. Não copiar cegamente uma chave recebida pela rede. Se o cadastro usar senha cifrada ou o usuário SSH for diferente por NAS, fornecer um arquivo JSON protegido em 0600:

```json
{
  "192.0.2.1": {"username": "usuario_ssh", "password": "senha_definida_localmente"}
}
```

```sh
chmod 600 /root/queues-ssh.json
mkauth-queues --credentials=/root/queues-ssh.json
```

Não guardar credenciais no repositório. O helper `queues-db.php` é exclusivo de CLI, deve ficar fora da raiz web e não deve ser executado manualmente para exportar o snapshot, pois ele transporta credenciais internamente ao processo Python.

## O que corrige

- RADIUS sem fila dinâmica.
- Fila ainda com velocidade máxima, burst, garantia ou prioridade anteriores.
- Cliente associado a um grupo de plano antigo, mesmo se esse grupo ainda existir.
- Vínculos de plano duplicados, desde que sejam grupos simples reconhecidos.
- Atributo Mikrotik-Rate-Limit ausente ou desatualizado em um plano simples, usando os campos de velocidade vigentes de `sis_plano`.
- Adicional com nome legado estritamente reconhecido, somente se houver uma única correspondência comercial entre o número de MB e um plano ADICIONAIS existente.

Exemplo: ao trocar um cliente de um plano de 100 MB para um de 200 MB, a ferramenta identifica o plano atual, reconcilia o vínculo e envia a velocidade por CoA se a fila ainda estiver com o limite antigo. Ao editar as velocidades de um plano, também identifica a diferença entre `sis_plano`, o atributo RADIUS e a fila da sessão.

**A correção do atributo do plano é compartilhada:** altera `radgroupreply` para o plano vigente e vale para futuras autorizações de todos os clientes desse plano. Não altera perfis globais do MikroTik. As sessões abertas são ajustadas individualmente somente quando elegíveis e verificadas na varredura. Um NAS inacessível continua pendente; o programa não afirma que ele foi corrigido.

## O que preserva ou deixa para revisão

- Todas as sessões locais, sem flag RADIUS.
- Clientes inativos, bloqueados, com turbo, redução ou plano noturno; adicionais cujo titular tenha estado especial.
- Pools individuais reconhecidos como corte/bloqueio/redução.
- Grupos com checks ou respostas personalizadas, atributos individuais de autorização/velocidade e vínculos de grupos desconhecidos.
- Filas estáticas, inválidas, desabilitadas, alvos inesperados e múltiplas filas/sessões ambíguas.
- Planos incompletos, valores não numéricos ou bursts/garantias inconsistentes.

Nesses casos registra o motivo e não tenta substituir a política. O objetivo não é implementar ou certificar a política financeira de bloqueio.

## Aplicação e verificação

Antes de escrever, relê a sessão e o cadastro, trava e revalida os registros do banco e cria backup direcionado. Para a fila, exige que o NAS já aceite CoA, lê a porta em `/radius incoming`, confere IP e ID no accounting e envia somente Mikrotik-Rate-Limit.

Não habilita CoA/firewall automaticamente, não cria filas estáticas, não reinicia serviços nem envia Disconnect-Request. Depois do CoA-ACK, relê a fila e verifica os seis componentes do limite e o mesmo ID de sessão. ACK sem fila correta é erro.

Um lote não é uma transação distribuída: o banco pode ter sido corrigido antes de um erro de CoA, e clientes anteriores podem já estar ajustados. Nesse caso interrompe a aplicação naquele NAS, registra o erro e o backup, e exige nova auditoria. Nunca usa desconexão como alternativa. Uma segunda execução após a convergência não propõe novas alterações.

Uma única instância usa um lock local. Mudanças feitas por rotinas externas do MK-AUTH ainda podem ocorrer: são detectadas pelos guards e pela verificação posterior, mas o programa não remove a origem de uma rotina que recrie inconsistências.

## Backups e reversão

Backups com permissões restritas ficam em `/opt/mk-auth/backups/codex-queues`. Cada alteração do banco registra estado anterior e posterior em `database.json`; cada ajuste de sessão registra `before.json`, respostas e o resultado do banco. Esses arquivos contêm dados dos clientes e não devem ser publicados.

Reverter somente a alteração direcionada do banco:

```sh
mkauth-queues --rollback-db=/caminho/database.json --apply
```

Recusa reversão se os dados mudaram depois da aplicação. Não restaura tabelas inteiras. A reversão do banco não muda automaticamente uma sessão aberta.

Restaurar os limites anteriores da mesma sessão:

```sh
mkauth-queues --rollback-session=/caminho/before.json --apply
```

Exige o mesmo ID e IP, que a fila ainda corresponda à correção e que o cliente continue elegível. Restaura os componentes de limite capturados; se não havia fila, envia 0/0 e verifica ausência de limite. Isso pode manter um objeto de fila com limite zero conforme a versão do RouterOS. Se a sessão mudou ou o rollback não for confirmado, interrompe sem desconectar.

Antes de aplicar em outro servidor, validar o suporte transacional das tabelas e a restauração em ambiente isolado. `radusergroup` pode usar TokuDB em instalações antigas.

Para voltar a uma versão anterior das ferramentas, o instalador mantém `releases/` e registra o destino anterior em `previous-release.txt`. Restaurar o link `current` para o diretório revisado não reverte alterações já realizadas nos clientes.

## Compatibilidade e validação

Validado em MK-AUTH com PHP 7.1/Python 3.7, MikroTik RouterOS 6.49.18 e banco com TokuDB/InnoDB. Requer os campos padrão usados de `nas`, `sis_cliente`, `sis_adicional`, `sis_plano`, `radusergroup`, `radreply`, `radgroupreply`, `radgroupcheck` e `radacct`. Configurações SQL com socket/porta personalizados ou outro formato podem exigir adaptação. NAS por IPv4; credenciais SSH devem ser utilizáveis ou sobrescritas no arquivo privado.

Testes automatizados cobrem descoberta de múltiplos NAS, sessões locais, estados especiais, troca de plano/velocidade, fila ausente, unidades equivalentes, políticas personalizadas, accounting divergente e reversão de sessão. A gravação de grupos/atributos, recusa de guard desatualizado, rollback do banco e instalação repetida foram exercitados em base e diretórios isolados. O tráfego CoA nesses testes isolados foi simulado; a criação real por CoA foi confirmada anteriormente em 46 sessões, com filas e IDs preservados. O novo fluxo de descoberta foi exercitado em leitura no servidor real, sem aplicar limpeza em massa como teste.

Os arquivos antigos `reconciliar-radius.php` e `atualizar-filas-coa.py` permanecem para compatibilidade com o fluxo manual anterior. A instalação atual usa `sincronizar-queues.py` e `queues-db.php`.

[FreeRADIUS SQL](https://wiki.freeradius.org/modules/Rlm_sql) · [MikroTik RADIUS/CoA](https://help.mikrotik.com/docs/spaces/ROS/pages/328097/RADIUS).
