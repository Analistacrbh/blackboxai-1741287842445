<?php
class ProductController {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function index() {
        try {
            // Buscar todos os produtos
            $stmt = $this->pdo->prepare("
                SELECT * FROM products 
                WHERE status = TRUE 
                ORDER BY nome
            ");
            $stmt->execute();
            $products = $stmt->fetchAll();

            // Buscar estatísticas dos produtos
            $stats = $this->getProductStats();

            require_once '../src/views/products/index.php';
        } catch (PDOException $e) {
            die('Erro ao carregar produtos: ' . $e->getMessage());
        }
    }

    private function getProductStats() {
        try {
            // Total de produtos
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as total 
                FROM products 
                WHERE status = TRUE
            ");
            $totalProducts = $stmt->fetch()['total'];

            // Valor total em estoque
            $stmt = $this->pdo->query("
                SELECT COALESCE(SUM(estoque * preco_custo), 0) as valor_estoque
                FROM products
                WHERE status = TRUE
            ");
            $stockValue = $stmt->fetch()['valor_estoque'];

            // Produtos com estoque baixo
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as total
                FROM products
                WHERE status = TRUE 
                AND estoque <= estoque_minimo
            ");
            $lowStock = $stmt->fetch()['total'];

            // Produtos mais vendidos
            $stmt = $this->pdo->query("
                SELECT p.nome, p.codigo, 
                       SUM(si.quantidade) as total_vendido,
                       SUM(si.subtotal) as valor_total
                FROM products p
                JOIN sale_items si ON p.id = si.product_id
                JOIN sales s ON si.sale_id = s.id
                WHERE s.status = 'Concluída'
                AND p.status = TRUE
                GROUP BY p.id, p.nome, p.codigo
                ORDER BY total_vendido DESC
                LIMIT 5
            ");
            $topProducts = $stmt->fetchAll();

            return [
                'total_products' => $totalProducts,
                'stock_value' => $stockValue,
                'low_stock' => $lowStock,
                'top_products' => $topProducts
            ];
        } catch (PDOException $e) {
            return [
                'total_products' => 0,
                'stock_value' => 0,
                'low_stock' => 0,
                'top_products' => []
            ];
        }
    }

    public function create() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método inválido');
            }

            $codigo = clean($_POST['codigo']);
            $nome = clean($_POST['nome']);
            $descricao = clean($_POST['descricao']);
            $preco_custo = str_replace(',', '.', $_POST['preco_custo']);
            $preco_venda = str_replace(',', '.', $_POST['preco_venda']);
            $estoque = (int)$_POST['estoque'];
            $estoque_minimo = (int)$_POST['estoque_minimo'];
            $categoria = clean($_POST['categoria']);

            // Verificar se código já existe
            if (!empty($codigo)) {
                $stmt = $this->pdo->prepare("SELECT id FROM products WHERE codigo = ? AND status = TRUE");
                $stmt->execute([$codigo]);
                if ($stmt->fetch()) {
                    throw new Exception('Código já cadastrado');
                }
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO products (
                    codigo, nome, descricao, preco_custo,
                    preco_venda, estoque, estoque_minimo,
                    categoria, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)
            ");

            $stmt->execute([
                $codigo, $nome, $descricao, $preco_custo,
                $preco_venda, $estoque, $estoque_minimo,
                $categoria
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Produto cadastrado com sucesso!'
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
            // Buscar dados do produto
            $stmt = $this->pdo->prepare("
                SELECT * FROM products 
                WHERE id = ? AND status = TRUE
            ");
            $stmt->execute([$id]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new Exception('Produto não encontrado');
            }

            // Buscar histórico de vendas do produto
            $stmt = $this->pdo->prepare("
                SELECT si.*, s.data_venda, s.status,
                       c.nome as customer_name
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                JOIN customers c ON s.customer_id = c.id
                WHERE si.product_id = ?
                ORDER BY s.data_venda DESC
                LIMIT 10
            ");
            $stmt->execute([$id]);
            $sales = $stmt->fetchAll();

            echo json_encode([
                'status' => 'success',
                'product' => $product,
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
            $codigo = clean($_POST['codigo']);
            $nome = clean($_POST['nome']);
            $descricao = clean($_POST['descricao']);
            $preco_custo = str_replace(',', '.', $_POST['preco_custo']);
            $preco_venda = str_replace(',', '.', $_POST['preco_venda']);
            $estoque = (int)$_POST['estoque'];
            $estoque_minimo = (int)$_POST['estoque_minimo'];
            $categoria = clean($_POST['categoria']);

            // Verificar se código já existe
            if (!empty($codigo)) {
                $stmt = $this->pdo->prepare("
                    SELECT id FROM products 
                    WHERE codigo = ? AND id != ? AND status = TRUE
                ");
                $stmt->execute([$codigo, $id]);
                if ($stmt->fetch()) {
                    throw new Exception('Código já cadastrado');
                }
            }

            $stmt = $this->pdo->prepare("
                UPDATE products SET
                    codigo = ?,
                    nome = ?,
                    descricao = ?,
                    preco_custo = ?,
                    preco_venda = ?,
                    estoque = ?,
                    estoque_minimo = ?,
                    categoria = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $codigo, $nome, $descricao, $preco_custo,
                $preco_venda, $estoque, $estoque_minimo,
                $categoria, $id
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Produto atualizado com sucesso!'
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

            // Verificar se existem vendas para este produto
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total 
                FROM sale_items 
                WHERE product_id = ?
            ");
            $stmt->execute([$id]);
            if ($stmt->fetch()['total'] > 0) {
                throw new Exception('Não é possível excluir um produto que possui vendas');
            }

            // Excluir produto (soft delete)
            $stmt = $this->pdo->prepare("UPDATE products SET status = FALSE WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Produto excluído com sucesso!'
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function updateStock() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método inválido');
            }

            $id = $_POST['id'];
            $quantidade = (int)$_POST['quantidade'];
            $tipo = $_POST['tipo']; // 'entrada' ou 'saida'

            // Buscar produto
            $stmt = $this->pdo->prepare("SELECT estoque FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new Exception('Produto não encontrado');
            }

            // Calcular novo estoque
            $novo_estoque = $tipo === 'entrada' 
                ? $product['estoque'] + $quantidade
                : $product['estoque'] - $quantidade;

            if ($novo_estoque < 0) {
                throw new Exception('Estoque insuficiente');
            }

            // Atualizar estoque
            $stmt = $this->pdo->prepare("UPDATE products SET estoque = ? WHERE id = ?");
            $stmt->execute([$novo_estoque, $id]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Estoque atualizado com sucesso!'
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
