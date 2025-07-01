<?php
require_once 'includes/config.php';

// Buscar categorias e itens do cardápio
try {
    $conn = getConnection();
    
    // Buscar categorias ativas
    $stmt = $conn->prepare("SELECT * FROM categories WHERE ativo = 1 ORDER BY nome");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar itens do cardápio ativos
    $stmt = $conn->prepare("
        SELECT mi.*, c.nome as categoria_nome 
        FROM menu_items mi 
        JOIN categories c ON mi.categoria_id = c.id 
        WHERE mi.ativo = 1 
        ORDER BY c.nome, mi.nome
    ");
    $stmt->execute();
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Erro ao carregar cardápio: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo SITE_SLOGAN; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="logo1.png">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <a href="index.php"><img src="logo.png" alt="<?php echo SITE_NAME; ?>" class="logo-img"></a>
                <div class="logo-text">
                    <h1><?php echo SITE_NAME; ?></h1>
                    <p><?php echo SITE_SLOGAN; ?></p>
                </div>
            </div>
            <nav class="nav">
                <a href="#cardapio" class="nav-link">Cardápio</a>
                <a href="#carrinho" class="nav-link" style="display: flex; align-items: center;">
        <span style="display: flex; align-items: center;">
            <!-- Novo Ícone SVG de carrinho Shopee -->
            <svg viewBox="0 0 26.6 25.6" width="22" height="22" class="shopee-svg-icon navbar__link-icon icon-shopping-cart-2" style="margin-right:4px;">
                <title>Shopping Cart Icon</title>
                <polyline fill="none" points="2 1.7 5.5 1.7 9.6 18.3 21.2 18.3 24.6 6.1 7 6.1" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2.5"></polyline>
                <circle cx="10.7" cy="23" r="2.2" stroke="none" fill="currentColor"></circle>
                <circle cx="19.7" cy="23" r="2.2" stroke="none" fill="currentColor"></circle>
            </svg>
            <span id="cart-count" style="font-weight:bold; margin-left:2px;"></span>
        </span>
    </a>
                <a href="admin/" class="nav-link">Admin</a>
            </nav>
        </div>
    </header>

    <main class="main">
        <section id="hero" class="hero">
            <div class="container">
                <h2>Bem-vindo ao CardapioGO</h2>
                <p>Faça seu pedido de forma rápida e prática!</p>
                <a href="#cardapio" class="btn btn-primary">Ver Cardápio</a>
            </div>
        </section>

        <section id="cardapio" class="cardapio">
            <div class="container">
                <h2>Nosso Cardápio</h2>
                
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php else: ?>
                    <!-- Filtros por categoria -->
                    <div class="category-filters">
                        <button class="filter-btn active" data-category="all">Todos</button>
                        <?php foreach ($categories as $category): ?>
                            <button class="filter-btn" data-category="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['nome']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Itens do cardápio -->
                    <div class="menu-grid">
                        <?php foreach ($menu_items as $item): ?>
                            <div class="menu-item" data-category="<?php echo $item['categoria_id']; ?>">
                                <div class="menu-item-image">
                                    <?php if ($item['imagem']): ?>
                                        <img src="images/<?php echo htmlspecialchars($item['imagem']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['nome']); ?>">
                                    <?php else: ?>
                                        <div class="no-image">Sem imagem</div>
                                    <?php endif; ?>
                                </div>
                                <div class="menu-item-content">
                                    <h3><?php echo htmlspecialchars($item['nome']); ?></h3>
                                    <p class="description"><?php echo htmlspecialchars($item['descricao']); ?></p>
                                    <p class="category"><?php echo htmlspecialchars($item['categoria_nome']); ?></p>
                                    <div class="menu-item-footer">
                                        <span class="price"><?php echo formatPrice($item['preco']); ?></span>
                                        <button class="btn btn-secondary add-to-cart" 
                                                data-id="<?php echo $item['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($item['nome']); ?>"
                                                data-price="<?php echo $item['preco']; ?>">
                                            Adicionar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Carrinho de compras -->
        <section id="carrinho" class="carrinho" style="display: none;">
            <div class="container">
                <h2>Seu Carrinho</h2>
                <div id="cart-items"></div>
                <div class="cart-total">
                    <strong>Total: <span id="cart-total">R$ 0,00</span></strong>
                </div>
                <div class="cart-actions">
                    <button class="btn btn-secondary" onclick="clearCart()">Limpar Carrinho</button>
                    <button class="btn btn-primary" onclick="showCheckout()">Finalizar Pedido</button>
                </div>
            </div>
        </section>

        <!-- Checkout -->
        <section id="checkout" class="checkout" style="display: none;">
            <div class="container">
                <h2>Finalizar Pedido</h2>
                <form id="checkout-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cliente_nome">Nome Completo:</label>
                            <input type="text" id="cliente_nome" name="cliente_nome" required>
                        </div>
                        <div class="form-group">
                            <label for="cliente_telefone">Telefone:</label>
                            <input type="tel" id="cliente_telefone" name="cliente_telefone" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="cliente_email">E-mail:</label>
                        <input type="email" id="cliente_email" name="cliente_email" required>
                    </div>

                    <div class="form-group">
                        <label>Tipo de Entrega:</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="tipo_entrega" value="balcao" checked onchange="toggleDeliveryFields()">
                                Retirar no Balcão
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="tipo_entrega" value="entrega" onchange="toggleDeliveryFields()">
                                Entrega em Casa
                            </label>
                        </div>
                    </div>

                    <div id="delivery-fields" style="display: none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="endereco_entrega">Endereço:</label>
                                <input type="text" id="endereco_entrega" name="endereco_entrega">
                            </div>
                            <div class="form-group">
                                <label for="endereco_numero">Número:</label>
                                <input type="text" id="endereco_numero" name="endereco_numero">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="endereco_complemento">Complemento:</label>
                                <input type="text" id="endereco_complemento" name="endereco_complemento">
                            </div>
                            <div class="form-group">
                                <label for="endereco_bairro">Bairro:</label>
                                <input type="text" id="endereco_bairro" name="endereco_bairro">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="endereco_cidade">Cidade:</label>
                                <input type="text" id="endereco_cidade" name="endereco_cidade">
                            </div>
                            <div class="form-group">
                                <label for="endereco_cep">CEP:</label>
                                <input type="text" id="endereco_cep" name="endereco_cep" placeholder="00000-000">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Forma de Pagamento:</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="forma_pagamento" value="dinheiro" checked onchange="togglePaymentFields()">
                                Dinheiro
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="forma_pagamento" value="cartao" onchange="togglePaymentFields()">
                                Cartão (na entrega)
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="forma_pagamento" value="pix" onchange="togglePaymentFields()">
                                PIX
                            </label>
                        </div>
                    </div>

                    <div id="money-fields" style="display: block;">
                        <div class="form-group">
                            <label for="troco_para">Troco para:</label>
                            <input type="number" id="troco_para" name="troco_para" step="0.01" placeholder="0.00">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="cupom">Cupom de Desconto:</label>
                        <div class="coupon-input">
                            <input type="text" id="cupom" name="cupom" placeholder="Digite o código do cupom">
                            <button type="button" class="btn btn-secondary" onclick="applyCoupon()">Aplicar</button>
                        </div>
                        <div id="coupon-message" class="coupon-message"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="observacoes">Observações:</label>
                        <textarea id="observacoes" name="observacoes" rows="2" placeholder="Observações adicionais sobre o pedido"></textarea>
                    </div>

                    <div class="checkout-summary">
                        <div class="summary-line">
                            <span>Subtotal:</span>
                            <span id="checkout-subtotal">R$ 0,00</span>
                        </div>
                        <div class="summary-line" id="coupon-discount-line" style="display: none;">
                            <span>Desconto:</span>
                            <span id="checkout-discount">- R$ 0,00</span>
                        </div>
                        <div class="summary-line" id="delivery-fee-line" style="display: none;">
                            <span>Taxa de Entrega:</span>
                            <span id="checkout-delivery">R$ 5,00</span>
                        </div>
                        <div class="summary-line total-line">
                            <span><strong>Total Final:</strong></span>
                            <span id="checkout-total"><strong>R$ 0,00</strong></span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="backToCart()">Voltar</button>
                        <button type="submit" class="btn btn-primary">Confirmar Pedido</button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 <?php echo SITE_NAME; ?>. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="js/script.js"></script>

    <!-- Modal PIX -->
    <div id="pix-modal" class="qr-modal">
        <div class="qr-modal-content">
            <h3>Pagamento PIX</h3>
            <div id="qr-code-container" class="qr-code-container">
                <img id="qr-code-image" src="" alt="QR Code PIX" style="display: none;">
                <div id="qr-loading">Gerando QR Code...</div>
            </div>
            
            <div class="payment-instructions">
                <h4>Como pagar:</h4>
                <ol>
                    <li>Abra o app do seu banco</li>
                    <li>Escolha a opção PIX</li>
                    <li>Escaneie o QR Code acima</li>
                    <li>Confirme o pagamento</li>
                </ol>
            </div>
            
            <div id="payment-status" class="payment-status pending">
                <strong>Aguardando pagamento...</strong>
                <p>Pedido #<span id="order-number"></span></p>
                <p>Valor: R$ <span id="payment-amount"></span></p>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closePixModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="checkPaymentStatus()">Verificar Pagamento</button>
            </div>
        </div>
    </div>

    <!-- Modal Recibo -->
    <div id="receipt-modal" class="qr-modal">
        <div class="qr-modal-content">
            <h3>Recibo Digital</h3>
            <div id="receipt-content" class="receipt-content">
                <!-- Conteúdo do recibo será inserido aqui -->
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="printReceipt()">Imprimir</button>
                <button type="button" class="btn btn-primary" onclick="closeReceiptModal()">Fechar</button>
            </div>
        </div>
    </div>
</body>
</html>


        <!-- Script para atualizar contagem do carrinho -->
<script>
function updateCartCount() {
    // Supondo que o carrinho está salvo no localStorage como array de objetos
    let cart = JSON.parse(localStorage.getItem('cart') || '[]');
    let count = 0;
    cart.forEach(item => {
        count += item.quantity || 1; // ou ajuste conforme sua estrutura
    });
    const cartCount = document.getElementById('cart-count');
    if (count > 0) {
        cartCount.textContent = count;
    } else {
        cartCount.textContent = '';
    }
}

// Atualiza ao carregar a página
document.addEventListener('DOMContentLoaded', updateCartCount);
// Atualiza quando o carrinho é alterado
document.addEventListener('cartUpdated', updateCartCount);

// Chame updateCartCount() sempre que adicionar/remover itens do carrinho
</script>

