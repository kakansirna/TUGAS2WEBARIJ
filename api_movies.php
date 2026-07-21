<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$file = __DIR__ . "/movies.json";

// Helper function to read movies from JSON file
function readMovies($file) {
    if (!file_exists($file)) {
        return [];
    }
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

// Helper function to write movies to JSON file
function writeMovies($file, $movies) {
    return file_put_contents($file, json_encode($movies, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

$method = $_SERVER['REQUEST_METHOD'];

// Handle GET - Tampilkan semua atau cari movie
if ($method === 'GET') {
    $movies = readMovies($file);
    
    // Check if search query string is provided
    if (isset($_GET['search'])) {
        $keyword = trim(strtolower($_GET['search']));
        if ($keyword !== '') {
            $filtered = [];
            foreach ($movies as $movie) {
                $title = isset($movie['title']) ? strtolower($movie['title']) : '';
                $genre = isset($movie['genre']) ? strtolower($movie['genre']) : '';
                if (strpos($title, $keyword) !== false || strpos($genre, $keyword) !== false) {
                    $filtered[] = $movie;
                }
            }
            $movies = $filtered;
        }
    }
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "data" => $movies
    ]);
    exit();
}

// Handle POST - Tambah movie baru
if ($method === 'POST') {
    // Read parameters from $_POST since we are using multipart/form-data
    if (!isset($_POST['title']) || !isset($_POST['genre']) || !isset($_POST['year'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Data film tidak lengkap (judul, genre, tahun wajib diisi)."
        ]);
        exit();
    }
    
    $title = trim($_POST['title']);
    $genre = trim($_POST['genre']);
    $year = intval($_POST['year']);
    
    if (empty($title) || empty($genre) || $year <= 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Format data tidak valid atau field kosong."
        ]);
        exit();
    }
    
    // Handle image upload
    $imagePath = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $fileInfo = pathinfo($_FILES['image']['name']);
        $ext = strtolower($fileInfo['extension'] ?? '');
        
        if (!in_array($ext, $allowedExtensions)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Format file gambar tidak didukung (hanya JPG, PNG, GIF, WEBP)."
            ]);
            exit();
        }
        
        // Double check if file is an actual image
        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check === false) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "File yang diunggah bukan gambar yang valid."
            ]);
            exit();
        }
        
        // Create upload directory inside backend/ if it doesn't exist
        $uploadDir = __DIR__ . "/uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate a safe unique name for the file
        $fileName = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($_FILES['image']['name']));
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imagePath = "uploads/" . $fileName;
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Gagal menyimpan file gambar ke server."
            ]);
            exit();
        }
    }
    
    $movies = readMovies($file);
    
    // Calculate new ID (max ID + 1)
    $maxId = 0;
    foreach ($movies as $movie) {
        if (isset($movie['id']) && $movie['id'] > $maxId) {
            $maxId = $movie['id'];
        }
    }
    $newId = $maxId + 1;
    
    $newMovie = [
        "id" => $newId,
        "title" => $title,
        "genre" => $genre,
        "year" => $year,
        "image" => $imagePath // Save image path here
    ];
    
    $movies[] = $newMovie;
    
    if (writeMovies($file, $movies)) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Movie berhasil ditambahkan!",
            "data" => $newMovie
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Gagal menyimpan data movie ke movies.json."
        ]);
    }
    exit();
}

// Method not allowed fallback
http_response_code(405);
echo json_encode([
    "success" => false,
    "message" => "Metode request tidak diizinkan."
]);
