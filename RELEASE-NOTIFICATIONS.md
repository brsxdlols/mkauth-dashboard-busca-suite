# Dashboard: logs e notificações

- Log RADIUS em modal, com filtros, busca por nome/login nos eventos carregados e seleção de linhas; SQL desmarcado inicialmente.
- Consulta histórica por login limitada aos arquivos disponíveis, sem inventar registros de autenticação.
- Avisos de pagamento por usuário, modo simplificado/detalhado e duração de 1 a 30 segundos (padrão: 3).
- Controles Limpar e Não mostrar mais nos cards; instrução para reativar nas configurações.
- Cards de atendimento sem percentuais e alinhamento do campo de busca.
- Anexo de contrato continua incluído na busca e na instalação.

O instalador preserva a página principal de um addon Radius Logs já instalado.
Atualize as abas com Ctrl+F5 após instalar, para recarregar os scripts.

Teste visual sem registrar pagamentos: no servidor, execute `php /root/test-payment-notification.php USUARIO_ADMIN` mantendo uma aba aberta com esse usuário. O evento de demonstração expira; não altera cobranças.

Preferências de pagamento são salvas automaticamente por usuário. O modo simplificado mostra apenas o nome e o valor, além do título do aviso. Os logs periódicos só são consultados enquanto o modal está aberto.
