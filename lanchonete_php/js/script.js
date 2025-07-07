// Sistema de Carrinho e Funcionalidades do CardapioGO
let cart = [];
let currentSection = 'hero';
let appliedCoupon = null;
let deliveryFee = 5.00;

// Variáveis globais para PIX
let currentOrderId = null;


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
    if (cartCount) {
        cartCount.textContent = totalItems > 0 ? totalItems : '';
    }
    
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
    if (discountLine) discountLine.style.display = discount > 0 ? 'flex' : 'none';
    if (deliveryLine) deliveryLine.style.display = delivery > 0 ? 'flex' : 'none';
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
    if (messageElement) {
        messageElement.textContent = message;
        messageElement.className = 'coupon-message ' + type;
    }
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
        showNotification('Erro ao processar pedido');
        console.error('Error:', error);
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

// Função para mostrar pagamento PIX - CORRIGIDA
// Cole este código no lugar das funções antigas em js/script.js

// Função para mostrar pagamento PIX - AJUSTADA COM O TEMPO DE 10 SEGUNDOS
function showPixPayment(orderId) {
    currentOrderId = orderId;
    const modal = document.getElementById('pix-modal');
    const orderNumberEl = document.getElementById('order-number');
    const paymentAmountEl = document.getElementById('payment-amount');

    if (!modal) {
        console.error('Modal PIX não encontrado');
        showOrderSuccess(orderId); // Mostra sucesso se o modal falhar
        return;
    }

    // Atualiza as informações do modal
    if (orderNumberEl) {
        orderNumberEl.textContent = String(orderId).padStart(6, '0');
    }
    if (paymentAmountEl) {
        // Calcula o total final do carrinho para exibir no modal
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        let discount = 0;
        if (appliedCoupon) {
            discount = appliedCoupon.tipo === 'percentual' ? subtotal * (appliedCoupon.valor / 100) : appliedCoupon.valor;
        }
        const deliveryType = document.querySelector('input[name="tipo_entrega"]:checked');
        const delivery = (deliveryType && deliveryType.value === 'entrega') ? deliveryFee : 0;
        const total = Math.max(0, subtotal - discount + delivery);
        
        paymentAmountEl.textContent = total.toFixed(2).replace('.', ',');
    }

    // Garante que o status inicial seja "Aguardando pagamento..."
    const paymentStatus = document.getElementById('payment-status');
    if (paymentStatus) {
        paymentStatus.className = 'payment-status pending';
        paymentStatus.innerHTML = `<strong>Aguardando pagamento...</strong>
            <p>Pedido #<span id="order-number">${String(orderId).padStart(6, '0')}</span></p>
            <p>Valor: R$ <span id="payment-amount">${paymentAmountEl.textContent}</span></p>`;
    }

    // Mostra o modal
    modal.style.display = 'block';
    
    // Inicia um temporizador de 10 segundos para simular a confirmação
    console.log("Aguardando 10 segundos para simular a confirmação do pagamento...");
    setTimeout(() => {
        // Após 10 segundos, chama a função para verificar (e confirmar) o pagamento
        checkPaymentStatus(orderId);
    }, 15000); // 10000 milissegundos = 10 segundos
}

// Função para verificar o status do pagamento - AJUSTADA
function checkPaymentStatus(orderId) {
    fetch('confirm_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            order_id: orderId || currentOrderId 
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'confirmado') {
            // 1. Mostra "Pagamento Confirmado"
            showPaymentConfirmed();
            
            // 2. Espera mais 2 segundos antes de fechar e mostrar o recibo
            setTimeout(() => {
                closePixModal();
                showReceipt(data.recibo);
            }, 2000); // 2000 milissegundos = 2 segundos
        }
    })
    .catch(error => {
        console.error('Erro ao verificar pagamento:', error);
        // Opcional: fechar modal em caso de erro
        closePixModal();
        alert("Ocorreu um erro ao processar o pagamento.");
    });
}

function showPaymentConfirmed() {
    const paymentStatus = document.getElementById('payment-status');
    if (paymentStatus) {
        paymentStatus.className = 'payment-status confirmed';
        paymentStatus.innerHTML = '<strong>Pagamento Confirmado!</strong><p>Processando pedido...</p>';
    }
}

function closePixModal() {
    const modal = document.getElementById('pix-modal');
    if (modal) {
        modal.style.display = 'none';
    }
    // A verificação do paymentCheckInterval foi removida pois não usamos mais o sistema de loop.
    currentOrderId = null;
}

function showReceipt(recibo) {
    // Fechar modal PIX
    closePixModal();
    
    // Mostrar modal do recibo
    const receiptModal = document.getElementById('receipt-modal');
    const receiptContent = document.getElementById('receipt-content');
    
    if (receiptModal && receiptContent) {
        receiptContent.textContent = recibo;
        receiptModal.style.display = 'flex';
    }
    
    // Limpar carrinho e voltar ao início
    clearCart();
    showSection('hero');
    document.getElementById('checkout-form').reset();
    appliedCoupon = null;
}

function closeReceiptModal() {
    const modal = document.getElementById('receipt-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function printReceipt() {
    const content = document.getElementById('receipt-content');
    if (!content) return;
    
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
        <body><pre>${content.textContent}</pre></body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

function showOrderSuccess(orderId) {
    showNotification('Pedido realizado com sucesso! Número: #' + orderId);
    clearCart();
    showSection('hero');
    document.getElementById('checkout-form').reset();
    appliedCoupon = null;
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
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Persistência do carrinho
function saveCartToStorage() {
    try {
        localStorage.setItem('cardapiogo_cart', JSON.stringify(cart));
    } catch (error) {
        console.error('Erro ao salvar carrinho:', error);
    }
}

function loadCartFromStorage() {
    try {
        const savedCart = localStorage.getItem('cardapiogo_cart');
        if (savedCart) {
            cart = JSON.parse(savedCart);
            updateCartDisplay();
        }
    } catch (error) {
        console.error('Erro ao carregar carrinho:', error);
        cart = [];
    }
}

function updateCartCount() {
    try {
        const cartData = JSON.parse(localStorage.getItem('cardapiogo_cart') || '[]');
        const count = cartData.reduce((sum, item) => sum + (item.quantity || 1), 0);
        const cartCount = document.getElementById('cart-count');
        if (cartCount) {
            cartCount.textContent = count > 0 ? count : '';
        }
    } catch (error) {
        console.error('Erro ao atualizar contador:', error);
    }
}
// Efeito de rolagem para o cabeçalho (header)
function initializeHeaderScroll() {
    const header = document.querySelector('.header');
    if (!header) return;

    // Verifica a posição da rolagem ao carregar a página
    if (window.scrollY > 50) {
        header.classList.add('scrolled');
    }

    // Adiciona um listener para o evento de scroll
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) { // Adiciona a classe se rolar mais de 50px
            header.classList.add('scrolled');
        } else { // Remove a classe se estiver no topo
            header.classList.remove('scrolled');
        }
    });
}

// Chama a nova função quando o conteúdo da página for carregado
document.addEventListener('DOMContentLoaded', initializeHeaderScroll);