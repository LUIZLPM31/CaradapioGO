-- Banco de dados para o sistema CardapioGO
-- Criação das tabelas conforme especificação

CREATE DATABASE IF NOT EXISTS cardapiogo;
USE cardapiogo;

-- Tabela de usuários administradores
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de categorias do cardápio
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de itens do cardápio
CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    imagem VARCHAR(255),
    preco DECIMAL(10,2) NOT NULL,
    categoria_id INT,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categories(id)
);

-- Tabela de pedidos
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_nome VARCHAR(100) NOT NULL,
    cliente_telefone VARCHAR(20),
    cliente_email VARCHAR(100),
    endereco_entrega TEXT,
    endereco_numero VARCHAR(10),
    endereco_complemento VARCHAR(100),
    endereco_bairro VARCHAR(100),
    endereco_cidade VARCHAR(100),
    endereco_cep VARCHAR(10),
    tipo_entrega ENUM('balcao', 'entrega') DEFAULT 'balcao',
    forma_pagamento ENUM('dinheiro', 'cartao', 'pix') DEFAULT 'dinheiro',
    troco_para DECIMAL(10,2) NULL,
    cupom_codigo VARCHAR(50) NULL,
    cupom_desconto DECIMAL(10,2) DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL,
    taxa_entrega DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pendente', 'confirmado', 'preparando', 'pronto', 'entregue', 'cancelado') DEFAULT 'pendente',
    status_pagamento ENUM('pendente', 'confirmado', 'cancelado') DEFAULT 'pendente',
    qr_code_pix TEXT NULL,
    observacoes TEXT,
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_confirmacao TIMESTAMP NULL
);

-- Tabela de itens do pedido
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    preco_unitario DECIMAL(10,2) NOT NULL,
    observacoes TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu_items(id)
);

-- Tabela de cupons de desconto
CREATE TABLE cupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    descricao VARCHAR(200),
    tipo ENUM('percentual', 'valor_fixo') NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_inicio DATE,
    data_fim DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserção de dados iniciais

-- Usuário administrador padrão (senha: admin123)
INSERT INTO users (nome, email, senha_hash) VALUES 
('Administrador', 'admin@cardapiogo.com', 'admin123');
-- Categorias iniciais
INSERT INTO categories (nome, descricao) VALUES 
('Hambúrgueres', 'Deliciosos hambúrgueres artesanais'),
('Bebidas', 'Refrigerantes, sucos e bebidas geladas'),
('Porções', 'Porções para compartilhar'),
('Sobremesas', 'Doces e sobremesas especiais'),
('Combos', 'Combos promocionais');

-- Itens do cardápio de exemplo
INSERT INTO menu_items (nome, descricao, preco, categoria_id, imagem) VALUES 
('X-Burger Clássico', 'Hambúrguer artesanal com carne bovina, queijo, alface, tomate e molho especial', 18.90, 1, 'X-Burger Clássico.jpg'),
('X-Bacon', 'Hambúrguer com carne bovina, bacon crocante, queijo e molho barbecue', 22.90, 1, 'x-bacon.jpg'),
('X-Frango', 'Hambúrguer de frango grelhado com queijo, alface e maionese temperada', 19.90, 1, 'frango.jpg'),
('Coca-Cola 350ml', 'Refrigerante Coca-Cola gelado', 5.50, 2, 'coca.jpg'),
('Suco de Laranja', 'Suco natural de laranja 300ml', 7.90, 2, 'suco.jpg'),
('Batata Frita Grande', 'Porção de batata frita crocante para 2-3 pessoas', 15.90, 3, 'batata-frita.jpg'),
('Milk Shake de Chocolate', 'Cremoso milk shake de chocolate com chantilly', 12.90, 4, 'milk.jpg'),
('Combo X-Burger', 'X-Burger Clássico + Batata Média + Refrigerante', 28.90, 5, 'combo.jpg'),
('X-Tudo', 'Hambúrguer completo com carne, queijo, bacon, ovo, alface, tomate e molho especial', 25.90, 1, 'x-tudo.jpg'),
('X-Salada', 'Hambúrguer com carne, queijo, alface, tomate e maionese', 19.90, 1, 'x-salada.jpg'),
('X-Calabresa', 'Hambúrguer com carne, queijo e calabresa fatiada', 21.90, 1, 'x-calabresa.jpg');

-- Cupons de exemplo
INSERT INTO cupons (codigo, descricao, tipo, valor, data_inicio, data_fim) VALUES 
('BEMVINDO10', 'Desconto de 10% para novos clientes', 'percentual', 10.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY)),
('FRETE5', 'R$ 5,00 de desconto na entrega', 'valor_fixo', 5.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY));
