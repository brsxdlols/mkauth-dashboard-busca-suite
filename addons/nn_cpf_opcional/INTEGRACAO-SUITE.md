# Integracao na suite Dashboard + Busca

Origem: brsxdlols/mkauth-migracao-sergio-transporte, patches/cpf-opcional, lido em 26/10/2026.
README.md e INTEGRACAO.md nesta pasta foram mantidos como referencia do pacote original.

## Operacao

A interface fica em Opcoes > Recursos > SETUP, abaixo de Validar CPF/CNPJ.
Chave nn_permitir_sem_cpf em sis_opcao: somente sim permite documento vazio.
O instalador inicializa com nao somente quando a chave estiver ausente e preserva escolhas existentes.
Documentos preenchidos continuam sujeitos a configuracao nativa validar_cpf; os demais campos obrigatorios permanecem exigidos.

## Instalador

install.sh chama scripts/install-cpf-policy.py depois de instalar os helpers compartilhados.
O instalador verifica os cinco pontos de entrada, o despacho depois da autenticacao, o metodo de permissao da versao local e symlinks.
Os originais cifrados permanecem intactos em /var/lib/mkauth-suite-cpf-opcional, fora da pasta publica.
Nenhum original, codigo nativo decodificado ou dado de cliente e distribuido no GitHub.
O backup cpf-opcional contem uma copia JSON completa de sis_opcao e o estado anterior da chave, com permissao privada, alem dos arquivos afetados.
O JSON serve como backup da tabela afetada; nao e um dump completo de todas as tabelas financeiras/clientes.
A reversao usa apenas a chave do patch, nunca restaura o restante da tabela ou o banco inteiro.

Integracao existente da origem e preservada, incluindo native/*.runtime.php e os cinco pontos de entrada.
Arquivos e modulos modificados depois da instalacao nao sao sobrescritos silenciosamente.
Formato nao reconhecido gera relatorio not_applied e preserva os arquivos nativos.
A rotina nao busca nem executa SQLs financeiros ou outros scripts do repositorio de migracao.

## Conferencia e reversao

O relatorio fica em BACKUP/cpf-opcional/cpf-install-report.json.
Analise sem aplicar: python3 scripts/install-cpf-policy.py /opt/mk-auth/admin /caminho/backup --dry-run
Reversao: python3 BACKUP/cpf-opcional/rollback-cpf-policy.py
A reversao cancela se algum arquivo ou a chave tiver sido alterado depois; revisar tais alteracoes antes de prosseguir.
Apos reverter, invalidar os arquivos PHP alterados no OPcache do processo web ou aguardar a revalidacao normal.

## Testes

Frontend: node addons/nn_cpf_opcional/tests/test_validator.mjs /opt/mk-auth/admin/scripts/mk-auth.js
Backend: scripts/test-cpf-policy.php aceita somente MYSQL_DB=mka_cpf_test_<hex>.
Usar um banco temporario com copia de sis_opcao e tabela cpf_fixture(login varchar(64) primary key, cpf_cnpj varchar(64)); nunca apontar esse teste ao banco ativo.
O teste faz gravacoes sinteticas apenas nessa base isolada.
A validacao inclui vazio ligado/desligado, criacao/edicao, valores invalidos, permissao e CSRF; o teste frontend usa o validador nativo local para CPF invalido/valido e outros campos obrigatorios.
Reinstalacao e reversao foram exercitadas com os cinco arquivos nativos copiados para uma arvore temporaria, sem executar seus controladores com sessoes artificiais.
Compatibilidade analisada em MK-AUTH 25.08 e preservacao do patch de origem em 26.03; outras versoes dependem das verificacoes locais.
