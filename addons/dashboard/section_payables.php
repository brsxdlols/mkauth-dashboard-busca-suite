<?php if(!isset($conn)||!function_exists('permissao')){http_response_code(403);return;} ?>
<!-- Contas a pagar -->
            <?php if ((permissao('perm_contaspagar')) && $tbl_contas_pagar == 's') { ?>

                <div class='col-12 col'>

                    <section class="mka-operation-card" data-dashboard-section="payables"><h2>Contas a pagar</h2><div class="mka-operation-table"><table class='table table-sm'>
                        <thead>
                            <tr class="bg-primary text-uppercase">
                                <th class='fw-bold text-light'></th>
                                <th class='fw-bold text-light'>Título</th>
                                <th class='fw-bold text-light'>Parcela</th>
                                <th class='fw-bold text-light'>Fornecedor / Funcionário</th>
                                <th class='fw-bold text-light'>Vencimento</th>
                                <th class='fw-bold text-light'>Valor</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php

                            $query_sis_contaspagar = mysqli_query($conn, "SELECT c.id AS id_conta, c.nrdocumento, c.parcatual, c.numparcelas, c.historico, c.vencimento, c.valor, f.razaosoc, c.tipodiv, func.nome , c.uuid_contaspagar
                            FROM sis_contaspagar c 
                            LEFT JOIN sis_fornecedor f ON c.fornecedor = f.id 
                            LEFT JOIN sis_func func ON c.fornecedor = func.id
                            WHERE c.status NOT LIKE 'liquidado' AND c.vencimento <= '$nova_data_5'  ORDER BY c.vencimento");


                            $ccCP = 0;if($query_sis_contaspagar&&mysqli_num_rows($query_sis_contaspagar)===0)echo '<tr><td colspan="6" class="mka-operation-empty">Nenhuma conta a pagar no período exibido.</td></tr>';
                            while ($cp = mysqli_fetch_array($query_sis_contaspagar)) {
                                $cp_id = $cp['id_conta'];
                                $cp_uuid_contaspagar = $cp['uuid_contaspagar'];
                                $cp_nrdocumento = $cp['nrdocumento'];
                                $cp_parcatual = $cp['parcatual'];
                                $cp_numparcelas = $cp['numparcelas'];

                                if ($cp['tipodiv'] == "for") {
                                    $cp_fornecedor = $cp['razaosoc'] . " - " . $cp['historico'];
                                } else {
                                    $cp_fornecedor = $cp['nome'] . " - " . $cp['historico'];
                                }

                                $cp_vencimento = date('d/m/Y', strtotime($cp['vencimento']));

                                $cp_valor = number_format($cp['valor'], 2, ',', '.');
                            ?>

                                <tr class='font-field' data-dashboard-payable-detail tabindex="0" role="button" aria-label="Ver detalhes da conta a pagar">
                                    <td class="">
                                        <strong>
                                            <a href="/admin/contaspagar_liquidar<?php echo $ext_mk; ?>?uuid=<?php echo $cp_uuid_contaspagar; ?>" class="btn-link text-decoration-none">Liquidar</a>
                                        </strong>
                                    </td>
                                    <td class=''><?php echo $cp_nrdocumento; ?></td>
                                    <td class=''><?php echo "$cp_parcatual / $cp_numparcelas"; ?></td>
                                    <td class=''><?php echo $cp_fornecedor; ?></td>
                                    <td class=''><?php echo $cp_vencimento; ?></td>
                                    <td class=''>R$ <?php echo $cp_valor; ?></td>
                                </tr>

                            <?php
                                $ccCP++;
                                if ($ccCP == 15) {
                                    break;
                                }
                            }
                            ?>

                        </tbody>
                    </table></div></section>
                </div>

            <?php } ?>
