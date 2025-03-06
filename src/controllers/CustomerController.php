<?php
class CustomerController {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function index() {
        try {
            // Buscar todos os clientes
            $stmt = $this->pdo->prepare("
                SELECT * FROM customers 
                WHERE status = TRUE 
                ORDER BY nome
            ");
            $stmt->execute();
            $customers = $stmt->fetchAll();

            // Buscar estatísticas dos clientes
            $stats = $this->getCustomerStats();

            require_once '../src/views/customers/index.php';
        } catch (PDOException $e) {
            die('Erro ao carregar clientes: ' . $e->getMessage());
        }
    }

    private function getCustomerStats() {
        try {
            // Total de clientes
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as total 
                FROM customers 
                WHERE status = TRUE
            ");
            $totalCustomers = $stmt->fetch()['total'];

            // Total de vendas por cliente
            $stmt = $this->pdo->query("
                SELECT c.nome, COUNT(s.id) as total_vendas, SUM(s.valor_total) as valor_total
                FROM customers c
                LEFT JOIN sales s ON c.id = s.customer_id AND s.status = 'Concluída'
                WHERE c.status = TRUE
                GROUP BY c.id, c.nome
                ORDER BY valor_total DESC
                LIMIT 5
            ");
            $topCustomers = $stmt->fetchAll();

            // Clientes novos este mês
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as total
                FROM customers
                WHERE status = TRUE 
                AND MONTH(created_at) = MONTH(CURRENT_DATE())
                AND YEAR(created_at) = YEAR(CURRENT_DATE())
            ");
            $newCustomers = $stmt->fetch()['total'];

            return [
                'total_customers' => $totalCustomers,
                'top_customers' => $topCustomers,
                'new_customers' => $newCustomers
            ];
        } catch (PDOException $e) {
            return [
                'total_customers' => 0,
                'top_customers' => [],
                'new_customers' => 0
            ];
        }
    }

    public function create() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método inválido');
            }

            $nome = clean($_POST['nome']);
            $cpf_cnpj = clean($_POST['cpf_cnpj']);
            $email = clean($_POST['email']);
            $telefone = clean($_POST['telefone']);
            $endereco = clean($_POST['endereco']);
            $cidade = clean($_POST['cidade']);
            $estado = clean($_POST['estado']);
            $cep = clean($_POST['cep']);
            $observacoes = clean($_POST['observacoes']);

            // Verificar se CPF/CNPJ já existe
            if (!empty($cpf_cnpj)) {
                $stmt = $this->pdo->prepare("SELECT id FROM customers WHERE cpf_cnpj = ? AND status = TRUE");
                $stmt->execute([$cpf_cnpj]);
                if ($stmt->fetch()) {
                    throw new Exception('CPF/CNPJ já cadastrado');
                }
            }

            // Verificar se email já existe
            if (!empty($email)) {
                $stmt = $this->pdo->prepare("SELECT id FROM customers WHERE email = ? AND status = TRUE");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    throw new Exception('Email já cadastrado');
                }
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO customers (
                    nome, cpf_cnpj, email, telefone, 
                    endereco, cidade, estado, cep, 
                    observacoes, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)
            ");

            $stmt->execute([
                $nome, $cpf_cnpj, $email, $telefone,
                $endereco, $cidade, $estado, $cep,
                $observacoes
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Cliente cadastrado com sucesso!'
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function view($id) {
        try {
            // Buscar dados do cliente
            $stmt = $this->pdo->prepare("
                SELECT * FROM customers 
                WHERE id = ? AND status = TRUE
            ");
            $stmt->execute([$id]);
            $customer = $stmt->fetch();

            if (!$customer) {
                throw new Exception('Cliente não encontrado');
            }

            // Buscar vendas do cliente
            $stmt = $this->pdo->prepare("
                SELECT s.*, u.nome as user_name
                FROM sales s
                JOIN users u ON s.user_id = u.id
                WHERE s.customer_id = ?
                ORDER BY s.data_venda DESC
                LIMIT 10
            ");
            $stmt->execute([$id]);
            $sales = $stmt->fetchAll();

            echo json_encode([
                'status' => 'success',
                'customer' => $customer,
                'sales' => $sales
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function update() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método inválido');
            }

            $id = $_POST['id'];
            $nome = clean($_POST['nome']);
            $cpf_cnpj = clean($_POST['cpf_cnpj']);
            $email = clean($_POST['email']);
            $telefone = clean($_POST['telefone']);
            $endereco = clean($_POST['endereco']);
            $cidade = clean($_POST['cidade']);
            $estado = clean($_POST['estado']);
            $cep = clean($_POST['cep']);
            $observacoes = clean($_POST['observacoes']);

            // Verificar se CPF/CNPJ já existe
            if (!empty($cpf_cnpj)) {
                $stmt = $this->pdo->prepare("
                    SELECT id FROM customers 
                    WHERE cpf_cnpj = ? AND id != ? AND status = TRUE
                ");
                $stmt->execute([$cpf_cnpj, $id]);
                if ($stmt->fetch()) {
                    throw new Exception('CPF/CNPJ já cadastrado');
                }
            }

            // Verificar se email já existe
            if (!empty($email)) {
                $stmt = $this->pdo->prepare("
                    SELECT id FROM customers 
                    WHERE email = ? AND id != ? AND status = TRUE
                ");
                $stmt->execute([$email, $id]);
                if ($stmt->fetch()) {
                    throw new Exception('Email já cadastrado');
                }
            }

            $stmt = $this->pdo->prepare("
                UPDATE customers SET
                    nome = ?,
                    cpf_cnpj = ?,
                    email = ?,
                    telefone = ?,
                    endereco = ?,
                    cidade = ?,
                    estado = ?,
                    cep = ?,
                    observacoes = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $nome, $cpf_cnpj, $email, $telefone,
                $endereco, $cidade, $estado, $cep,
                $observacoes, $id
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Cliente atualizado com sucesso!'
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function delete() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método inválido');
            }

            $id = $_POST['id'];

            // Verificar se existem vendas para este cliente
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM sales WHERE customer_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetch()['total'] > 0) {
                throw new Exception('Não é possível excluir um cliente que possui vendas');
            }

            // Excluir cliente (soft delete)
            $stmt = $this->pdo->prepare("UPDATE customers SET status = FALSE WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Cliente excluído com sucesso!'
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
