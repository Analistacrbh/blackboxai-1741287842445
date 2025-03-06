<?php
class DashboardController {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function index() {
        try {
            // Totais
            $totals = $this->getTotals();
            
            // Vendas recentes
            $recentSales = $this->getRecentSales();
            
            // Clientes recentes
            $recentCustomers = $this->getRecentCustomers();
            
            // Produtos com estoque baixo
            $lowStock = $this->getLowStock();
            
            // Gráfico de vendas dos últimos 7 dias
            $salesChart = $this->getSalesChartData();

            require_once '../src/views/dashboard/index.php';
        } catch (PDOException $e) {
            die('Erro ao carregar dashboard: ' . $e->getMessage());
        }
    }

    private function getTotals() {
        // Total de vendas do dia
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(valor_total), 0) as total
            FROM sales 
            WHERE DATE(data_venda) = CURDATE() 
            AND status = 'Concluída'
        ");
        $stmt->execute();
        $dailySales = $stmt->fetch()['total'];

        // Total de vendas do mês
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(valor_total), 0) as total
            FROM sales 
            WHERE MONTH(data_venda) = MONTH(CURRENT_DATE()) 
            AND YEAR(data_venda) = YEAR(CURRENT_DATE())
            AND status = 'Concluída'
        ");
        $stmt->execute();
        $monthlySales = $stmt->fetch()['total'];

        // Total de clientes
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM customers WHERE status = TRUE");
        $totalCustomers = $stmt->fetch()['total'];

        // Total de produtos
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM products WHERE status = TRUE");
        $totalProducts = $stmt->fetch()['total'];

        return [
            'daily_sales' => $dailySales,
            'monthly_sales' => $monthlySales,
            'total_customers' => $totalCustomers,
            'total_products' => $totalProducts
        ];
    }

    private function getRecentSales() {
        $stmt = $this->pdo->prepare("
            SELECT s.*, c.nome as customer_name, u.nome as user_name
            FROM sales s
            JOIN customers c ON s.customer_id = c.id
            JOIN users u ON s.user_id = u.id
            WHERE s.status != 'Cancelada'
            ORDER BY s.data_venda DESC
            LIMIT 5
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getRecentCustomers() {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM customers
            WHERE status = TRUE
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getLowStock() {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM products
            WHERE status = TRUE
            AND estoque <= estoque_minimo
            ORDER BY estoque ASC
            LIMIT 5
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getSalesChartData() {
        $data = [];
        $labels = [];
        
        // Últimos 7 dias
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('d/m', strtotime($date));

            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(valor_total), 0) as total
                FROM sales
                WHERE DATE(data_venda) = ?
                AND status = 'Concluída'
            ");
            $stmt->execute([$date]);
            $data[] = floatval($stmt->fetch()['total']);
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
}
