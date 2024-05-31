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

    // Prepare and execute SQL statement
    $sql = "DELETE FROM reizen WHERE id='$id'";

    if (mysqli_query($conn, $sql)) {
        $response = array("message" => "Record deleted successfully.", "status" => "200");
    } else {
        $response = array("message" => "ERROR: Could not execute $sql. " . mysqli_error($conn), "status" => "500");
    }
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
?>
