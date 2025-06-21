<?php
require_once '../includes/config.php';

// Verificar se está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getConnection();

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_item':
                $nome = sanitize($_POST['nome']);
                $descricao = sanitize($_POST['descricao']);
                $preco = floatval($_POST['preco']);
                $categoria_id = intval($_POST['categoria_id']);
                
                $stmt = $conn->prepare("INSERT INTO menu_items (nome, descricao, preco, categoria_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nome, $descricao, $preco, $categoria_id]);
                $success = "Item adicionado com sucesso!";
                break;
                
            case 'update_order_status':
                $order_id = intval($_POST['order_id']);
                $status = sanitize($_POST['status']);
                
                $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt->execute([$status, $order_id]);
                $success = "Status do pedido atualizado!";
                break;

            case 'edit_item':
                $item_id = intval($_POST['item_id']);
                $nome = sanitize($_POST['nome']);
                $descricao = sanitize($_POST['descricao']);
                $preco = floatval($_POST['preco']);
                $categoria_id = intval($_POST['categoria_id']);

                $stmt = $conn->prepare("UPDATE menu_items SET nome = ?, descricao = ?, preco = ?, categoria_id = ? WHERE id = ?");
                $stmt->execute([$nome, $descricao, $preco, $categoria_id, $item_id]);
                $success = "Item editado com sucesso!";
                break;

            case 'delete_item':
                $item_id = intval($_POST['item_id']);
                $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
                $stmt->execute([$item_id]);
                $success = "Item excluído com sucesso!";
                break;
        }
    }
}

