<?php
include_once('../database/dbconnection.php');
include_once('../IdP-map/IdC.php');

// Get headers
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

// Retrieve data
$sql = "SELECT * FROM reizen";
$result = mysqli_query($conn, $sql);
$data = array();


if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    $response = array("message" => "Data retrieved successfully.", "status" => "200", "data" => $data);
} else {
    $response = array("message" => "No recordsss found.", "status" => "404", "data" => $data, "result" => $result);
}

// Close connection
mysqli_close($conn);

//debug

// Send JSON response
header("HTTP/1.1 " . $response['status']);
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json;charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("Cache-Control: max-age=100");
echo json_encode($response);
exit;
?>
