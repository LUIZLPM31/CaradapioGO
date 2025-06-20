<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

// Validar cupom
if (isset($input['action']) && $input['action'] === 'validate_coupon') {
    $codigo = sanitize($input['codigo']);
    
    if (empty($codigo)) {
        echo json_encode(['valid' => false, 'message' => 'Código do cupom é obrigatório']);
        exit;
    }
    
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("
            SELECT codigo, descricao, tipo, valor, data_inicio, data_fim 
            FROM cupons 
            WHERE codigo = ? AND ativo = 1
        ");
        $stmt->execute([$codigo]);
        $cupom = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cupom) {
            echo json_encode(['valid' => false, 'message' => 'Cupom não encontrado ou inválido']);
            exit;
        }
        
        // Verificar datas de validade
        $hoje = date('Y-m-d');
        if ($cupom['data_inicio'] && $cupom['data_inicio'] > $hoje) {
            echo json_encode(['valid' => false, 'message' => 'Cupom ainda não está válido']);
            exit;
        }
        
        if ($cupom['data_fim'] && $cupom['data_fim'] < $hoje) {
            echo json_encode(['valid' => false, 'message' => 'Cupom expirado']);
            exit;
        }
        
        echo json_encode([
            'valid' => true,
            'message' => 'Cupom válido: ' . $cupom['descricao'],
            'tipo' => $cupom['tipo'],
            'valor' => floatval($cupom['valor'])
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['valid' => false, 'message' => 'Erro ao validar cupom']);
    }
    exit;
}

// Processar pedido
$cliente_nome = sanitize($input['cliente_nome']);
$cliente_telefone = sanitize($input['cliente_telefone']);
$cliente_email = sanitize($input['cliente_email']);
$tipo_entrega = sanitize($input['tipo_entrega']);
$forma_pagamento = sanitize($input['forma_pagamento']);
$observacoes = sanitize($input['observacoes']);
$items = $input['items'];
$subtotal = floatval($input['subtotal']);
$cupom_codigo = isset($input['cupom_codigo']) ? sanitize($input['cupom_codigo']) : null;
$cupom_desconto = floatval($input['cupom_desconto']);
$taxa_entrega = floatval($input['taxa_entrega']);
$total = floatval($input['total']);

// Campos de endereço (apenas para entrega)
$endereco_entrega = null;
$endereco_numero = null;
$endereco_complemento = null;
$endereco_bairro = null;
$endereco_cidade = null;
$endereco_cep = null;
$troco_para = null;

if ($tipo_entrega === 'entrega') {
    $endereco_entrega = sanitize($input['endereco_entrega']);
    $endereco_numero = sanitize($input['endereco_numero']);
    $endereco_complemento = sanitize($input['endereco_complemento']);
    $endereco_bairro = sanitize($input['endereco_bairro']);
    $endereco_cidade = sanitize($input['endereco_cidade']);
    $endereco_cep = sanitize($input['endereco_cep']);
}

if ($forma_pagamento === 'dinheiro' && isset($input['troco_para'])) {
    $troco_para = floatval($input['troco_para']);
}

// Validações básicas
if (empty($cliente_nome) || empty($cliente_telefone) || empty($cliente_email)) {
    echo json_encode(['success' => false, 'message' => 'Dados do cliente são obrigatórios']);
    exit;
}

if ($tipo_entrega === 'entrega' && (empty($endereco_entrega) || empty($endereco_numero) || empty($endereco_bairro))) {
    echo json_encode(['success' => false, 'message' => 'Dados de endereço são obrigatórios para entrega']);
    exit;
}

if (empty($items) || !is_array($items)) {
    echo json_encode(['success' => false, 'message' => 'Carrinho vazio']);
    exit;
}

try {
    $conn = getConnection();
    $conn->beginTransaction();
    
    // Inserir pedido
    $stmt = $conn->prepare("
        INSERT INTO orders (
            cliente_nome, cliente_telefone, cliente_email, 
            endereco_entrega, endereco_numero, endereco_complemento, 
            endereco_bairro, endereco_cidade, endereco_cep,
            tipo_entrega, forma_pagamento, troco_para,
            cupom_codigo, cupom_desconto, subtotal, taxa_entrega, total,
            observacoes, status, status_pagamento
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente', 'pendente')
    ");
    
    $stmt->execute([
        $cliente_nome, $cliente_telefone, $cliente_email,
        $endereco_entrega, $endereco_numero, $endereco_complemento,
        $endereco_bairro, $endereco_cidade, $endereco_cep,
        $tipo_entrega, $forma_pagamento, $troco_para,
        $cupom_codigo, $cupom_desconto, $subtotal, $taxa_entrega, $total,
        $observacoes
    ]);
    
    $order_id = $conn->lastInsertId();
    
    // Inserir itens do pedido
    $stmt = $conn->prepare("
        INSERT INTO order_items (order_id, item_id, quantidade, preco_unitario) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($items as $item) {
        $stmt->execute([
            $order_id,
            $item['id'],
            $item['quantity'],
            $item['price']
        ]);
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Pedido realizado com sucesso!',
        'order_id' => $order_id,
        'forma_pagamento' => $forma_pagamento
    ]);
    
} catch(PDOException $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erro ao processar pedido: ' . $e->getMessage()]);
}
?>

