<?php require_once '../src/views/templates/header.php'; ?>

<!-- Sales List -->
<div class="container-fluid py-4">
    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-shopping-cart text-primary"></i> Vendas
        </h1>
        <div class="d-flex">
            <button class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#modalNovaVenda">
                <i class="fas fa-plus"></i> Nova Venda
            </button>
            <button class="btn btn-success btn-sm me-2" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Exportar
            </button>
            <button class="btn btn-info btn-sm" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="salesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Valor Total</th>
                            <th>Forma Pagto</th>
                            <th>Status</th>
                            <th>Vendedor</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td><?php echo $sale['id']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($sale['data_venda'])); ?></td>
                                <td><?php echo clean($sale['customer_name']); ?></td>
                                <td>R$ <?php echo number_format($sale['valor_total'], 2, ',', '.'); ?></td>
                                <td><?php echo clean($sale['forma_pagamento']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $sale['status'] == 'Concluída' ? 'success' : 
                                            ($sale['status'] == 'Pendente' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo $sale['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo clean($sale['user_name']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info me-1" 
                                            onclick="viewSale(<?php echo $sale['id']; ?>)"
                                            title="Visualizar">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($sale['status'] != 'Concluída'): ?>
                                        <button class="btn btn-sm btn-success me-1" 
                                                onclick="addPayment(<?php echo $sale['id']; ?>)"
                                                title="Adicionar Pagamento">
                                            <i class="fas fa-dollar-sign"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if (hasPermission('Administrador')): ?>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="deleteSale(<?php echo $sale['id']; ?>)"
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

<!-- Modal Nova Venda -->
<div class="modal fade" id="modalNovaVenda" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Venda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNovaVenda" onsubmit="return saveSale(event)">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Cliente</label>
                            <select class="form-select" name="customer_id" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['id']; ?>">
                                        <?php echo clean($customer['nome']); ?> 
                                        <?php echo $customer['cpf_cnpj'] ? '(' . clean($customer['cpf_cnpj']) . ')' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data da Venda</label>
                            <input type="datetime-local" class="form-control" name="data_venda" 
                                   value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Produtos</label>
                        <div id="produtos-container">
                            <div class="row mb-2 produto-item">
                                <div class="col-md-6">
                                    <select class="form-select" name="produtos[]" required onchange="updatePrice(this)">
                                        <option value="">Selecione...</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product['id']; ?>" 
                                                    data-price="<?php echo $product['preco_venda']; ?>"
                                                    data-stock="<?php echo $product['estoque']; ?>">
                                                <?php echo clean($product['nome']); ?> 
                                                (R$ <?php echo number_format($product['preco_venda'], 2, ',', '.'); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" name="quantidades[]" 
                                           placeholder="Quantidade" min="1" required onchange="updateSubtotal(this)">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger" onclick="removeProduto(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary mt-2" onclick="addProduto()">
                            <i class="fas fa-plus"></i> Adicionar Produto
                        </button>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Forma de Pagamento</label>
                            <select class="form-select" name="forma_pagamento" required>
                                <option value="">Selecione...</option>
                                <option value="Dinheiro">Dinheiro</option>
                                <option value="Cartão de Crédito">Cartão de Crédito</option>
                                <option value="Cartão de Débito">Cartão de Débito</option>
                                <option value="PIX">PIX</option>
                                <option value="Boleto">Boleto</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Total</label>
                            <input type="text" class="form-control" id="total" readonly>
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
                <button type="submit" form="formNovaVenda" class="btn btn-primary">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Visualizar Venda -->
<div class="modal fade" id="modalViewSale" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes da Venda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="saleDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" onclick="printSale()">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pagamento -->
<div class="modal fade" id="modalPayment" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Pagamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formPayment" onsubmit="return savePayment(event)">
                    <input type="hidden" name="sale_id" id="payment_sale_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Valor</label>
                        <input type="number" class="form-control" name="valor" step="0.01" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Forma de Pagamento</label>
                        <select class="form-select" name="forma_pagamento" required>
                            <option value="">Selecione...</option>
                            <option value="Dinheiro">Dinheiro</option>
                            <option value="Cartão de Crédito">Cartão de Crédito</option>
                            <option value="Cartão de Débito">Cartão de Débito</option>
                            <option value="PIX">PIX</option>
                            <option value="Boleto">Boleto</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Data do Pagamento</label>
                        <input type="datetime-local" class="form-control" name="data_pagamento" 
                               value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formPayment" class="btn btn-primary">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
// DataTables
$(document).ready(function() {
    $('#salesTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
        },
        order: [[0, 'desc']]
    });
});

// Funções para manipulação de produtos
function addProduto() {
    const container = document.getElementById('produtos-container');
    const newItem = container.children[0].cloneNode(true);
    
    // Limpar valores
    newItem.querySelector('select').value = '';
    newItem.querySelector('input').value = '';
    
    container.appendChild(newItem);
}

function removeProduto(button) {
    const container = document.getElementById('produtos-container');
    if (container.children.length > 1) {
        button.closest('.produto-item').remove();
        updateTotal();
    }
}

function updatePrice(select) {
    const row = select.closest('.produto-item');
    const quantityInput = row.querySelector('input[name="quantidades[]"]');
    const option = select.selectedOptions[0];
    
    if (option) {
        const stock = option.dataset.stock;
        quantityInput.max = stock;
    }
    
    updateSubtotal(quantityInput);
}

function updateSubtotal(input) {
    const row = input.closest('.produto-item');
    const select = row.querySelector('select');
    const option = select.selectedOptions[0];
    
    if (option && input.value) {
        const price = parseFloat(option.dataset.price);
        const quantity = parseInt(input.value);
        const stock = parseInt(option.dataset.stock);
        
        if (quantity > stock) {
            alert('Quantidade maior que o estoque disponível!');
            input.value = stock;
        }
    }
    
    updateTotal();
}

function updateTotal() {
    let total = 0;
    const rows = document.querySelectorAll('.produto-item');
    
    rows.forEach(row => {
        const select = row.querySelector('select');
        const input = row.querySelector('input');
        const option = select.selectedOptions[0];
        
        if (option && option.dataset.price && input.value) {
            const price = parseFloat(option.dataset.price);
            const quantity = parseInt(input.value);
            total += price * quantity;
        }
    });
    
    document.getElementById('total').value = total.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
}

// Funções AJAX
async function saveSale(event) {
    event.preventDefault();
    
    try {
        const form = event.target;
        const formData = new FormData(form);
        
        const response = await fetch('index.php?controller=sales&action=create', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            showSuccess(result.message);
            $('#modalNovaVenda').modal('hide');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Erro ao salvar venda');
    }
    
    return false;
}

async function viewSale(id) {
    try {
        const response = await fetch(`index.php?controller=sales&action=view&id=${id}`);
        const result = await response.json();
        
        if (result.status === 'success') {
            const sale = result.sale;
            const items = result.items;
            const payments = result.payments;
            
            let html = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Informações da Venda</h6>
                        <p><strong>Cliente:</strong> ${sale.customer_name}</p>
                        <p><strong>Data:</strong> ${formatDate(sale.data_venda)}</p>
                        <p><strong>Valor Total:</strong> ${formatMoney(sale.valor_total)}</p>
                        <p><strong>Forma de Pagamento:</strong> ${sale.forma_pagamento}</p>
                        <p><strong>Status:</strong> ${sale.status}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Informações Adicionais</h6>
                        <p><strong>Vendedor:</strong> ${sale.user_name}</p>
                        <p><strong>Observações:</strong> ${sale.observacoes || '-'}</p>
                    </div>
                </div>

                <h6>Itens da Venda</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Quantidade</th>
                                <th>Preço Unit.</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${items.map(item => `
                                <tr>
                                    <td>${item.product_name}</td>
                                    <td>${item.quantidade}</td>
                                    <td>${formatMoney(item.preco_unitario)}</td>
                                    <td>${formatMoney(item.subtotal)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>

                <h6>Pagamentos</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Valor</th>
                                <th>Forma</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${payments.map(payment => `
                                <tr>
                                    <td>${formatDate(payment.data_pagamento)}</td>
                                    <td>${formatMoney(payment.valor)}</td>
                                    <td>${payment.forma_pagamento}</td>
                                    <td>${payment.status}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
            
            document.getElementById('saleDetails').innerHTML = html;
            $('#modalViewSale').modal('show');
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Erro ao carregar venda');
    }
}

function addPayment(id) {
    document.getElementById('payment_sale_id').value = id;
    $('#modalPayment').modal('show');
}

async function savePayment(event) {
    event.preventDefault();
    
    try {
        const form = event.target;
        const formData = new FormData(form);
        
        const response = await fetch('index.php?controller=sales&action=addPayment', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            showSuccess(result.message);
            $('#modalPayment').modal('hide');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Erro ao salvar pagamento');
    }
    
    return false;
}

function deleteSale(id) {
    confirmDelete('Deseja realmente excluir esta venda?', async () => {
        try {
            const formData = new FormData();
            formData.append('id', id);
            
            const response = await fetch('index.php?controller=sales&action=delete', {
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
            showError('Erro ao excluir venda');
        }
    });
}

function printSale() {
    const content = document.getElementById('saleDetails').innerHTML;
    const win = window.open('', '', 'height=700,width=700');
    win.document.write(`
        <html>
            <head>
                <title>Impressão de Venda</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body>
                <div class="container mt-4">
                    <h4 class="text-center mb-4">${SITE_NAME} - Comprovante de Venda</h4>
                    ${content}
                </div>
            </body>
        </html>
    `);
    win.document.close();
    win.print();
}

function exportToExcel() {
    const table = document.getElementById('salesTable');
    const wb = XLSX.utils.table_to_book(table, {sheet: "Vendas"});
    XLSX.writeFile(wb, 'vendas.xlsx');
}
</script>

<?php require_once '../src/views/templates/footer.php'; ?>
