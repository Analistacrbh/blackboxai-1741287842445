<?php require_once '../src/views/templates/header.php'; ?>

<!-- Customers List -->
<div class="container-fluid py-4">
    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-users text-primary"></i> Clientes
        </h1>
        <div class="d-flex">
            <button class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#modalNovoCliente">
                <i class="fas fa-plus"></i> Novo Cliente
            </button>
            <button class="btn btn-success btn-sm me-2" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Exportar
            </button>
            <button class="btn btn-info btn-sm" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <!-- Total Customers Card -->
        <div class="col-xl-4 col-md-6">
            <div class="card border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total de Clientes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_customers']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Customers Card -->
        <div class="col-xl-4 col-md-6">
            <div class="card border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Novos Clientes (Este Mês)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['new_customers']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Customers Card -->
        <div class="col-xl-4 col-md-12">
            <div class="card border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                        Melhores Clientes
                    </div>
                    <?php if (empty($stats['top_customers'])): ?>
                        <p class="text-center text-muted mb-0">Nenhuma venda registrada</p>
                    <?php else: ?>
                        <div class="small">
                            <?php foreach (array_slice($stats['top_customers'], 0, 3) as $customer): ?>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span><?php echo clean($customer['nome']); ?></span>
                                    <span class="text-success">
                                        R$ <?php echo number_format($customer['valor_total'], 2, ',', '.'); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Customers Table -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="customersTable">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF/CNPJ</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Cidade/UF</th>
                            <th>Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo clean($customer['nome']); ?></td>
                                <td><?php echo clean($customer['cpf_cnpj']); ?></td>
                                <td><?php echo clean($customer['email']); ?></td>
                                <td><?php echo clean($customer['telefone']); ?></td>
                                <td>
                                    <?php 
                                        echo clean($customer['cidade']);
                                        echo $customer['estado'] ? '/' . clean($customer['estado']) : '';
                                    ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info me-1" 
                                            onclick="viewCustomer(<?php echo $customer['id']; ?>)"
                                            title="Visualizar">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-primary me-1" 
                                            onclick="editCustomer(<?php echo $customer['id']; ?>)"
                                            title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if (hasPermission('Administrador')): ?>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="deleteCustomer(<?php echo $customer['id']; ?>)"
                                                title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo/Editar Cliente -->
<div class="modal fade" id="modalCliente" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formCliente" onsubmit="return saveCustomer(event)">
                    <input type="hidden" name="id" id="customer_id">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome</label>
                            <input type="text" class="form-control" name="nome" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CPF/CNPJ</label>
                            <input type="text" class="form-control" name="cpf_cnpj">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="telefone">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Endereço</label>
                        <input type="text" class="form-control" name="endereco">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Cidade</label>
                            <input type="text" class="form-control" name="cidade">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="">Selecione...</option>
                                <option value="AC">AC</option>
                                <option value="AL">AL</option>
                                <option value="AP">AP</option>
                                <option value="AM">AM</option>
                                <option value="BA">BA</option>
                                <option value="CE">CE</option>
                                <option value="DF">DF</option>
                                <option value="ES">ES</option>
                                <option value="GO">GO</option>
                                <option value="MA">MA</option>
                                <option value="MT">MT</option>
                                <option value="MS">MS</option>
                                <option value="MG">MG</option>
                                <option value="PA">PA</option>
                                <option value="PB">PB</option>
                                <option value="PR">PR</option>
                                <option value="PE">PE</option>
                                <option value="PI">PI</option>
                                <option value="RJ">RJ</option>
                                <option value="RN">RN</option>
                                <option value="RS">RS</option>
                                <option value="RO">RO</option>
                                <option value="RR">RR</option>
                                <option value="SC">SC</option>
                                <option value="SP">SP</option>
                                <option value="SE">SE</option>
                                <option value="TO">TO</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CEP</label>
                            <input type="text" class="form-control" name="cep">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formCliente" class="btn btn-primary">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Visualizar Cliente -->
<div class="modal fade" id="modalViewCustomer" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="customerDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" onclick="printCustomer()">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// DataTables
$(document).ready(function() {
    $('#customersTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
        }
    });
});

// Máscaras de input
$(document).ready(function() {
    $('input[name="cpf_cnpj"]').inputmask({
        mask: ['999.999.999-99', '99.999.999/9999-99'],
        keepStatic: true
    });
    $('input[name="telefone"]').inputmask('(99) 99999-9999');
    $('input[name="cep"]').inputmask('99999-999');
});

