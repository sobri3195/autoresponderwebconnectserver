<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Database configuration
$host = "localhost";
$db_name = "db";
$username = "user";
$password = "password";
$connection = new mysqli($host, $username, $password, $db_name);

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// get posted data
$posted_data = json_decode(file_get_contents("php://input"));

// make sure json data is not incomplete
if (
    !empty($posted_data->query) &&
    !empty($posted_data->appPackageName) &&
    !empty($posted_data->messengerPackageName) &&
    !empty($posted_data->query->sender) &&
    !empty($posted_data->query->message)
) {
    // Extract registration data from message
    if (preg_match('/^regis:(.*?);(.*?);(.*?);(.*?)$/', $posted_data->query->message, $matches)) {
        // Assign extracted data to variables
        $name = $matches[1];
        $address = $matches[2];
        $birthdate = $matches[3];
        $whatsappNumber = $matches[4];
        
        // Prepare SQL Insert statement
        $stmt = $connection->prepare("INSERT INTO registrations (name, address, birthdate, whatsapp_number) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $address, $birthdate, $whatsappNumber);
        
        // Execute query and check for success
        if ($stmt->execute()) {
            // Write to log file
            file_put_contents("registration_log.txt", "Success: Registration for {$name} at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
            
            // Prepare and execute log insert to database
            $logStmt = $connection->prepare("INSERT INTO registration_logs (name, status) VALUES (?, 'Success')");
            $logStmt->bind_param("s", $name);
            $logStmt->execute();
            $logStmt->close();

            http_response_code(200);
            echo json_encode(array("replies" => array(
                array("message" => "Hey " . $name . ", pendaftaran Anda berhasil disimpan!"),
                array("message" => "Success ✅")
            )));
        } else {
            // Write to log file
            $error = $connection->error;
            file_put_contents("registration_log.txt", "Error: {$error} for {$name} at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
            
            // Prepare and execute log insert to database
            $logStmt = $connection->prepare("INSERT INTO registration_logs (name, status) VALUES (?, ?)");
            $logStmt->bind_param("ss", $name, $error);
            $logStmt->execute();
            $logStmt->close();

            http_response_code(503);
            echo json_encode(array("message" => "Gagal menyimpan data."));
        }
        
        $stmt->close();
    } else {
        // Not a registration message
        echo json_encode(array("replies" => array(
            array("message" => "Format pendaftaran salah atau data tidak lengkap.")
        )));
    }
} else {
    // set response code - 400 bad request
    http_response_code(400);
    echo json_encode(array("message" => "Data JSON tidak lengkap. Apakah permintaan dikirim oleh AutoResponder?"));
}

// Close connection
$connection->close();
?>
