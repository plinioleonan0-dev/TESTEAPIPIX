<?php
header('Content-Type: application/json');

// ==========================================
// 🚨 CONFIGURAÇÕES SECRETAS (NUNCA EXPONHA)
// ==========================================
$API_KEY = 'sk_d636d456e0fc7c3a69e88923cc5fc2ae6ab1345024a7aa919dc301b16544cc0e'; // Coloque sua chave real
$PRODUCT_HASH = 'prod_0092beb142922970'; // Coloque seu hash real
$AMOUNT_CENTS = 300; // Ex: 1500 = R$ 15,00

// ==========================================
// FUNÇÕES HELPERS (Segurança e Validação)
// ==========================================
function generateUniqueEmail() {
    return "cliente_" . time() . "_" . uniqid() . "@email.com";
}

function generateValidCPF() {
    $n = array();
    for ($i = 0; $i < 9; $i++) { $n[$i] = rand(0, 9); }
    $d1 = $n[8]*2 + $n[7]*3 + $n[6]*4 + $n[5]*5 + $n[4]*6 + $n[3]*7 + $n[2]*8 + $n[1]*9 + $n[0]*10;
    $d1 = 11 - ( $d1 % 11 );
    if ( $d1 >= 10 ) $d1 = 0;
    $d2 = $d1*2 + $n[8]*3 + $n[7]*4 + $n[6]*5 + $n[5]*6 + $n[4]*7 + $n[3]*8 + $n[2]*9 + $n[1]*10 + $n[0]*11;
    $d2 = 11 - ( $d2 % 11 );
    if ( $d2 >= 10 ) $d2 = 0;
    return implode('', $n) . $d1 . $d2;
}

// ==========================================
// PAYLOAD E REQUISIÇÃO (API PARADISE)
// ==========================================
$payload = [
    "amount" => $AMOUNT_CENTS,
    "productHash" => $PRODUCT_HASH,
    "customer" => [
        "name" => "Cliente Random",
        "email" => generateUniqueEmail(),
        "document" => generateValidCPF(),
        "phone" => "11999999999"
    ]
];

// URL CORRIGIDA (com .php no final)
$ch = curl_init('https://multi.paradisepags.com/api/v1/transaction.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Key: ' . $API_KEY
]);

$response = curl_exec($ch);
curl_close($ch);

// Retorna os dados da Paradise direto para o frontend
echo $response;
?>