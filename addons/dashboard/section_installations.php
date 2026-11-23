<?php if(!isset($conn)||!function_exists('permissao')){http_response_code(403);return;} ?>
<!-- Instalacoes -->

            <?php if ((permissao('perm_instalacao'))) { ?>

                <div class='col-12 mb-3 installation-highlight-target' id='solicitacoes-instalacao'>
                    <section class="mka-operation-card" data-dashboard-section="installations"><h2>Instalações pendentes</h2><div class="mka-operation-table"><table class='table table-sm'>
                        <thead>
                            <tr class="bg-primary text-uppercase">
                                <th class='fw-bold text-light'></th>
                                <th class='fw-bold text-light'>Instalação</th>
                                <th class='fw-bold text-light'>Nome</th>
                                <th class='fw-bold text-light'>Técnico</th>
								<th class='fw-bold text-light'>Data de Cadastro</th> <!-- Nova coluna -->
                            </tr>
                        </thead>

                        <tbody>

                            <?php

                            $query_sis_instalacao = mysqli_query($conn, "
							SELECT uuid_solic, disp, nome, login, tecnico, processamento AS data_cadastro 
							FROM sis_solic 
							WHERE status = 'aberto' 
							AND (datainst <= '$nova_data_1' OR visita <= '$nova_data_1' OR datainst IS NULL) 
							ORDER BY datainst
								");
								
                            $tot_instalacoes = mysqli_num_rows($query_sis_instalacao);
 if(!$tot_instalacoes)echo '<tr><td colspan="5" class="mka-operation-empty">Nenhuma instalação pendente.</td></tr>';

                            $tot_atendimento = $tot_chamados + $tot_instalacoes;
                            $perc_chamados = $tot_atendimento > 0 ? number_format($tot_chamados / $tot_atendimento * 100, 2) : '0.00';
                            $perc_instalacoes = $tot_atendimento > 0 ? number_format($tot_instalacoes / $tot_atendimento * 100, 2) : '0.00';

                            if (permissao('perm_totais')) {
                                // The card above counts scoped installation visits, independently of website requests below.
                            }
                            echo "<script>$('#perc_instalacoes').html(\"$perc_instalacoes%\")</script>";
                            echo "<script>$('#perc_chamados').html(\"$perc_chamados%\")</script>";

                            $ccInst = 0;

                            while ($inst = mysqli_fetch_array($query_sis_instalacao)) {
                                $inst_uuid = $inst['uuid_solic'];
                                $inst_disponivel = $inst['disp'] == "sim" ? "Disponível" : "Indisponível";
                                $inst_nome = $inst['nome'];
                                $inst_login = $inst['login'];
                                $inst_tecnico = $inst['tecnico'];
								$data_cadastro = !empty($inst['data_cadastro']) ? date("d/m/Y", strtotime($inst['data_cadastro'])) : "N/A"; // Formata a data
                            ?>

                                <tr class='font-field'>
                                    <td class="">
                                        <strong>
                                            <a href="/admin/instalar_alt<?php echo $ext_mk; ?>?uuid=<?php echo $inst_uuid; ?>" class="btn-link text-decoration-none">Alterar </a>

                                            <a href="/admin/instalacao_info<?php echo $ext_mk; ?>?uuid=<?php echo $inst_uuid; ?>" onclick="window.open(this.href, this.target, 'width=800, height=500, scrollbars=yes'); return false;" class="btn-link text-decoration-none">Info</a>

                                            <a href="/admin/cliente_ins<?php echo $ext_mk; ?>?new_install=<?php echo $inst_uuid; ?>" class="btn-link text-decoration-none" target="_blank">Incluir Cliente</a>

                                            <a href="#" class="btn-link text-danger text-decoration-none delete-installation-request" data-solic="<?php echo htmlspecialchars($inst_uuid, ENT_QUOTES, 'UTF-8'); ?>" title="Excluir esta solicitação"><i class="bi bi-trash3"></i> Excluir</a>

                                        </strong>
                                    </td>
                                    <td class=''><?php echo $inst_disponivel; ?></td>
                                    <td class=''><?php echo "$inst_nome <strong>[$inst_login]</strong>"; ?></td>
                                    <td class=''><?php echo $inst_tecnico; ?></td>
									<td class=''><?php echo $data_cadastro; ?></td> <!-- Exibe a data de cadastro -->
                                </tr>

                            <?php
                                $ccInst++;
                                if ($ccInst == 500) {
                                    break;
                                }
                            }
                            ?>

                        </tbody>
                    </table></div></section>
                </div>
            <?php } ?>
