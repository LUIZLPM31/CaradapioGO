// Sistema de Carrinho e Funcionalidades do CardapioGO
let cart = [];
let currentSection = 'hero';
let appliedCoupon = null;
let deliveryFee = 5.00;

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();
    initializeCart();
    initializeNavigation();
    initializeCheckoutForm();
    loadCartFromStorage();
    updateCartCount();
});

// Filtros de categoria
function initializeFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const menuItems = document.querySelectorAll('.menu-item');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remover classe active de todos os botões
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const category = this.dataset.category;
            
            // Filtrar itens
            menuItems.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
}

// Sistema de carrinho
function initializeCart() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const item = {
                id: parseInt(this.dataset.id),
                name: this.dataset.name,
                price: parseFloat(this.dataset.price),
                quantity: 1
            };
            
            addToCart(item);
        });
    });
}

function addToCart(item) {
    const existingItem = cart.find(cartItem => cartItem.id === item.id);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push(item);
    }
    
    updateCartDisplay();
    saveCartToStorage();
    showNotification('Item adicionado ao carrinho!');
}

function removeFromCart(itemId) {
    cart = cart.filter(item => item.id !== itemId);
    updateCartDisplay();
    saveCartToStorage();
}

function updateCartQuantity(itemId, quantity) {
    const item = cart.find(cartItem => cartItem.id === itemId);
    if (item) {
        if (quantity <= 0) {
            removeFromCart(itemId);
        } else {
            item.quantity = quantity;
            updateCartDisplay();
            saveCartToStorage();
        }
    }
}

function clearCart() {
    cart = [];
    appliedCoupon = null;
    updateCartDisplay();
    saveCartToStorage();
    showNotification('Carrinho limpo!');
}

function updateCartDisplay() {
    const cartCount = document.getElementById('cart-count');
    const cartItems = document.getElementById('cart-items');
    const cartTotal = document.getElementById('cart-total');
    
    // Atualizar contador
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartCount.textContent = totalItems;
    
    // Atualizar lista de itens
    if (cartItems) {
        cartItems.innerHTML = '';
        
        if (cart.length === 0) {
            cartItems.innerHTML = '<p class="empty-cart">Seu carrinho está vazio</p>';
        } else {
            cart.forEach(item => {
                const itemElement = document.createElement('div');
                itemElement.className = 'cart-item';
                itemElement.innerHTML = `
                    <div class="cart-item-info">
                        <h4>${item.name}</h4>
                        <p class="cart-item-price">${formatPrice(item.price)}</p>
                    </div>
                    <div class="cart-item-controls">
                        <button onclick="updateCartQuantity(${item.id}, ${item.quantity - 1})">-</button>
                        <span class="quantity">${item.quantity}</span>
                        <button onclick="updateCartQuantity(${item.id}, ${item.quantity + 1})">+</button>
                        <button onclick="removeFromCart(${item.id})" class="remove-btn">×</button>
                    </div>
                `;
                cartItems.appendChild(itemElement);
            });
        }
    }
    
    // Atualizar total
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const formattedTotal = formatPrice(subtotal);
    
    if (cartTotal) cartTotal.textContent = formattedTotal;
    
    // Atualizar checkout se estiver visível
    updateCheckoutSummary();
}

