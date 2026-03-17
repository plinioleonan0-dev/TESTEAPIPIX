<?php
header('Content-Type: application/json');

// ==========================================
// 🚨 URL SECRETA DO UPSELL
// ==========================================
$UPSELL_URL = 'http://localhost/checkout-pix/obrigado.html';

$hash = isset($_GET['hash']) ? $_GET['hash'] : '';

if (empty($hash)) {
    echo json_encode(['status' => 'error', 'message' => 'Hash não informado.']);
    exit;
}

// Consulta o status na API da Paradise
$ch = curl_init("https://multi.paradisepags.com/api/v1/check_status.php?hash=" . urlencode($hash));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

// Lógica de Segurança (Aceita 'paid' ou 'approved' conforme a documentação da Paradise)
if (isset($data['status']) && ($data['status'] === 'paid' || $data['status'] === 'approved')) {
    echo json_encode([
        'status' => 'paid',
        'redirect_url' => $UPSELL_URL
    ]);
} else {
    echo json_encode([
        'status' => 'pending'
    ]);
}
?>