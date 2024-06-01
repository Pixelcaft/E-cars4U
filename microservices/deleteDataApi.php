<?php
include_once('../database/dbconnection.php');
include_once('../IdP-map/IdC.php');

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);
$headers = getallheaders();
$authorization = isset($headers['Authorization']) ? $headers['Authorization'] : '';

// Check if token is provided
if (preg_match('/Bearer\s(\S+)/', $authorization, $matches)) {
    $bearerToken = $matches[1];
} else {
    $response = array("message" => "No token provided.");
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Verify token
$bearerCredentials = array(
    'username' => isset($data['username']) ? $data['username'] : '',
    'password' => isset($data['password']) ? $data['password'] : ''
);
$idC = new IdC($bearerToken, $bearerCredentials);

if (!$idC->decodeToken()) {
    $response = array("message" => "Invalid token.");
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Check if data is valid
if (isset($data['id'])) {
    $id = $data['id'];

    // Prepare SQL statement
    $stmt = $conn->prepare("DELETE FROM ecars WHERE id = ?");
    if (!$stmt) {
        $response = array("message" => "Error: " . $conn->error, "status" => "500");
        echo json_encode($response);
        exit();
    }

    // Bind parameters
    if (!$stmt->bind_param("i", $id)) {
        $response = array("message" => "Error: " . $stmt->error, "status" => "500");
        echo json_encode($response);
        exit();
    }

    // Execute statement
    if ($stmt->execute()) {
        $response = array("message" => "Record deleted successfully.", "status" => "200");
    } else {
        $response = array("message" => "Error: " . $stmt->error, "status" => "500");
    }

    // Close statement
    $stmt->close();
} else {
    $response = array("message" => "Invalid input.", "status" => "400");
}

// Close connection
mysqli_close($conn);

// Send JSON response
header("HTTP/1.1 " . $response['status']);
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json;charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("Cache-Control: max-age=100");
echo json_encode($response);
exit;
?>