function updateCheckoutSummary() {
    const subtotalElement = document.getElementById('checkout-subtotal');
    const discountElement = document.getElementById('checkout-discount');
    const deliveryElement = document.getElementById('checkout-delivery');
    const totalElement = document.getElementById('checkout-total');
    const discountLine = document.getElementById('coupon-discount-line');
    const deliveryLine = document.getElementById('delivery-fee-line');
    
    if (!subtotalElement) return;
    
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    let discount = 0;
    let delivery = 0;
    
    // Calcular desconto do cupom
    if (appliedCoupon) {
        if (appliedCoupon.tipo === 'percentual') {
            discount = subtotal * (appliedCoupon.valor / 100);
        } else {
            discount = appliedCoupon.valor;
        }
    }
    
    // Calcular taxa de entrega
    const tipoEntrega = document.querySelector('input[name="tipo_entrega"]:checked');
    if (tipoEntrega && tipoEntrega.value === 'entrega') {
        delivery = deliveryFee;
    }
    
    const total = Math.max(0, subtotal - discount + delivery);
    
    // Atualizar elementos
    subtotalElement.textContent = formatPrice(subtotal);
    discountElement.textContent = '- ' + formatPrice(discount);
    deliveryElement.textContent = formatPrice(delivery);
    totalElement.innerHTML = '<strong>' + formatPrice(total) + '</strong>';
    
    // Mostrar/esconder linhas
    discountLine.style.display = discount > 0 ? 'flex' : 'none';
    deliveryLine.style.display = delivery > 0 ? 'flex' : 'none';
}

// Navegação
function initializeNavigation() {
    const navLinks = document.querySelectorAll('.nav-link[href^="#"]');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            showSection(targetId);
        });
    });
}

function showSection(sectionId) {
    // Esconder todas as seções
    const sections = document.querySelectorAll('main > section');
    sections.forEach(section => {
        section.style.display = 'none';
    });
    
    // Mostrar seção selecionada
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.style.display = 'block';
        currentSection = sectionId;
        
        // Scroll para o topo
        window.scrollTo(0, 0);
    }
}

function showCheckout() {
    if (cart.length === 0) {
        showNotification('Adicione itens ao carrinho primeiro!');
        return;
    }
    
    showSection('checkout');
    updateCheckoutSummary();
}

function backToCart() {
    showSection('carrinho');
}

// Inicializar formulário de checkout
function initializeCheckoutForm() {
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            e.preventDefault();
            processOrder();
        });
    }
}

// Funções para alternar campos do formulário
function toggleDeliveryFields() {
    const tipoEntrega = document.querySelector('input[name="tipo_entrega"]:checked');
    const deliveryFields = document.getElementById('delivery-fields');
    
    if (tipoEntrega && tipoEntrega.value === 'entrega') {
        deliveryFields.style.display = 'block';
        // Tornar campos obrigatórios
        document.getElementById('endereco_entrega').required = true;
        document.getElementById('endereco_numero').required = true;
        document.getElementById('endereco_bairro').required = true;
    } else {
        deliveryFields.style.display = 'none';
        // Remover obrigatoriedade
        document.getElementById('endereco_entrega').required = false;
        document.getElementById('endereco_numero').required = false;
        document.getElementById('endereco_bairro').required = false;
    }
    
    updateCheckoutSummary();
}

function togglePaymentFields() {
    const formaPagamento = document.querySelector('input[name="forma_pagamento"]:checked');
    const moneyFields = document.getElementById('money-fields');
    
    if (formaPagamento && formaPagamento.value === 'dinheiro') {
        moneyFields.style.display = 'block';
    } else {
        moneyFields.style.display = 'none';
    }
}

// Validação e aplicação de cupom
function applyCoupon() {
    const cupomInput = document.getElementById('cupom');
    const codigo = cupomInput.value.trim().toUpperCase();
    const messageElement = document.getElementById('coupon-message');
    
    if (!codigo) {
        showCouponMessage('Digite um código de cupom', 'error');
        return;
    }
    
    // Fazer requisição para validar cupom
    fetch('process_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'validate_coupon',
            codigo: codigo
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.valid) {
            appliedCoupon = {
                codigo: codigo,
                tipo: data.tipo,
                valor: data.valor
            };
            showCouponMessage(data.message, 'success');
            cupomInput.disabled = true;
            updateCheckoutSummary();
        } else {
            showCouponMessage(data.message, 'error');
            appliedCoupon = null;
            updateCheckoutSummary();
        }
    })
    .catch(error => {
        showCouponMessage('Erro ao validar cupom', 'error');
        console.error('Error:', error);
    });
}

