<?php
// Desabilitar exibição de erros para não quebrar o JSON
ini_set('display_errors', 0);
error_reporting(0);

require_once 'includes/config.php';

header('Content-Type: application/json');

// Função para retornar erro JSON
function returnError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnError('Método não permitido', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['order_id'])) {
    returnError('ID do pedido é obrigatório');
}

$order_id = intval($input['order_id']);

if ($order_id <= 0) {
    returnError('ID do pedido inválido');
}

try {
    $conn = getConnection();
    
    if (!$conn) {
        returnError('Erro de conexão com banco de dados');
    }
    
    // Verificar se o pedido existe
    $stmt = $conn->prepare("SELECT id, status_pagamento, total FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        returnError('Pedido não encontrado');
    }
    
    if ($order['status_pagamento'] === 'confirmado') {
        returnError('Pagamento já foi confirmado');
    }
    
    // Atualizar status do pagamento
    $stmt = $conn->prepare("UPDATE orders SET status_pagamento = 'confirmado', status = 'confirmado' WHERE id = ?");
    $result = $stmt->execute([$order_id]);
    
    if (!$result) {
        returnError('Erro ao atualizar status do pagamento');
    }
    
    // Buscar dados completos do pedido para o recibo
    $stmt = $conn->prepare("
        SELECT o.*, 
               GROUP_CONCAT(
                   CONCAT(mi.nome, ' (', oi.quantidade, 'x R$ ', FORMAT(oi.preco_unitario, 2), ')')
                   SEPARATOR '\n'
               ) as itens
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN menu_items mi ON oi.item_id = mi.id
        WHERE o.id = ?
        GROUP BY o.id
    ");
    $stmt->execute([$order_id]);
    $orderComplete = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$orderComplete) {
        returnError('Erro ao buscar dados do pedido');
    }
    
    // Função para gerar recibo
    function generateReceipt($order) {
        $recibo = "=== RECIBO ===\n";
        $recibo .= "Pedido #" . $order['id'] . "\n";
        $recibo .= "Cliente: " . $order['cliente_nome'] . "\n";
        $recibo .= "Telefone: " . $order['cliente_telefone'] . "\n";
        
        if (isset($order['created_at'])) {
            $recibo .= "Data: " . date('d/m/Y H:i', strtotime($order['created_at'])) . "\n";
        }
        
        $recibo .= "Tipo: " . ucfirst($order['tipo_entrega']) . "\n";
        $recibo .= "Pagamento: " . ucfirst($order['forma_pagamento']) . "\n\n";
        
        if ($order['itens']) {
            $recibo .= "ITENS:\n" . $order['itens'] . "\n\n";
        }
        
        $recibo .= "Subtotal: R$ " . number_format($order['subtotal'], 2, ',', '.') . "\n";
        
        if ($order['taxa_entrega'] > 0) {
            $recibo .= "Taxa de entrega: R$ " . number_format($order['taxa_entrega'], 2, ',', '.') . "\n";
        }
        
        if ($order['cupom_desconto'] > 0) {
            $recibo .= "Desconto: -R$ " . number_format($order['cupom_desconto'], 2, ',', '.') . "\n";
        }
        
        $recibo .= "TOTAL: R$ " . number_format($order['total'], 2, ',', '.') . "\n";
        $recibo .= "\nObrigado pela preferência!";
        
        return $recibo;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Pagamento confirmado com sucesso!',
        'recibo' => generateReceipt($orderComplete),
        'order_id' => $order_id
    ]);
    
} catch(PDOException $e) {
    error_log("Erro PDO em confirm_payment.php: " . $e->getMessage());
    returnError('Erro interno do servidor');
} catch(Exception $e) {
    error_log("Erro geral em confirm_payment.php: " . $e->getMessage());
    returnError('Erro interno do servidor');
}
?>