// Buscar dados para o dashboard
try {
    $conn = getConnection();
    
    // Pedidos pendentes
    $stmt = $conn->prepare("SELECT * FROM orders WHERE status IN ('pendente', 'preparando') ORDER BY data DESC");
    $stmt->execute();
    $pending_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Categorias
    $stmt = $conn->prepare("SELECT * FROM categories ORDER BY nome");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Itens do cardápio
    $stmt = $conn->prepare("
        SELECT mi.*, c.nome as categoria_nome 
        FROM menu_items mi 
        JOIN categories c ON mi.categoria_id = c.id 
        ORDER BY c.nome, mi.nome
    ");
    $stmt->execute();
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estatísticas
    $stmt = $conn->prepare("SELECT COUNT(*) as total_pedidos FROM orders WHERE DATE(data) = CURDATE()");
    $stmt->execute();
    $stats_today = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("SELECT SUM(total) as faturamento FROM orders WHERE DATE(data) = CURDATE() AND status != 'cancelado'");
    $stmt->execute();
    $revenue_today = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Erro ao carregar dados: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/png" href="/lanchonete_php/logo1.png">
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="container">
            <h1>Painel Administrativo</h1>
            <nav class="admin-nav">
                <a href="#dashboard" class="nav-link active">Dashboard</a>
                <a href="#pedidos" class="nav-link">Pedidos</a>
                <a href="#cardapio" class="nav-link">Cardápio</a>
                <a href="../" class="nav-link">Ver Site</a>
                <a href="logout.php" class="nav-link">Sair</a>
            </nav>
        </div>
    </header>

    <main class="admin-main">
        <div class="container">
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Dashboard -->
            <section id="dashboard" class="admin-section">
                <h2>Dashboard</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Pedidos Hoje</h3>
                        <p class="stat-number"><?php echo $stats_today['total_pedidos'] ?? 0; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Faturamento Hoje</h3>
                        <p class="stat-number"><?php echo formatPrice($revenue_today['faturamento'] ?? 0); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Pedidos Pendentes</h3>
                        <p class="stat-number"><?php echo count($pending_orders); ?></p>
                    </div>
                </div>
            </section>

            <!-- Pedidos -->
            <section id="pedidos" class="admin-section" style="display: none;">
                <h2>Gerenciar Pedidos</h2>
                <div class="orders-list">
                    <?php foreach ($pending_orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <h3>Pedido #<?php echo $order['id']; ?></h3>
                                <span class="status status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                            <div class="order-details">
                                <p><strong>Cliente:</strong> <?php echo htmlspecialchars($order['cliente_nome']); ?></p>
                                <p><strong>Telefone:</strong> <?php echo htmlspecialchars($order['cliente_telefone']); ?></p>
                                <p><strong>Endereço:</strong> <?php echo htmlspecialchars($order['endereco_entrega']); ?></p>
                                <p><strong>Total:</strong> <?php echo formatPrice($order['total']); ?></p>
                                <p><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($order['data'])); ?></p>
                                <?php if ($order['observacoes']): ?>
                                    <p><strong>Observações:</strong> <?php echo htmlspecialchars($order['observacoes']); ?></p>
                                <?php endif; ?>
                            </div>
                            <form method="POST" class="order-actions">
                                <input type="hidden" name="action" value="update_order_status">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="status" onchange="this.form.submit()">
                                    <option value="pendente" <?php echo $order['status'] === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                    <option value="preparando" <?php echo $order['status'] === 'preparando' ? 'selected' : ''; ?>>Preparando</option>
                                    <option value="pronto" <?php echo $order['status'] === 'pronto' ? 'selected' : ''; ?>>Pronto</option>
                                    <option value="entregue" <?php echo $order['status'] === 'entregue' ? 'selected' : ''; ?>>Entregue</option>
                                    <option value="cancelado" <?php echo $order['status'] === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                </select>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Cardápio -->
            <section id="cardapio" class="admin-section" style="display: none;">
                <h2>Gerenciar Cardápio</h2>
                
                <!-- Adicionar novo item -->
                <div class="add-item-form">
                    <h3>Adicionar Novo Item</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_item">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nome">Nome:</label>
                                <input type="text" id="nome" name="nome" required>
                            </div>
                            <div class="form-group">
                                <label for="categoria_id">Categoria:</label>
                                <select id="categoria_id" name="categoria_id" required>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="preco">Preço:</label>
                                <input type="number" id="preco" name="preco" step="0.01" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="descricao">Descrição:</label>
                            <textarea id="descricao" name="descricao" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Adicionar Item</button>
                    </form>
                </div>

                <!-- Lista de itens -->
                <div class="menu-items-list">
                    <h3>Itens do Cardápio</h3>
                    <div class="items-grid">
                        <?php foreach ($menu_items as $item): ?>
                            <div class="item-card">
                                <h4><?php echo htmlspecialchars($item['nome']); ?></h4>
                                <p class="item-category"><?php echo htmlspecialchars($item['categoria_nome']); ?></p>
                                <p class="item-description"><?php echo htmlspecialchars($item['descricao']); ?></p>
                                <p class="item-price"><?php echo formatPrice($item['preco']); ?></p>
                                <div class="item-actions">
                                    <button class="btn btn-secondary btn-sm edit-btn"
                                        data-id="<?php echo $item['id']; ?>"
                                        data-nome="<?php echo htmlspecialchars($item['nome']); ?>"
                                        data-descricao="<?php echo htmlspecialchars($item['descricao']); ?>"
                                        data-preco="<?php echo $item['preco']; ?>"
                                        data-categoria="<?php echo $item['categoria_id']; ?>"
                                        type="button">Editar</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_item">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir este item?')">Excluir</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Formulário de edição de item (oculto) -->
                <div id="edit-item-modal" style="display:none; background:#fff; border:1px solid #ccc; padding:20px; position:fixed; top:10%; left:50%; transform:translateX(-50%); z-index:1000;">
                    <h3>Editar Item</h3>
                    <form method="POST" id="edit-item-form">
                        <input type="hidden" name="action" value="edit_item">
                        <input type="hidden" name="item_id" id="edit-item-id">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="edit-nome">Nome:</label>
                                <input type="text" id="edit-nome" name="nome" required>
                            </div>
                            <div class="form-group">
                                <label for="edit-categoria">Categoria:</label>
                                <select id="edit-categoria" name="categoria_id" required>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit-preco">Preço:</label>
                                <input type="number" id="edit-preco" name="preco" step="0.01" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit-descricao">Descrição:</label>
                            <textarea id="edit-descricao" name="descricao" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('edit-item-modal').style.display='none'">Cancelar</button>
                    </form>
                </div>
                <!-- Fim do formulário de edição -->
            </section>
        </div>
    </main>

    <script>
        // Navegação entre seções
        document.querySelectorAll('.admin-nav .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.getAttribute('href').startsWith('#')) {
                    e.preventDefault();
                    
                    // Remover classe active de todos os links
                    document.querySelectorAll('.admin-nav .nav-link').forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Esconder todas as seções
                    document.querySelectorAll('.admin-section').forEach(section => {
                        section.style.display = 'none';
                    });
                    
                    // Mostrar seção selecionada
                    const targetId = this.getAttribute('href').substring(1);
                    const targetSection = document.getElementById(targetId);
                    if (targetSection) {
                        targetSection.style.display = 'block';
                    }
                }
            });
        });

        // Edição de item do cardápio
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('edit-item-id').value = this.dataset.id;
                document.getElementById('edit-nome').value = this.dataset.nome;
                document.getElementById('edit-descricao').value = this.dataset.descricao;
                document.getElementById('edit-preco').value = this.dataset.preco;
                document.getElementById('edit-categoria').value = this.dataset.categoria;
                document.getElementById('edit-item-modal').style.display = 'block';
            });
        });
    </script>
</body>
</html>

