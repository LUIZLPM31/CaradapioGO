<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['order_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do pedido é obrigatório']);
    exit;
}

$order_id = intval($input['order_id']);
$action = isset($input['action']) ? $input['action'] : 'confirm';

try {
    $conn = getConnection();
    
    if ($action === 'check_status') {
        // Verificar status do pagamento
        $stmt = $conn->prepare("
            SELECT id, status_pagamento, data_confirmacao 
            FROM orders 
            WHERE id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            echo json_encode(['error' => 'Pedido não encontrado']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'status_pagamento' => $order['status_pagamento'],
            'data_confirmacao' => $order['data_confirmacao']
        ]);
        
    } else if ($action === 'confirm') {
        // Confirmar pagamento (simulado - em produção verificar com API do banco)
        $stmt = $conn->prepare("
            UPDATE orders 
            SET status_pagamento = 'confirmado', 
                status = 'confirmado',
                data_confirmacao = NOW() 
            WHERE id = ? AND status_pagamento = 'pendente'
        ");
        $stmt->execute([$order_id]);
        
        if ($stmt->rowCount() > 0) {
            // Buscar dados do pedido para o recibo
            $stmt = $conn->prepare("
                SELECT o.*, 
                       GROUP_CONCAT(
                           CONCAT(mi.nome, ' (', oi.quantidade, 'x ', oi.preco_unitario, ')')
                           SEPARATOR ', '
                       ) as itens
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN menu_items mi ON oi.item_id = mi.id
                WHERE o.id = ?
                GROUP BY o.id
            ");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'Pagamento confirmado com sucesso!',
                'recibo' => generateReceipt($order)
            ]);
        } else {
            echo json_encode(['error' => 'Não foi possível confirmar o pagamento']);
        }
    }
    
} catch(PDOException $e) {
    echo json_encode(['error' => 'Erro ao processar confirmação: ' . $e->getMessage()]);
}

function generateReceipt($order) {
    $recibo = [
        'numero_pedido' => str_pad($order['id'], 6, '0', STR_PAD_LEFT),
        'data_pedido' => date('d/m/Y H:i:s', strtotime($order['data'])),
        'data_confirmacao' => date('d/m/Y H:i:s', strtotime($order['data_confirmacao'])),
        'cliente' => [
            'nome' => $order['cliente_nome'],
            'telefone' => $order['cliente_telefone'],
            'email' => $order['cliente_email']
        ],
        'entrega' => [
            'tipo' => $order['tipo_entrega'],
            'endereco' => $order['tipo_entrega'] === 'entrega' ? 
                $order['endereco_entrega'] . ', ' . $order['endereco_numero'] . 
                ($order['endereco_complemento'] ? ', ' . $order['endereco_complemento'] : '') .
                ' - ' . $order['endereco_bairro'] . ', ' . $order['endereco_cidade'] . 
                ' - CEP: ' . $order['endereco_cep'] : 'Retirar no balcão'
        ],
        'pagamento' => [
            'forma' => ucfirst($order['forma_pagamento']),
            'status' => 'Confirmado'
        ],
        'valores' => [
            'subtotal' => number_format($order['subtotal'], 2, ',', '.'),
            'desconto' => number_format($order['cupom_desconto'], 2, ',', '.'),
            'taxa_entrega' => number_format($order['taxa_entrega'], 2, ',', '.'),
            'total' => number_format($order['total'], 2, ',', '.')
        ],
        'itens' => $order['itens'],
        'observacoes' => $order['observacoes']
    ];
    
    return $recibo;
}
?>

