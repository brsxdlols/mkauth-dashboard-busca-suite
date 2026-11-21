<?php require_once __DIR__.'/config.php'; ?>

<!DOCTYPE html>
<html lang="pt-BR" class="has-navbar-fixed-top">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="utf-8">
<title>MK-AUTH :: <?php echo $Manifest->{'name'}; ?></title>
<link href="../rel_clientes/css/bootstrap.css" rel="stylesheet" type="text/css" />
<link href="../../estilos/mk-auth.css" rel="stylesheet" type="text/css" />
<link href="../../estilos/font-awesome.css" rel="stylesheet" type="text/css" />
<link href="../../estilos/bi-icons.css" rel="stylesheet" type="text/css" />
<script src="../../scripts/jquery.js"></script>
<script src="../../scripts/mk-auth.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.body.addEventListener('click', function(event) {
        if (event.target.closest('.btn-retirar')) {
            const btn = event.target.closest('.btn-retirar');
            const login = btn.getAttribute('data-login');
            const acao = btn.getAttribute('data-acao');

            const mensagem = `Tem certeza que deseja marcar "${acao.toUpperCase()}" para o cliente ${login}?`;

            if (!confirm(mensagem)) {
                return;
            }

            fetch('registrar_retirada.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({login:login,acao:acao,csrf:<?= json_encode($_SESSION['rd_csrf'],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>}).toString()
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === 'ok') {
                    location.reload();
                } else {
                    alert('Erro ao registrar alteração');
                }
            });
        }
    });
});



</script>



<link href="../shared/addon_header_mobile.css" rel="stylesheet"><link href="report.css?v=20261121-1" rel="stylesheet"></head>


<body class="off-suite rd-suite">
<?php include('../../topo.php'); ?>


<div class="container text-center mt-3">
    <h2 class="fs-5 text-dark fw-bold">
        <i class="bi bi-person-fill-x"></i> Relatório de Clientes Desativados - V <?php echo $Manifest->{'version'}; ?>
    </h2>
</div>



<ul class="nav nav-tabs justify-content-center py-2">
    <li class="nav-item">
        <a href="index.php" class="nav-link active" aria-current="page">
        INÍCIO
        </a>
    </li>
    <li class="nav-item">
        <a href="#" class="nav-link" onClick="window.print()">
            <i class="fa fa-print fs-4"></i>
        </a>
    </li>
</ul>
        <?php require_once __DIR__.'/config.php'; ?>



<form id="searchForm" method="GET" action="" class="form-inline">
    <div class="container">
        <div class="row">
            <div class="col-md-2">
                <label for="data_inicial"><b>Data Inicial:</b></label>
                <input type="date" name="data_inicial" id="data_inicial" class="form-control"
                    value="<?php echo isset($_GET['data_inicial']) ? htmlspecialchars($_GET['data_inicial']) : ''; ?>"
>
            </div>
            <div class="col-md-2">
                <label for="data_final"><b>Data Final:</b></label>
                <input type="date" name="data_final" id="data_final" class="form-control"
                    value="<?php echo isset($_GET['data_final']) ? htmlspecialchars($_GET['data_final']) : ''; ?>"
>
            </div>
            <div class="col-md-3">
                <label for="search"><b>Buscar Cliente:</b></label>
                <input type="text" id="search" name="search" class="form-control" placeholder="Digite o Login, Nome ou CPF/CNPJ"
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            </div>
        </div>

        <div class="row mt-3 d-flex justify-content-center">
            <div class="col-md-1">
                <input type="submit" value="Buscar" class="btn btn-primary">
            </div>
            <div class="col-md-1">
                <button id="clearSearchButton" type="button" onclick="clearSearch()" class="btn btn-secondary">Limpar</button>
            </div>
        </div>
    </div>
</form>

