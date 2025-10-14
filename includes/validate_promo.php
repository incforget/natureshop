<?php
include_once 'config.php';
include_once 'functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['valid' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['code']) || !isset($data['subtotal'])) {
    echo json_encode(['valid' => false, 'message' => 'Missing required parameters']);
    exit;
}

$code = trim(strtoupper($data['code']));
$subtotal = floatval($data['subtotal']);

$result = applyPromoCode($code, $subtotal);

echo json_encode($result);
?>