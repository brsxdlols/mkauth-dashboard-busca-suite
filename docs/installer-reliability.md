# Compatibilidade da instalação

O instalador seleciona HHVM para os links nativos da Busca e salva o valor anterior no backup.

A etapa de horário executa a correção pelo terminal, usando o cliente NTP existente, conferindo as referências e verificando novamente o resultado. O monitor web continua disponível. Uma falha ou ausência de duas referências concordantes gera aviso; o instalador não declara sincronização confirmada. Não instala outro daemon concorrente. Configurações de fuso e sincronização têm backup.

A variante conhecida do reconciliador com FORCE INDEX (acctstoptime) recebe a mesma correção de identidade de sessão das demais versões. Código desconhecido continua preservado. A instalação não executa o reconciliador nem derruba conexões.

A contagem de notas financeiras aceita UUIDs com collations utf8mb4 general e unicode distintas, sem converter tabelas nativas. A consulta opcional não deve interromper toda a listagem se estiver indisponível.

Um trigger tig_brl_pag externo é preservado, incluindo integrações Evolution/Recibo. Não é seguro substituí-lo apenas pelo nome. Os avisos da análise de atividades também indicam que a consulta nativa foi preservada quando a equivalência não pôde ser comprovada.

Quando o kernel usa AppArmor mas a imagem não inclui apparmor_parser, o instalador tenta obter e extrair a ferramenta do pacote Debian em um backup privado, sem instalar/iniciar o serviço global. Sem candidato APT, preserva a política e registra o aviso. A fila NTP usa o diretório nativo dados/suite-time-monitor, criado mesmo nessas imagens; o diagnóstico web confirma o acesso pelas permissões reais do operador.
