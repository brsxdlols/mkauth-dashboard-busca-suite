<?php
require_once('config.php');
require_once('offline-filters.php');
require_once('offline-settings.php');
if (empty($_SESSION['off_display_csrf'])) $_SESSION['off_display_csrf'] = bin2hex(random_bytes(32));
$display = off_display_settings($link);
$message = ''; $error = '';
$legacy = array('mail_host'=>'','mail_username'=>'','mail_port'=>'587','link_logo'=>'');
$result = mysqli_query($link, 'SELECT mail_host, mail_username, mail_port, link_logo FROM sis_rel_con_ms_cfg WHERE id = 1');
if ($result && ($row = mysqli_fetch_assoc($result))) $legacy = $row;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : '';
    if (!hash_equals($_SESSION['off_display_csrf'], $token)) $error = 'A sessão do formulário expirou. Recarregue a página e tente novamente.';
    elseif (isset($_POST['save_display'])) {
        $hours = isset($_POST['alert_hours']) ? $_POST['alert_hours'] : '';
        if (!off_display_valid_hours($hours)) $error = 'Informe um período entre 0,01 e 876000 horas, com até duas casas decimais.';
        else {
            $enabled = isset($_POST['alert_enabled']) ? 1 : 0; $number = (float)$hours;
            $stmt = mysqli_prepare($link, 'INSERT INTO sis_rel_cli_offline_display_cfg (id, enabled, hours) VALUES (1, ?, ?) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled), hours=VALUES(hours)');
            $ok = false;
            if ($stmt) { mysqli_stmt_bind_param($stmt, 'id', $enabled, $number); $ok = mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt); }
            if ($ok) { $display = off_display_settings($link); $message = 'Destaque atualizado com sucesso.'; }
            else $error = 'Não foi possível salvar as configurações. Tente novamente.';
        }
    } elseif (isset($_POST['save_legacy'])) {
        foreach ($legacy as $key=>$value) if (isset($_POST[$key]) && is_string($_POST[$key])) $legacy[$key] = trim($_POST[$key]);
        $password = isset($_POST['mail_password']) && is_string($_POST['mail_password']) ? $_POST['mail_password'] : '';
        $stmt = mysqli_prepare($link, "UPDATE sis_rel_con_ms_cfg SET mail_host=?, mail_username=?, mail_port=?, link_logo=?, mail_password=IF(?='',mail_password,?) WHERE id=1");
        $ok = false;
        if ($stmt) { mysqli_stmt_bind_param($stmt,'ssssss',$legacy['mail_host'],$legacy['mail_username'],$legacy['mail_port'],$legacy['link_logo'],$password,$password); $ok = mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt); }
        if ($ok) $message = 'Configurações de e-mail atualizadas.'; else $error = 'Não foi possível salvar as configurações de e-mail.';
    }
}
?>
<!doctype html><?php echo isset($_SESSION['MM_Usuario']) ? '<html lang="pt-BR">' : '<html lang="pt-BR" class="has-navbar-fixed-top">'; ?><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Clientes offline · Configurações</title><link rel="stylesheet" href="../../estilos/mk-auth.css"><link rel="stylesheet" href="../../estilos/font-awesome.css"><script src="../../scripts/jquery.js"></script><script src="../../scripts/mk-auth.js"></script><link rel="stylesheet" href="offline-clean.css?v=20261017-1"></head><body>
<?php include('../../topo.php'); ?>
<main id="offline-report" class="off-config"><header class="off-heading"><div><span>RELATÓRIO DE CONEXÕES</span><h1>Configurações</h1><p>Defina quando uma desconexão merece mais atenção.</p></div><div class="off-tools"><a href="index.php">Voltar ao relatório</a></div></header>
<?php if ($message): ?><p role="status" class="off-success"><?= off_h($message) ?></p><?php endif; ?>
<?php if ($error): ?><p role="alert" class="off-error"><?= off_h($error) ?></p><?php endif; ?>
<form method="post" class="off-settings-card"><input type="hidden" name="csrf" value="<?= off_h($_SESSION['off_display_csrf']) ?>"><h2>Destaque por tempo offline</h2><p>O tempo offline ganha um destaque vermelho e a linha recebe uma faixa discreta ao atingir o limite. Vale para clientes ativos e bloqueados.</p><label class="off-toggle"><input type="checkbox" name="alert_enabled" value="1" <?= $display['enabled'] ? 'checked' : '' ?>> Ativar destaque vermelho</label><label class="off-hours-setting">Destacar a partir de quantas horas?<input type="number" name="alert_hours" value="<?= off_h($display['hours']) ?>" min="0.01" max="876000" step="0.01" required></label><p class="off-help">Exemplos: 0,5 hora = 30 minutos · 6 horas · 24 horas = 1 dia. Esta configuração vale para todos os usuários do relatório.</p><div class="off-red-preview">Cliente offline há <?= off_h($display['hours']) ?> horas ou mais <strong>Exemplo de destaque</strong></div><p class="off-help">Ao abrir o relatório sem escolher uma ordenação, os clientes com maior tempo offline aparecem primeiro.</p><button type="submit" name="save_display" value="1" class="off-save">Salvar configurações</button></form>
<details class="off-settings-card"><summary>Configurações existentes de e-mail e logo</summary><form method="post"><input type="hidden" name="csrf" value="<?= off_h($_SESSION['off_display_csrf']) ?>"><?php foreach(array('mail_host'=>'Servidor SMTP','mail_username'=>'E-mail','mail_port'=>'Porta','link_logo'=>'Link do logo') as $key=>$label): ?><label class="off-hours-setting"><?= $label ?><input type="text" name="<?= $key ?>" value="<?= off_h($legacy[$key]) ?>"></label><?php endforeach; ?><label class="off-hours-setting">Nova senha (deixe vazia para manter a atual)<input type="password" name="mail_password" value="" autocomplete="new-password"></label><button type="submit" name="save_legacy" value="1" class="off-save">Salvar e-mail e logo</button></form></details>
</main><?php mysqli_close($link); include('../../baixo.php'); ?><script src="../../menu.js.php"></script></body></html>
