<?php
// Health check endpoint for deployment
header('Content-Type: application/json');
http_response_code(200);
echo json_encode([
    'status' => 'healthy',
    'service' => 'arbitragem-cripto',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>