function showCouponMessage(message, type) {
    const messageElement = document.getElementById('coupon-message');
    messageElement.textContent = message;
    messageElement.className = 'coupon-message ' + type;
}

// Processar pedido
function processOrder() {
    if (cart.length === 0) {
        showNotification('Carrinho vazio!');
        return;
    }
    
    const formData = new FormData(document.getElementById('checkout-form'));
    const tipoEntrega = formData.get('tipo_entrega');
    const formaPagamento = formData.get('forma_pagamento');
    
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    let cupomDesconto = 0;
    let taxaEntrega = 0;
    
    // Calcular desconto
    if (appliedCoupon) {
        if (appliedCoupon.tipo === 'percentual') {
            cupomDesconto = subtotal * (appliedCoupon.valor / 100);
        } else {
            cupomDesconto = appliedCoupon.valor;
        }
    }
    
    // Calcular taxa de entrega
    if (tipoEntrega === 'entrega') {
        taxaEntrega = deliveryFee;
    }
    
    const total = Math.max(0, subtotal - cupomDesconto + taxaEntrega);
    
    const orderData = {
        cliente_nome: formData.get('cliente_nome'),
        cliente_telefone: formData.get('cliente_telefone'),
        cliente_email: formData.get('cliente_email'),
        tipo_entrega: tipoEntrega,
        endereco_entrega: formData.get('endereco_entrega'),
        endereco_numero: formData.get('endereco_numero'),
        endereco_complemento: formData.get('endereco_complemento'),
        endereco_bairro: formData.get('endereco_bairro'),
        endereco_cidade: formData.get('endereco_cidade'),
        endereco_cep: formData.get('endereco_cep'),
        forma_pagamento: formaPagamento,
        troco_para: formData.get('troco_para'),
        cupom_codigo: appliedCoupon ? appliedCoupon.codigo : null,
        cupom_desconto: cupomDesconto,
        subtotal: subtotal,
        taxa_entrega: taxaEntrega,
        total: total,
        observacoes: formData.get('observacoes'),
        items: cart
    };
    
    // Mostrar loading
    const submitBtn = document.querySelector('#checkout-form button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Processando...';
    submitBtn.disabled = true;
    
    fetch('process_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.forma_pagamento === 'pix') {
                showPixPayment(data.order_id);
            } else {
                showOrderSuccess(data.order_id);
            }
        } else {
            showNotification('Erro: ' + data.message);
        }
    })
    .catch(error => {
        showNotification('Erro ao processar pedido. Tente novamente.');
        console.error('Error:', error);
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

function showOrderSuccess(orderId) {
    showNotification('Pedido realizado com sucesso! Número: #' + orderId);
    clearCart();
    showSection('hero');
    document.getElementById('checkout-form').reset();
    appliedCoupon = null;
}

function showPixPayment(orderId) {
    // Aqui seria implementado o modal do PIX
    // Por enquanto, apenas simular
    showNotification('Pedido #' + orderId + ' criado! Gerando QR Code PIX...');
    setTimeout(() => {
        showOrderSuccess(orderId);
    }, 3000);
}

// Utilitários
function formatPrice(price) {
    return 'R$ ' + price.toFixed(2).replace('.', ',');
}

function showNotification(message) {
    // Criar elemento de notificação
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    
    // Adicionar ao body
    document.body.appendChild(notification);
    
    // Mostrar com animação
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Remover após 3 segundos
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Persistência do carrinho
function saveCartToStorage() {
    localStorage.setItem('cardapiogo_cart', JSON.stringify(cart));
}

function loadCartFromStorage() {
    const savedCart = localStorage.getItem('cardapiogo_cart');
    if (savedCart) {
        cart = JSON.parse(savedCart);
        updateCartDisplay();
    }
}


// Variáveis globais para PIX
let currentOrderId = null;
let paymentCheckInterval = null;

// Função para mostrar pagamento PIX
function showPixPayment(orderId) {
    currentOrderId = orderId;
    const modal = document.getElementById('pix-modal');
    const orderNumber = document.getElementById('order-number');
    const qrLoading = document.getElementById('qr-loading');
    const qrImage = document.getElementById('qr-code-image');
    
    // Mostrar modal
    modal.style.display = 'block';
    orderNumber.textContent = String(orderId).padStart(6, '0');
    
    // Resetar estado
    qrLoading.style.display = 'block';
    qrImage.style.display = 'none';
    
    // Gerar QR Code
    generateQRCode(orderId);
    
    // Iniciar verificação automática de pagamento
    startPaymentCheck(orderId);
}

function generateQRCode(orderId) {
    fetch('generate_qr.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: orderId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const qrLoading = document.getElementById('qr-loading');
            const qrImage = document.getElementById('qr-code-image');
            const paymentAmount = document.getElementById('payment-amount');
            
            qrLoading.style.display = 'none';
            qrImage.src = data.qr_code;
            qrImage.style.display = 'block';
            paymentAmount.textContent = parseFloat(data.total).toFixed(2).replace('.', ',');
        } else {
            showNotification('Erro ao gerar QR Code: ' + data.error);
            closePixModal();
        }
    })
    .catch(error => {
        showNotification('Erro ao gerar QR Code');
        console.error('Error:', error);
        closePixModal();
    });
}

function startPaymentCheck(orderId) {
    // Verificar a cada 5 segundos
    paymentCheckInterval = setInterval(() => {
        checkPaymentStatus();
    }, 5000);
}

function checkPaymentStatus() {
    if (!currentOrderId) return;
    
    fetch('confirm_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: currentOrderId,
            action: 'check_status'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.status_pagamento === 'confirmado') {
            // Pagamento confirmado
            clearInterval(paymentCheckInterval);
            showPaymentConfirmed();
            
            // Simular confirmação automática após 2 segundos
            setTimeout(() => {
                confirmPayment();
            }, 2000);
        }
    })
    .catch(error => {
        console.error('Erro ao verificar status:', error);
    });
}

