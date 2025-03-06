<?php require_once '../src/views/templates/header.php'; ?>

<!-- Dashboard Content -->
<div class="container-fluid py-4">
    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tachometer-alt text-primary"></i> Dashboard
        </h1>
        <div class="d-flex">
            <button class="btn btn-primary btn-sm me-2" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
            </button>
            <button class="btn btn-success btn-sm" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Exportar
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <!-- Daily Sales Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Vendas (Hoje)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                R$ <?php echo number_format($totals['daily_sales'], 2, ',', '.'); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Sales Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Vendas (Mês)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                R$ <?php echo number_format($totals['monthly_sales'], 2, ',', '.'); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customers Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total de Clientes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($totals['total_customers']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total de Produtos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($totals['total_products']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Sales Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Vendas dos Últimos 7 Dias</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Products -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Produtos com Estoque Baixo</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($lowStock)): ?>
                        <p class="text-center text-muted">Nenhum produto com estoque baixo.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Estoque</th>
                                        <th>Mínimo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStock as $product): ?>
                                        <tr>
                                            <td><?php echo clean($product['nome']); ?></td>
                                            <td><?php echo $product['estoque']; ?></td>
                                            <td><?php echo $product['estoque_minimo']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities Row -->
    <div class="row">
        <!-- Recent Sales -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Vendas Recentes</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($recentSales)): ?>
                        <p class="text-center text-muted">Nenhuma venda recente.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Cliente</th>
                                        <th>Valor</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentSales as $sale): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($sale['data_venda'])); ?></td>
                                            <td><?php echo clean($sale['customer_name']); ?></td>
                                            <td>R$ <?php echo number_format($sale['valor_total'], 2, ',', '.'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $sale['status'] == 'Concluída' ? 'success' : 'warning'; ?>">
                                                    <?php echo $sale['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Customers -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Clientes Recentes</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($recentCustomers)): ?>
                        <p class="text-center text-muted">Nenhum cliente recente.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Telefone</th>
                                        <th>Cadastro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentCustomers as $customer): ?>
                                        <tr>
                                            <td><?php echo clean($customer['nome']); ?></td>
                                            <td><?php echo clean($customer['email']); ?></td>
                                            <td><?php echo clean($customer['telefone']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Sales Chart
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($salesChart['labels']); ?>,
        datasets: [{
            label: 'Vendas (R$)',
            data: <?php echo json_encode($salesChart['data']); ?>,
            fill: false,
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'R$ ' + value.toFixed(2);
                    }
                }
            }
        }
    }
});

// Função para exportar dados
function exportToExcel() {
    // Implementar exportação
    alert('Funcionalidade de exportação em desenvolvimento');
}
</script>

<?php require_once '../src/views/templates/footer.php'; ?>
