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
if (isset($data['id']) && isset($data['naam']) && isset($data['achternaam']) && isset($data['prijs']) && isset($data['zitplaatsen']) && isset($data['type'])) {
    $id = $data['id'];
    $naam = $data['naam'];
    $achternaam = $data['achternaam'];
    $prijs = $data['prijs'];
    $zitplaatsen = $data['zitplaatsen'];
    $type = $data['type'];

    // Prepare and execute SQL statement
    $sql = "UPDATE reizen SET naam='$naam', achternaam='$achternaam', prijs='$prijs', zitplaatsen='$zitplaatsen', type='$type' WHERE id='$id'";

    if (mysqli_query($conn, $sql)) {
        $response = array("message" => "Records updated successfully.", "status" => "200");
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
