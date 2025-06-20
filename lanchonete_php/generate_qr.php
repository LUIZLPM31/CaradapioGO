<?php
require_once __DIR__ . 
    '/vendor/autoload.php'; // Inclui o autoload do Composer
require_once 'includes/config.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

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

try {
    $conn = getConnection();
    
    // Buscar dados do pedido
    $stmt = $conn->prepare("
        SELECT id, cliente_nome, total, forma_pagamento, status_pagamento 
        FROM orders 
        WHERE id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['error' => 'Pedido não encontrado']);
        exit;
    }
    
    if ($order['forma_pagamento'] !== 'pix') {
        echo json_encode(['error' => 'Pedido não é PIX']);
        exit;
    }
    
    // Gerar dados PIX (simulado - em produção usar API do banco)
    $pixData = generatePixData($order);
    
    // Gerar QR Code
    $options = new QROptions([
        'version'    => 5,
        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'   => QRCode::ECC_L,
        'scale'      => 6,
        'imageBase64' => true,
    ]);
    
    $qrcode = new QRCode($options);
    $qrCodeImage = $qrcode->render($pixData);
    
    // Salvar QR Code no banco
    $stmt = $conn->prepare("UPDATE orders SET qr_code_pix = ? WHERE id = ?");
    $stmt->execute([$pixData, $order_id]);
    
    echo json_encode([
        'success' => true,
        'qr_code' => $qrCodeImage,
        'pix_data' => $pixData,
        'order_id' => $order_id,
        'total' => $order['total'],
        'cliente_nome' => $order['cliente_nome']
    ]);
    
} catch(Exception $e) {
    echo json_encode(['error' => 'Erro ao gerar QR Code: ' . $e->getMessage()]);
}

function generatePixData($order) {
    // Dados PIX simulados (em produção, usar API do banco)
    $pixKey = '12345678901'; // CPF/CNPJ da lanchonete
    $merchantName = 'CARDAPIOGO LANCHONETE';
    $merchantCity = 'SAO PAULO';
    $amount = number_format($order['total'], 2, '.', '');
    $txid = 'PEDIDO' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
    
    // Formato PIX simplificado (em produção usar biblioteca específica)
    $pixString = "00020126580014BR.GOV.BCB.PIX0136{$pixKey}0208{$txid}5204000053039865802BR5925{$merchantName}6009{$merchantCity}62070503***6304";
    
    // Calcular CRC16 (simplificado)
    $crc = sprintf('%04X', crc16($pixString));
    
    return $pixString . $crc;
}

function crc16($data) {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($data); $i++) {
        $crc ^= ord($data[$i]) << 8;
        for ($j = 0; $j < 8; $j++) {
            if ($crc & 0x8000) {
                $crc = ($crc << 1) ^ 0x1021;
            } else {
                $crc = $crc << 1;
            }
        }
    }
    return $crc & 0xFFFF;
}