// Funções AJAX
async function saveCustomer(event) {
    event.preventDefault();
    
    try {
        const form = event.target;
        const formData = new FormData(form);
        const isEdit = formData.get('id') !== '';
        
        const response = await fetch(`index.php?controller=customers&action=${isEdit ? 'update' : 'create'}`, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            showSuccess(result.message);
            $('#modalCliente').modal('hide');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Erro ao salvar cliente');
    }
    
    return false;
}

async function viewCustomer(id) {
    try {
        const response = await fetch(`index.php?controller=customers&action=view&id=${id}`);
        const result = await response.json();
        
        if (result.status === 'success') {
            const customer = result.customer;
            const sales = result.sales;
            
            let html = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Informações Pessoais</h6>
                        <p><strong>Nome:</strong> ${customer.nome}</p>
                        <p><strong>CPF/CNPJ:</strong> ${customer.cpf_cnpj || '-'}</p>
                        <p><strong>Email:</strong> ${customer.email || '-'}</p>
                        <p><strong>Telefone:</strong> ${customer.telefone || '-'}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Endereço</h6>
                        <p><strong>Endereço:</strong> ${customer.endereco || '-'}</p>
                        <p><strong>Cidade/UF:</strong> ${customer.cidade}/${customer.estado || '-'}</p>
                        <p><strong>CEP:</strong> ${customer.cep || '-'}</p>
                    </div>
                </div>

                <div class="mb-3">
                    <h6>Observações</h6>
                    <p>${customer.observacoes || '-'}</p>
                </div>

                <h6>Últimas Vendas</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Vendedor</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${sales.length === 0 ? `
                                <tr>
                                    <td colspan="4" class="text-center">Nenhuma venda encontrada</td>
                                </tr>
                            ` : sales.map(sale => `
                                <tr>
                                    <td>${formatDate(sale.data_venda)}</td>
                                    <td>${formatMoney(sale.valor_total)}</td>
                                    <td>${sale.status}</td>
                                    <td>${sale.user_name}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
            
            document.getElementById('customerDetails').innerHTML = html;
            $('#modalViewCustomer').modal('show');
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Erro ao carregar cliente');
    }
}

async function editCustomer(id) {
    try {
        const response = await fetch(`index.php?controller=customers&action=view&id=${id}`);
        const result = await response.json();
        
        if (result.status === 'success') {
            const customer = result.customer;
            const form = document.getElementById('formCliente');
            
            // Preencher formulário
            form.querySelector('[name="id"]').value = customer.id;
            form.querySelector('[name="nome"]').value = customer.nome;
            form.querySelector('[name="cpf_cnpj"]').value = customer.cpf_cnpj;
            form.querySelector('[name="email"]').value = customer.email;
            form.querySelector('[name="telefone"]').value = customer.telefone;
            form.querySelector('[name="endereco"]').value = customer.endereco;
            form.querySelector('[name="cidade"]').value = customer.cidade;
            form.querySelector('[name="estado"]').value = customer.estado;
            form.querySelector('[name="cep"]').value = customer.cep;
            form.querySelector('[name="observacoes"]').value = customer.observacoes;
            
            // Atualizar título do modal
            document.querySelector('#modalCliente .modal-title').textContent = 'Editar Cliente';
            
            $('#modalCliente').modal('show');
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Erro ao carregar cliente');
    }
}

function deleteCustomer(id) {
    confirmDelete('Deseja realmente excluir este cliente?', async () => {
        try {
            const formData = new FormData();
            formData.append('id', id);
            
            const response = await fetch('index.php?controller=customers&action=delete', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.status === 'success') {
                showSuccess(result.message);
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showError(result.message);
            }
        } catch (error) {
            showError('Erro ao excluir cliente');
        }
    });
}

// Reset form when modal is closed
$('#modalCliente').on('hidden.bs.modal', function() {
    document.getElementById('formCliente').reset();
    document.getElementById('customer_id').value = '';
    document.querySelector('#modalCliente .modal-title').textContent = 'Novo Cliente';
});

function printCustomer() {
    const content = document.getElementById('customerDetails').innerHTML;
    const win = window.open('', '', 'height=700,width=700');
    win.document.write(`
        <html>
            <head>
                <title>Impressão de Cliente</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body>
                <div class="container mt-4">
                    <h4 class="text-center mb-4">${SITE_NAME} - Ficha do Cliente</h4>
                    ${content}
                </div>
            </body>
        </html>
    `);
    win.document.close();
    win.print();
}

function exportToExcel() {
    const table = document.getElementById('customersTable');
    const wb = XLSX.utils.table_to_book(table, {sheet: "Clientes"});
    XLSX.writeFile(wb, 'clientes.xlsx');
}
</script>

<?php require_once '../src/views/templates/footer.php'; ?>
