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

                                <tr class='font-field' data-dashboard-ticket-row tabindex="0" aria-label="Ver detalhes do chamado">
                                    <td class="">
                                        <a href="<?=htmlspecialchars('/admin/suporte_info'.$ext_mk.'?'.http_build_query(array('login'=>$sup_login,'chamado'=>$sup_chamado)),ENT_QUOTES,'UTF-8')?>" data-dashboard-ticket-info data-client-login="<?=htmlspecialchars($sup_login,ENT_QUOTES,'UTF-8')?>" data-client-name="<?=htmlspecialchars($sup_nome,ENT_QUOTES,'UTF-8')?>" data-ticket-reference="<?=htmlspecialchars($sup_chamado,ENT_QUOTES,'UTF-8')?>" class="btn-link text-decoration-none">Ver chamado</a>
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
