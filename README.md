# MK Auth Dashboard + Busca Inteligente

Pacote base para instalar e manter dois addons do MK Auth:

- `addons/dashboard`
- `addons/busca_inteligente`
- `admin/index.hhvm` redirecionando para a dashboard

Esta base ja inclui:

- correcao da dashboard para obedecer `cor_menu` do MK Auth
- `mkauth_dashboard_top.php` integrado ao `dashboard/index.php`
- correcao do monitor de trafego da busca inteligente
- aviso de erro quando a API do roteador nao responde no inicio do teste

## Estrutura

```text
admin/index.hhvm
addons/dashboard
addons/busca_inteligente
install.sh
```

## Instalar no servidor

Copie o repositorio para o servidor e execute:

```bash
chmod +x install.sh
./install.sh
```

Opcionalmente, informe outro caminho do MK Auth:

```bash
./install.sh /opt/mk-auth/admin
```

O instalador cria backup antes de substituir:

- `admin/index.hhvm`
- `addons/dashboard`
- `addons/busca_inteligente`

## Observacoes

- O addon da busca foi organizado no repositorio como `busca_inteligente`
- O destino esperado no servidor e `/opt/mk-auth/admin/addons/busca_inteligente`
- O topo da dashboard usa a opcao `cor_menu` gravada em `sis_opcao`
