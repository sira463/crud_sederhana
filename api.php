<?php
header('Content-Type: application/json');

// Nama file database SQLite
define('DB_FILE', __DIR__ . '/kontak_db.sqlite');

// Membuat koneksi SQLite
try {
    $conn = new PDO('sqlite:' . DB_FILE);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buat tabel kontak jika belum ada
    $conn->exec("CREATE TABLE IF NOT EXISTS kontak (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nama TEXT NOT NULL,
        telepon TEXT NOT NULL,
        email TEXT
    )");

} catch (PDOException $e) {
    die(json_encode(['status'=>'error','message'=>'Koneksi database gagal: '.$e->getMessage()]));
}

// Fungsi validasi input
function validate_input($data){
    $errors = [];
    if(empty($data['nama'])){
        $errors[] = 'Nama tidak boleh kosong';
    }
    if(empty($data['telepon']) || !preg_match('/^[0-9]+$/', $data['telepon'])){
        $errors[] = 'Nomor Telepon hanya boleh angka';
    }
    if(!empty($data['email'])){
        if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)){
            $errors[] = 'Format email tidak valid';
        } elseif(!preg_match('/@gmail\.com$/', $data['email'])){
            $errors[] = 'Email harus berakhiran @gmail.com';
        }
    }
    return $errors;
}

// Tangani request
$method = $_SERVER['REQUEST_METHOD'];

switch($method){
    case 'GET':
        $search = $_GET['search'] ?? '';
        if($search){
            $stmt = $conn->prepare("SELECT * FROM kontak WHERE nama LIKE :search");
            $stmt->execute([':search' => '%'.$search.'%']);
        } else {
            $stmt = $conn->query("SELECT * FROM kontak");
        }
        $kontak = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status'=>'success','data'=>$kontak]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $errors = validate_input($data);
        if($errors){
            echo json_encode(['status'=>'error','message'=>implode(', ',$errors)]);
            break;
        }
        $stmt = $conn->prepare("INSERT INTO kontak (nama, telepon, email) VALUES (:nama, :telepon, :email)");
        $stmt->execute([
            ':nama'=>$data['nama'],
            ':telepon'=>$data['telepon'],
            ':email'=>$data['email'] ?? ''
        ]);
        echo json_encode(['status'=>'success','message'=>'Kontak berhasil disimpan']);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $errors = validate_input($data);
        if(empty($data['id'])) $errors[] = "ID kontak tidak ditemukan";
        if($errors){
            echo json_encode(['status'=>'error','message'=>implode(', ',$errors)]);
            break;
        }
        $stmt = $conn->prepare("UPDATE kontak SET nama=:nama, telepon=:telepon, email=:email WHERE id=:id");
        $stmt->execute([
            ':nama'=>$data['nama'],
            ':telepon'=>$data['telepon'],
            ':email'=>$data['email'] ?? '',
            ':id'=>$data['id']
        ]);
        echo json_encode(['status'=>'success','message'=>'Kontak berhasil diperbarui']);
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        if(empty($data['id'])){
            echo json_encode(['status'=>'error','message'=>'ID kontak tidak ditemukan']);
            break;
        }
        $stmt = $conn->prepare("DELETE FROM kontak WHERE id=:id");
        $stmt->execute([':id'=>$data['id']]);
        echo json_encode(['status'=>'success','message'=>'Kontak berhasil dihapus']);
        break;

    default:
        echo json_encode(['status'=>'error','message'=>'Method tidak diizinkan']);
}
