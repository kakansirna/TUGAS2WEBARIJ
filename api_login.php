<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Metode request tidak diizinkan."]);
    exit();
}

// Hardcoded users database
$users = [
    ["username" => "admin", "password" => "admin123"],
    ["username" => "user",  "password" => "user123"]
];

// Read raw POST JSON input
$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['username']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Username dan password wajib diisi."]);
    exit();
}

$username = trim($input['username']);
$password = trim($input['password']);

$login_success = false;
$logged_user = "";

foreach ($users as $user) {
    if ($user['username'] === $username && $user['password'] === $password) {
        $login_success = true;
        $logged_user = $user['username'];
        break;
    }
}

if ($login_success) {
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "user" => $logged_user,
        "message" => "Login berhasil!"
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Username atau password salah."
    ]);
}