<script>
function clearSearch() {
    document.getElementById("search").value = "";
    document.getElementById("data_inicial").value = "";
    document.getElementById("data_final").value = "";
    document.getElementById("searchForm").submit();
}
</script>


<?php

$sqlCreateTable = "
CREATE TABLE IF NOT EXISTS cliente_desativados_ton (
    login VARCHAR(100) PRIMARY KEY,
    equipamento ENUM('sim', 'nao') NOT NULL DEFAULT 'nao',
    data_retirada DATETIME DEFAULT NULL
)";

$link->query($sqlCreateTable);



$pagina = max(1,isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1);
$limite = 40; 
$inicio = ($pagina - 1) * $limite;
$data_inicial = is_string($_GET['data_inicial']??null)?$_GET['data_inicial']:'';
$data_final = is_string($_GET['data_final']??null)?$_GET['data_final']:'';
$search = is_string($_GET['search']??null)?substr(trim($_GET['search']),0,160):'';

$sql = "SELECT c.nome, c.login, c.cpf_cnpj, c.data_desativacao, c.endereco, c.numero, c.bairro, c.cidade, c.estado, c.cep, c.fone, c.celular, c.celular2, c.complemento, 
               d.equipamento AS equipamento_status, d.data_retirada
        FROM sis_cliente c
        LEFT JOIN cliente_desativados_ton d ON c.login = d.login
        WHERE c.cli_ativado = 'n' AND ($rdScope)";
$params = [];
$types = "";

if (!empty($data_inicial) && !empty($data_final)) {
    $sql .= " AND DATE(c.data_desativacao) BETWEEN ? AND ?";
    $params[] = $data_inicial;
    $params[] = $data_final;
    $types .= "ss";
}

