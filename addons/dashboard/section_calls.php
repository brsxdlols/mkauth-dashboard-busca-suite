<?php if(!isset($conn)||!function_exists('permissao')){http_response_code(403);return;} ?>
<!-- Chamados -->

            <?php if ((permissao('perm_chamados')) && $tbl_chamados_abertos == 's') { ?>

                <div class='col-12 mb-3'>

                    <section class="mka-operation-card" data-dashboard-section="calls"><h2>Chamados em aberto</h2><div class="mka-operation-table"><table class='table table-sm'>
                        <thead>
                            <tr class="bg-primary text-uppercase">
                                <th class="fw-bold text-light"></th>
                                <th class='fw-bold text-light'>Chamado</th>
                                <th class='fw-bold text-light'>Abertura</th>
                                <th class='fw-bold text-light'>Nome [Login]</th>
                                <th class='fw-bold text-light'>Assunto</th>
                            </tr>
                        </thead>

                        <tbody>

                            <div class='row text-uppercase font-title'>

                            </div>
                            <?php

                            $query_sis_suporte = mysqli_query($conn, "SELECT s.chamado, s.abertura, s.login, s.assunto, c.nome FROM sis_suporte s LEFT JOIN sis_cliente c ON s.login = c.login WHERE $grupos s.status = 'aberto' AND c.cli_ativado = 's' ORDER BY s.abertura");

                            $tot_chamados = mysqli_num_rows($query_sis_suporte);
 if(!$tot_chamados)echo '<tr><td colspan="5" class="mka-operation-empty">Nenhum chamado em aberto.</td></tr>';

                            if (permissao('perm_totais')) {
                                echo "<script>$('#tot_chamados').html(\"$tot_chamados\")</script>";
                            }

                            $ccSup = 0;
                            while ($sup = mysqli_fetch_array($query_sis_suporte)) {

                                $sup_chamado = $sup['chamado'];
                                $sup_abertura = date('d/m/Y H:i:s', strtotime($sup['abertura']));
                                $sup_nome = $sup['nome'];

                                $sup_login = $sup['login'];
                                $sup_assunto = $sup['assunto'];
                            ?>

                                <tr class='font-field'>
                                    <td class="">
                                        <strong>
                                            <a href="/admin/chamado<?php echo $ext_mk; ?>?chamado=<?php echo $sup_chamado; ?>" onclick="window.open(this.href, this.target, 'width=800, height=500, scrollbars=yes'); return false;" class="btn-link text-decoration-none">Abrir </a>

                                            <a href="/admin/suporte_info<?php echo $ext_mk; ?>?login=<?php echo $sup_login; ?>&chamado=<?php echo $sup_chamado; ?>" onclick="window.open(this.href, this.target, 'width=800, height=500, scrollbars=yes'); return false;" class="btn-link text-decoration-none">Info</a>
                                        </strong>
                                    </td>

                                    <td class=''><?php echo $sup_chamado; ?></td>
                                    <td class=''><?php echo $sup_abertura; ?></td>
                                    <td class=''><?php echo "$sup_nome <strong>[$sup_login]</strong>"; ?></td>
                                    <td class=''><?php echo $sup_assunto; ?></td>
                                </tr>

                            <?php
                                $ccSup++;
                                if ($ccSup == 15) {
                                    break;
                                }
                            }
                            ?>

                        </tbody>
                    </table></div></section>
                </div>

            <?php } ?>
