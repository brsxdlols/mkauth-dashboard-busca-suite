# Instalacao do reconciliador ausente

A instalacao da suite passou a conferir os artefatos do reconciliador. Antes,
o upgrade preservava uma instalacao ausente sem cria-la e retornava sucesso.

O instalador agora chama `scripts/ensure-radius-reconcile.py`:

- Worker ausente: instala os modelos PHP do pacote, apos verificar sintaxe.
- Worker conhecido existente: aplica o upgrade restrito, preservando seu cron.
- Cron ausente: cria o agendamento padrao a cada dois minutos.
- Guarda ausente: restaura o helper correspondente ao worker conhecido.
- Rotina desconhecida, cron desativado/customizado sem chamada reconhecida ou
  cron legado concorrente: preserva os arquivos e encerra com erro explicito.
- Verifica sintaxe do worker, guarda e endpoint de status e a chamada PHP do cron.

Nao executa o worker nem escreve no accounting durante a instalacao. O cron
executa a reconciliacao normal, com trava, identificacao da sessao e guarda
offline. Nao altera o MikroTik, nao desconecta clientes e nao muda fuso horario.
Um cron instalado nao garante acesso a API: falhas de acesso continuam aparecendo
no status do reconciliador.

## Backup e reversao

O backup da instalacao, subdiretorio `offline-confirmation`, guarda
`artifacts-before.json`, os arquivos anteriores, seus modos e proprietarios.
O upgrade tambem salva seus originais em `upgrade/`.
Para reverter, suspenda somente o cron desse reconciliador e aguarde o worker
terminar; restaure os arquivos que existiam e os metadados do manifesto e remova
somente os artefatos novos indicados como `existed: false`. Restaure o cron
anterior por ultimo. Nao restaure tabelas de accounting: esta etapa nao as altera.

## Validacao isolada

`python3 scripts/test-ensure-radius-reconcile.py`

Os testes usam diretorios temporarios e PHP lint: primeira instalacao,
repeticao, cron customizado preservado, cron/guarda ausentes, cron desativado,
worker desconhecido, cron legado e falha de sintaxe. Nao consultam banco nem API.
Validados em PHP 7.3 e PHP 8.0.