if (!empty($search)) {
    $sql .= " AND (c.nome LIKE ? OR c.login LIKE ? OR c.cpf_cnpj LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}


$sqlCount = "
    SELECT COUNT(*) as total
    FROM sis_cliente c
    LEFT JOIN cliente_desativados_ton d ON c.login = d.login
    WHERE c.cli_ativado = 'n' AND ($rdScope)
";

$paramsCount = [];
$typesCount = "";

if (!empty($data_inicial) && !empty($data_final)) {
    $sqlCount .= " AND DATE(c.data_desativacao) BETWEEN ? AND ?";
    $paramsCount[] = $data_inicial;
    $paramsCount[] = $data_final;
    $typesCount .= "ss";
}

if (!empty($search)) {
    $sqlCount .= " AND (c.nome LIKE ? OR c.login LIKE ? OR c.cpf_cnpj LIKE ?)";
    $searchParam = "%$search%";
    $paramsCount[] = $searchParam;
    $paramsCount[] = $searchParam;
    $paramsCount[] = $searchParam;
    $typesCount .= "sss";
}

$stmtCount = $link->prepare($sqlCount);
if (!empty($paramsCount)) {
    $stmtCount->bind_param($typesCount, ...$paramsCount);
}
$stmtCount->execute();
$resultCount = $stmtCount->get_result();
$totalRegistros = $resultCount->fetch_assoc()['total'];

$tot_paginas = ceil($totalRegistros / $limite);
$pc = $pagina;
$anterior = $pc - 1;
$proximo = $pc + 1;


$sql .= " ORDER BY c.data_desativacao DESC LIMIT ? OFFSET ?";
$params[] = $limite;
$params[] = $inicio;
$types .= "ii";


$stmt = $link->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();



?>
<?php
function formatarCelular($numero) {
    $numeros = preg_replace('/\D/', '', $numero);
    
    if (strlen($numeros) == 11) {
        return '('.substr($numeros, 0, 2).') '.substr($numeros, 2, 5).'-'.substr($numeros, 7, 4);
    } elseif (strlen($numeros) == 10) {
        return '('.substr($numeros, 0, 2).') '.substr($numeros, 2, 4).'-'.substr($numeros, 6, 4);
    } else {
        return $numero;
    }
}
?>

<div class="container-fluid">

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead class="bg-primary text-white">
  <tr>
    <th class="text-white">Nome/Login</th>
    <th class="text-white">Endereço</th>
    <th class="text-white">CPF/CNPJ</th>
    <th class="text-white">Telefone</th>
    <th class="text-white">Equipamento</th>
   <th class="text-white" style="white-space: nowrap;">Data de Retirada</th>
<th class="text-white" style="white-space: nowrap;">
  <i class="bi bi-calendar"></i> Desativação
</th>

 

</tr>

            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
               <td>
    <div style="white-space: nowrap;">
        <?php echo htmlspecialchars($row['nome']); ?><br>
        <small><?php echo htmlspecialchars($row['login']); ?></small>
    </div>
</td>

<td style="font-size: 13px; text-align: center;">
    <?php if (!empty($row['endereco'])): ?>
        <span style="white-space: nowrap;"><?php echo htmlspecialchars($row['endereco']); ?></span><br>
    <?php endif; ?>

    <?php if (!empty($row['numero'])): ?>
        <span style="white-space: nowrap;"><strong>Nº:</strong>&nbsp;<?php echo htmlspecialchars($row['numero']); ?></span><br>
    <?php endif; ?>

    <?php if (!empty($row['complemento'])): ?>
        <strong>Complemento:</strong>&nbsp;<?php echo htmlspecialchars($row['complemento']); ?><br>
    <?php endif; ?>

    <?php if (!empty($row['bairro'])): ?>
        <span style="white-space: nowrap;"><strong>Bairro:</strong>&nbsp;<?php echo htmlspecialchars($row['bairro']); ?></span><br>
    <?php endif; ?>

    <?php if (!empty($row['cidade'])): ?>
        <span style="white-space: nowrap;"><strong>Cidade:</strong>&nbsp;<?php echo htmlspecialchars($row['cidade']); ?></span><br>
    <?php endif; ?>

    <?php if (!empty($row['estado'])): ?>
        <span style="white-space: nowrap;"><strong>Estado:</strong>&nbsp;<?php echo htmlspecialchars($row['estado']); ?></span><br>
    <?php endif; ?>

    <?php if (!empty($row['cep'])): ?>
        <span style="white-space: nowrap;"><strong>Cep:</strong>&nbsp;<?php echo htmlspecialchars($row['cep']); ?></span>
    <?php endif; ?>
</td>

                            <td><?php echo htmlspecialchars($row['cpf_cnpj']); ?></td>


<td>
  <?php 
    if (!empty($row['celular'])) {
        echo '<span style="white-space: nowrap;">' . htmlspecialchars(formatarCelular($row['celular'])) . '</span><br>';
    }
    if (!empty($row['celular2'])) {
        echo '<span style="white-space: nowrap;">' . htmlspecialchars(formatarCelular($row['celular2'])) . '</span><br>';
    }
    if (!empty($row['fone'])) {
        echo '<span style="white-space: nowrap;">' . htmlspecialchars(formatarCelular($row['fone'])) . '</span>';
    }
  ?>
</td>



<td>
<?php
$equipamentoStatus = $row['equipamento_status'] ?? 'nao';
$login = htmlspecialchars($row['login']);
?>

<div class="btn-group btn-group-toggle" data-toggle="buttons">
    <label class="btn btn-outline-success btn-sm btn-retirar <?php echo ($equipamentoStatus === 'sim') ? 'active' : ''; ?>" data-login="<?php echo $login; ?>" data-acao="sim">
        <input type="radio" name="opcao_<?php echo $login; ?>" autocomplete="off" <?php echo ($equipamentoStatus === 'sim') ? 'checked' : ''; ?>> Sim
    </label>
    <label class="btn btn-outline-secondary btn-sm btn-retirar <?php echo ($equipamentoStatus === 'nao') ? 'active' : ''; ?>" data-login="<?php echo $login; ?>" data-acao="nao">
        <input type="radio" name="opcao_<?php echo $login; ?>" autocomplete="off" <?php echo ($equipamentoStatus === 'nao') ? 'checked' : ''; ?>> Não
    </label>
</div>

<?php if ($equipamentoStatus === 'sim'): ?>
    <div style="margin-top: 4px; font-weight: bold; color: #198754;">
        ✅ RETIRADO
    </div>
<?php endif; ?>


</td>

<td>
<?php
if (!empty($row['data_retirada'])) {
    $dt = strtotime($row['data_retirada']);
    $data = date('d/m/Y', $dt);
    $hora = date('H:i:s', $dt);
    echo '<span style="color: #198754; font-weight: bold;">'; 
    echo '<i class="bi bi-calendar" style="color: black; margin-right: 4px;"></i>' . $data . '<br>';
    echo '<i class="bi bi-clock" style="color: black; margin-right: 4px;"></i>' . $hora;
    echo '</span>';
}
?>
</td>


<td style="white-space: nowrap;">
    <strong><i class="bi bi-calendar me-1 text-black"></i><?php echo date('d/m/Y', strtotime($row['data_desativacao'])); ?></strong><br>
    <span><i class="bi bi-clock me-1 text-black"></i><?php echo date('H:i:s', strtotime($row['data_desativacao'])); ?></span>
</td>


                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">Nenhum cliente desativado encontrado com os filtros aplicados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            
        </table>
    </div>
</div>
<?php
// Recupera os parâmetros atuais para manter os filtros na paginação
$params = [];
if (!empty($_GET['data_inicial'])) $params['data_inicial'] = $_GET['data_inicial'];
if (!empty($_GET['data_final']))   $params['data_final']   = $_GET['data_final'];
if (!empty($_GET['search']))       $params['search']       = $_GET['search'];

// Monta a URL base (sem 'pagina')
$urlBase = $_SERVER['PHP_SELF'] . '?' . http_build_query($params);

$paginationHtml = '<nav><ul class="pagination d-flex flex-wrap justify-content-center lead">';

if ($pc > 1) {
    $paginationHtml .= '<li class="page-item"><a class="page-link" href="' . $urlBase . '&pagina=' . $anterior . '">Anterior</a></li>';
}

for ($i = 1; $i <= ceil($tot_paginas); $i++) {
    $activeClass = ($pc == $i) ? 'active' : '';
    $paginationHtml .= '<li class="page-item ' . $activeClass . '"><a class="page-link" href="' . $urlBase . '&pagina=' . $i . '">' . $i . '</a></li>';
}

if ($pc < $tot_paginas) {
    $paginationHtml .= '<li class="page-item"><a class="page-link" href="' . $urlBase . '&pagina=' . $proximo . '">Próxima</a></li>';
}

$paginationHtml .= '</ul></nav>';

echo $paginationHtml;
?>


<div class="pt-3">
    <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap" style="font-size: 12px; padding: 8px 0;">
        <i class="fa fa-whatsapp text-success" style="font-size: 16px;"></i>
        <span><strong>Addon criado por Elton Pereira</strong></span>
        <span>📱 WhatsApp: (82) 98736-1943</span>
        <a href="https://wa.me/5582987361943?text=Ol%C3%A1%2C+gostaria+de+saber+mais+sobre+o+addon+de+Envio+de+Mensagens+Personalizadas" 
           class="btn btn-link p-0 m-0 text-success" style="font-size: 11px;" target="_blank">
            <i class="fa fa-comments"></i> Fale comigo
        </a>
    </div>
</div>
        <?php include('../../baixo.php'); ?>
    </div>

    <script src="../../menu.js<?= htmlspecialchars($ext_mk,ENT_QUOTES,'UTF-8') ?>"></script>
    <?php include('../../rodape.php'); ?>

</body>

</html>
