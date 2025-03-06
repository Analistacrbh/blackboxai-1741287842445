<?php
class SalesController {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function index() {
        try {
            // Buscar todas as vendas
            $stmt = $this->pdo->prepare("
                SELECT s.*, c.nome as customer_name, u.nome as user_name
                FROM sales s
                JOIN customers c ON s.customer_id = c.id
                JOIN users u ON s.user_id = u.id
                ORDER BY s.data_venda DESC
            ");
            $stmt->execute();
            $sales = $stmt->fetchAll();

            // Buscar clientes para o select do modal
            $stmt = $this->pdo->prepare("
                SELECT id, nome, cpf_cnpj 
                FROM customers 
                WHERE status = TRUE 
                ORDER BY nome
            ");
            $stmt->execute();
            $customers = $stmt->fetchAll();

            // Buscar produtos para o select do modal
            $stmt = $this->pdo->prepare("
                SELECT id, codigo, nome, preco_venda, estoque 
                FROM products 
                WHERE status = TRUE 
                ORDER BY nome
            ");
            $stmt->execute();
            $products = $stmt->fetchAll();

            require_once '../src/views/sales/index.php';
        } catch (PDOException $e) {
            die('Erro ao carregar vendas: ' . $e->getMessage());
        }
    }

    public function create() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método inválido');
            }

            $this->pdo->beginTransaction();

            // Dados da venda
            $customer_id = $_POST['customer_id'];
            $data_venda = $_POST['data_venda'];
            $forma_pagamento = $_POST['forma_pagamento'];
            $observacoes = $_POST['observacoes'];
            $produtos = $_POST['produtos'];
            $quantidades = $_POST['quantidades'];

            // Calcular valor total
            $valor_total = 0;
            foreach ($produtos as $key => $product_id) {
                $stmt = $this->pdo->prepare("SELECT preco_venda FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                $valor_total += $product['preco_venda'] * $quantidades[$key];
            }

            // Inserir venda
            $stmt = $this->pdo->prepare("
                INSERT INTO sales (
                    customer_id, user_id, data_venda, valor_total, 
                    forma_pagamento, status, observacoes
                ) VALUES (?, ?, ?, ?, ?, 'Pendente', ?)
            ");
            $stmt->execute([
                $customer_id,
                $_SESSION['user_id'],
                $data_venda,
                $valor_total,
                $forma_pagamento,
                $observacoes
            ]);
            $sale_id = $this->pdo->lastInsertId();

            // Inserir itens da venda
            foreach ($produtos as $key => $product_id) {
                $stmt = $this->pdo->prepare("SELECT preco_venda FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();

                $quantidade = $quantidades[$key];
                $preco_unitario = $product['preco_venda'];
                $subtotal = $preco_unitario * $quantidade;

                // Inserir item
                $stmt = $this->pdo->prepare("
                    INSERT INTO sale_items (
                        sale_id, product_id, quantidade, 
                        preco_unitario, subtotal
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $sale_id,
                    $product_id,
                    $quantidade,
                    $preco_unitario,
                    $subtotal
                ]);

                // Atualizar estoque
                $stmt = $this->pdo->prepare("
                    UPDATE products 
                    SET estoque = estoque - ? 
                    WHERE id = ?
                ");
                $stmt->execute([$quantidade, $product_id]);
            }

            $this->pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Venda registrada com sucesso!']);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function view($id) {
        try {
            // Buscar dados da venda
            $stmt = $this->pdo->prepare("
                SELECT s.*, c.nome as customer_name, u.nome as user_name
                FROM sales s
                JOIN customers c ON s.customer_id = c.id
                JOIN users u ON s.user_id = u.id
                WHERE s.id = ?
            ");
            $stmt->execute([$id]);
            $sale = $stmt->fetch();

            if (!$sale) {
                throw new Exception('Venda não encontrada');
            }

            // Buscar itens da venda
            $stmt = $this->pdo->prepare("
                SELECT si.*, p.nome as product_name
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
                WHERE si.sale_id = ?
            ");
            $stmt->execute([$id]);
            $items = $stmt->fetchAll();

            // Buscar pagamentos
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM payments
                WHERE sale_id = ?
                ORDER BY data_pagamento
            ");
            $stmt->execute([$id]);
            $payments = $stmt->fetchAll();

            echo json_encode([
                'status' => 'success',
                'sale' => $sale,
                'items' => $items,
                'payments' => $payments
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function update() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método inválido');
            }

            $sale_id = $_POST['id'];
            $status = $_POST['status'];
            $observacoes = $_POST['observacoes'];

            $stmt = $this->pdo->prepare("
                UPDATE sales 
                SET status = ?, observacoes = ?
                WHERE id = ?
            ");
            $stmt->execute([$status, $observacoes, $sale_id]);

            echo json_encode(['status' => 'success', 'message' => 'Venda atualizada com sucesso!']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function delete() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método inválido');
            }

            $sale_id = $_POST['id'];

            $this->pdo->beginTransaction();

            // Verificar se a venda existe
            $stmt = $this->pdo->prepare("SELECT status FROM sales WHERE id = ?");
            $stmt->execute([$sale_id]);
            $sale = $stmt->fetch();

            if (!$sale) {
                throw new Exception('Venda não encontrada');
            }

            // Se a venda estiver concluída, restaurar o estoque
            if ($sale['status'] == 'Concluída') {
                $stmt = $this->pdo->prepare("
                    SELECT product_id, quantidade
                    FROM sale_items
                    WHERE sale_id = ?
                ");
                $stmt->execute([$sale_id]);
                $items = $stmt->fetchAll();

                foreach ($items as $item) {
                    $stmt = $this->pdo->prepare("
                        UPDATE products 
                        SET estoque = estoque + ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$item['quantidade'], $item['product_id']]);
                }
            }

            // Excluir pagamentos
            $stmt = $this->pdo->prepare("DELETE FROM payments WHERE sale_id = ?");
            $stmt->execute([$sale_id]);

            // Excluir itens da venda
            $stmt = $this->pdo->prepare("DELETE FROM sale_items WHERE sale_id = ?");
            $stmt->execute([$sale_id]);

            // Excluir venda
            $stmt = $this->pdo->prepare("DELETE FROM sales WHERE id = ?");
            $stmt->execute([$sale_id]);

            $this->pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Venda excluída com sucesso!']);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function addPayment() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método inválido');
            }

            $sale_id = $_POST['sale_id'];
            $valor = $_POST['valor'];
            $forma_pagamento = $_POST['forma_pagamento'];
            $data_pagamento = $_POST['data_pagamento'];
            $observacoes = $_POST['observacoes'];

            // Inserir pagamento
            $stmt = $this->pdo->prepare("
                INSERT INTO payments (
                    sale_id, valor, forma_pagamento,
                    data_pagamento, status, observacoes
                ) VALUES (?, ?, ?, ?, 'Confirmado', ?)
            ");
            $stmt->execute([
                $sale_id,
                $valor,
                $forma_pagamento,
                $data_pagamento,
                $observacoes
            ]);

            // Verificar se a venda foi totalmente paga
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.valor_total,
                    COALESCE(SUM(p.valor), 0) as total_pago
                FROM sales s
                LEFT JOIN payments p ON s.id = p.sale_id AND p.status = 'Confirmado'
                WHERE s.id = ?
                GROUP BY s.id, s.valor_total
            ");
            $stmt->execute([$sale_id]);
            $result = $stmt->fetch();

            if ($result['total_pago'] >= $result['valor_total']) {
                $stmt = $this->pdo->prepare("
                    UPDATE sales 
                    SET status = 'Concluída'
                    WHERE id = ?
                ");
                $stmt->execute([$sale_id]);
            }

            echo json_encode(['status' => 'success', 'message' => 'Pagamento registrado com sucesso!']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
