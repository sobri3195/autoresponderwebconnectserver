<?php

// Konfigurasi error reporting

ini_set('display_errors', 0); // Nonaktifkan penampilan error di output
error_reporting(E_ALL); // Laporkan semua jenis error
ini_set('log_errors', 1); // Aktifkan logging error
ini_set('error_log', __DIR__ . '/error_sobri.txt'); // File log error akan disimpan di direktori yang sama dengan index.php

// Mengatur header untuk mengizinkan CORS dan mendefinisikan tipe konten
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Import untuk kelas DateTime
use DateTime;

// Kredensial database
$host = 'localhost:3306';
$dbname = 'regis';
$username = 'user';
$password = '123';
$dsn = "mysql:host=$host;dbname=$dbname;charset=UTF8";

try {
    // Membuat objek PDO dan mengatur opsi error mode
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Memeriksa apakah metode request adalah POST dan data yang diperlukan ada
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['data']) && strpos($_POST['data'], 'regis#') === 0) {
        // Memproses data masukan
        $input = explode('#', $_POST['data']);
        
        // Memastikan data input memiliki empat bagian
        if (count($input) === 4) {
            // Mengabaikan elemen pertama dan mendapatkan data yang diinginkan
            [, $nama, $tanggal_lahir, $alamat] = $input;
            // Mencoba membuat objek DateTime
            $tanggal_obj = DateTime::createFromFormat('d/m/Y', $tanggal_lahir);
            if ($tanggal_obj) {
                $tanggal_lahir = $tanggal_obj->format('Y-m-d');

                // SQL statement untuk menyisipkan data
                $sql = "INSERT INTO users (nama, alamat, tanggal_lahir) VALUES (:nama, :alamat, :tanggal_lahir)";
                $stmt = $pdo->prepare($sql);

                // Menjalankan statement dengan data terikat
                $stmt->execute([
                    ':nama' => $nama,
                    ':alamat' => $alamat,
                    ':tanggal_lahir' => $tanggal_lahir
                ]);

                // Memberikan respon sukses dalam format JSON
                echo json_encode(["replies" => [
                    ["message" => "Hey, data berhasil disimpan ke database!"],
                    ["message" => "Success ✅"]
                ]]);
            } else {
                // Data tanggal lahir tidak valid
                http_response_code(400);
                echo json_encode(["replies" => [
                    ["message" => "Error: Tanggal lahir tidak valid."]
                ]]);
            }
        } else {
            // Data masukan tidak lengkap atau tidak benar
            http_response_code(400);
            echo json_encode(["replies" => [
                ["message" => "Error: Data masukan tidak lengkap atau salah."]
            ]]);
        }
    }
} catch (PDOException $e) {
    // Menangani kesalahan koneksi database
    http_response_code(500);
    echo json_encode(["replies" => [
        ["message" => "Database connection error: " . $e->getMessage()],
    ]]);
}
?>
