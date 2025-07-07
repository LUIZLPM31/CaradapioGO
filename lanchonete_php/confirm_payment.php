<?php
// Desabilitar exibição de erros para não quebrar o JSON
ini_set('display_errors', 0);
error_reporting(0);

require_once 'includes/config.php';

header('Content-Type: application/json');

function returnError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'status' => 'error', 'error' => $message]);
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
        returnError('Erro de conexão com banco de dados', 500);
    }
    
    $stmt_order = $conn->prepare("SELECT status_pagamento FROM orders WHERE id = ?");
    $stmt_order->execute([$order_id]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        returnError('Pedido não encontrado', 404);
    }
    
    // Se o pagamento ainda está pendente, este script irá confirmá-lo (para fins de teste).
    if ($order['status_pagamento'] === 'pendente') {
        $stmt_update = $conn->prepare("UPDATE orders SET status_pagamento = 'confirmado', status = 'confirmado' WHERE id = ?");
        if (!$stmt_update->execute([$order_id])) {
             returnError('Erro ao atualizar o status do pedido', 500);
        }
    }

    // --- INÍCIO DA MODIFICAÇÃO: RECIBO GENÉRICO ---

    // Em vez de buscar os dados do pedido, criamos um texto fixo para o recibo.
    $genericReceipt = "=== RECIBO CARDAPIOGO===\n";
    $genericReceipt .= "Pedido #" . $order_id . "\n"; // Usamos o ID real do pedido
    $genericReceipt .= "Cliente: Você é especial \n";
    $genericReceipt .= "Telefone: (51) 981580205\n";
    $genericReceipt .= "Data: " . date('d/m/Y H:i') . "\n\n"; // Data e hora atuais
    $genericReceipt .= "Tipo: Entrega\n";
    $genericReceipt .= "Pagamento: PIX (Confirmado)\n\n";
    $genericReceipt .= "ITENS:\n";
    $genericReceipt .= "Obrigado pela preferência!\n";


    // Retornamos a resposta de sucesso com o recibo genérico.
    echo json_encode([
        'success' => true,
        'status' => 'confirmado',
        'message' => 'Pagamento confirmado com sucesso!',
        'recibo' => $genericReceipt, // Usando a variável com o texto fixo
        'order_id' => $order_id
    ]);
    
    // --- FIM DA MODIFICAÇÃO ---
    
} catch(PDOException $e) {
    error_log("Erro PDO em confirm_payment.php: " . $e->getMessage());
    returnError('Erro interno do servidor', 500);
}
?>