function showPaymentConfirmed() {
    const paymentStatus = document.getElementById('payment-status');
    paymentStatus.className = 'payment-status confirmed';
    paymentStatus.innerHTML = '<strong>Pagamento Confirmado!</strong><p>Processando pedido...</p>';
}

function confirmPayment() {
    fetch('confirm_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: currentOrderId,
            action: 'confirm'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closePixModal();
            showReceipt(data.recibo);
            clearCart();
            showSection('hero');
            document.getElementById('checkout-form').reset();
            appliedCoupon = null;
        } else {
            showNotification('Erro ao confirmar pagamento: ' + data.error);
        }
    })
    .catch(error => {
        showNotification('Erro ao confirmar pagamento');
        console.error('Error:', error);
    });
}

function closePixModal() {
    const modal = document.getElementById('pix-modal');
    modal.style.display = 'none';
    
    if (paymentCheckInterval) {
        clearInterval(paymentCheckInterval);
        paymentCheckInterval = null;
    }
    
    currentOrderId = null;
}

function showReceipt(recibo) {
    const modal = document.getElementById('receipt-modal');
    const content = document.getElementById('receipt-content');
    
    content.innerHTML = generateReceiptHTML(recibo);
    modal.style.display = 'block';
}

function generateReceiptHTML(recibo) {
    return `
        <div class="receipt-header">
            <h2>CardapioGO</h2>
            <p>Seu cardápio digital em tempo real</p>
        </div>
        
        <div class="receipt-section">
            <h4>Pedido #${recibo.numero_pedido}</h4>
            <div class="receipt-line">
                <span>Data do Pedido:</span>
                <span>${recibo.data_pedido}</span>
            </div>
            <div class="receipt-line">
                <span>Confirmação:</span>
                <span>${recibo.data_confirmacao}</span>
            </div>
        </div>
        
        <div class="receipt-section">
            <h4>Cliente</h4>
            <div class="receipt-line">
                <span>Nome:</span>
                <span>${recibo.cliente.nome}</span>
            </div>
            <div class="receipt-line">
                <span>Telefone:</span>
                <span>${recibo.cliente.telefone}</span>
            </div>
            <div class="receipt-line">
                <span>E-mail:</span>
                <span>${recibo.cliente.email}</span>
            </div>
        </div>
        
        <div class="receipt-section">
            <h4>Entrega</h4>
            <div class="receipt-line">
                <span>Tipo:</span>
                <span>${recibo.entrega.tipo === 'entrega' ? 'Entrega em Casa' : 'Retirar no Balcão'}</span>
            </div>
            <div class="receipt-line">
                <span>Endereço:</span>
                <span>${recibo.entrega.endereco}</span>
            </div>
        </div>
        
        <div class="receipt-section">
            <h4>Itens</h4>
            <p>${recibo.itens}</p>
            ${recibo.observacoes ? `<p><strong>Obs:</strong> ${recibo.observacoes}</p>` : ''}
        </div>
        
        <div class="receipt-section">
            <h4>Pagamento</h4>
            <div class="receipt-line">
                <span>Forma:</span>
                <span>${recibo.pagamento.forma}</span>
            </div>
            <div class="receipt-line">
                <span>Status:</span>
                <span>${recibo.pagamento.status}</span>
            </div>
        </div>
        
        <div class="receipt-section">
            <h4>Valores</h4>
            <div class="receipt-line">
                <span>Subtotal:</span>
                <span>R$ ${recibo.valores.subtotal}</span>
            </div>
            ${parseFloat(recibo.valores.desconto) > 0 ? `
            <div class="receipt-line">
                <span>Desconto:</span>
                <span>- R$ ${recibo.valores.desconto}</span>
            </div>` : ''}
            ${parseFloat(recibo.valores.taxa_entrega) > 0 ? `
            <div class="receipt-line">
                <span>Taxa de Entrega:</span>
                <span>R$ ${recibo.valores.taxa_entrega}</span>
            </div>` : ''}
            <div class="receipt-line total">
                <span>TOTAL:</span>
                <span>R$ ${recibo.valores.total}</span>
            </div>
        </div>
        
        <div class="receipt-footer">
            <p>Obrigado pela preferência!</p>
            <p>CardapioGO - Sistema de Pedidos Online</p>
        </div>
    `;
}

