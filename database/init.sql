-- Tabela de Usuários
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'Usuário',
    status INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Inserir usuário administrador padrão (senha: admin123)
INSERT OR IGNORE INTO users (nome, email, senha, role) 
VALUES ('Administrador', 'admin@admin.com', '$2y$10$p3jL93bZryq3e4hI46t5HuHu8M/AYLlxb5bq3MG9tspIHg45E/2Ee', 'Administrador');

-- Tabela de Clientes
CREATE TABLE IF NOT EXISTS customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telefone VARCHAR(20),
    cpf_cnpj VARCHAR(20),
    endereco TEXT,
    status INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Produtos
CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    estoque INTEGER NOT NULL DEFAULT 0,
    estoque_minimo INTEGER NOT NULL DEFAULT 5,
    status INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Vendas
CREATE TABLE IF NOT EXISTS sales (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER,
    user_id INTEGER NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    data_venda DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) NOT NULL DEFAULT 'Pendente',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabela de Itens da Venda
CREATE TABLE IF NOT EXISTS sale_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sale_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    quantidade INTEGER NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Tabela de Recebimentos
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sale_id INTEGER NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    forma_pagamento VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pendente',
    data_vencimento DATE NOT NULL,
    data_pagamento DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id)
);

-- Tabela de Configurações
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chave VARCHAR(50) NOT NULL UNIQUE,
    valor TEXT NOT NULL,
    descricao TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Inserir configurações padrão
INSERT OR IGNORE INTO settings (chave, valor, descricao) VALUES
('empresa_nome', 'Minha Empresa', 'Nome da empresa'),
('empresa_cnpj', '00.000.000/0000-00', 'CNPJ da empresa'),
('empresa_endereco', 'Endereço da empresa', 'Endereço completo'),
('empresa_telefone', '(00) 0000-0000', 'Telefone de contato'),
('empresa_email', 'contato@empresa.com', 'Email de contato'),
('impressora_padrao', 'default', 'Nome da impressora padrão'),
('backup_auto', '1', 'Realizar backup automático'),
('backup_tempo', '7', 'Dias entre backups'),
('msg_venda', 'Obrigado pela preferência!', 'Mensagem no final da venda');

-- Inserir alguns dados de exemplo
INSERT OR IGNORE INTO customers (nome, email, telefone, cpf_cnpj, endereco) VALUES
('Cliente Exemplo', 'cliente@exemplo.com', '(11) 99999-9999', '123.456.789-00', 'Rua Exemplo, 123');

INSERT OR IGNORE INTO products (nome, descricao, preco, estoque, estoque_minimo) VALUES
('Produto Exemplo', 'Descrição do produto exemplo', 99.99, 10, 5);

INSERT OR IGNORE INTO sales (customer_id, user_id, valor_total, status) VALUES
(1, 1, 99.99, 'Concluída');

INSERT OR IGNORE INTO sale_items (sale_id, product_id, quantidade, preco_unitario, subtotal) VALUES
(1, 1, 1, 99.99, 99.99);