function closeReceiptModal() {
    const modal = document.getElementById('receipt-modal');
    modal.style.display = 'none';
}

function printReceipt() {
    const content = document.getElementById('receipt-content').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Recibo - CardapioGO</title>
            <style>
                body { font-family: 'Courier New', monospace; margin: 20px; }
                .receipt-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 1rem; margin-bottom: 1rem; }
                .receipt-section { margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px dashed #ccc; }
                .receipt-line { display: flex; justify-content: space-between; margin-bottom: 0.25rem; }
                .receipt-line.total { font-weight: bold; border-top: 1px solid #333; padding-top: 0.5rem; }
                .receipt-footer { text-align: center; margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #333; }
            </style>
        </head>
        <body>${content}</body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Atualizar função showOrderSuccess para usar o novo fluxo
function showOrderSuccess(orderId) {
    showNotification('Pedido realizado com sucesso! Número: #' + orderId);
    clearCart();
    showSection('hero');
    document.getElementById('checkout-form').reset();
    appliedCoupon = null;
}

function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('cardapiogo_cart') || '[]');
    const count = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartCount = document.getElementById('cart-count');
    if (count > 0) {
        cartCount.textContent = `(${count})`;
    } else {
        cartCount.textContent = '';
    }
}

// Chame updateCartCount() sempre que o carrinho for alterado
document.addEventListener('DOMContentLoaded', updateCartCount);
// E também após adicionar/remover itens do carrinho

