<?php

// Define an array of allowed HTTP methods
$allowed_methods = array('DELETE');

// Get the HTTP method of the current request from the server
$method = $_SERVER['REQUEST_METHOD'];

// Check if the method of the current request is in the allowed methods array
if (!in_array($method, $allowed_methods)) {
    // If not, send a 405 Method Not Allowed response
    http_response_code(405);
    $response = array("message" => "Method not allowed.");
    header('Content-Type: application/json; charset=UTF-8');
    header("X-Content-Type-Options: nosniff");
    echo json_encode($response);
    exit;
}

// Function to validate the content type of the request
function validateContentType($contentType)
{
    // Define an array of valid content types
    $validContentTypes = ['application/json; charset=UTF-8'];
    // Check if the content type of the request is in the valid content types array
    if (!in_array($contentType, $validContentTypes)) {
        // If not, send a 415 Unsupported Media Type response
        http_response_code(415);
        $response = array("message" => "Unsupported Media Type");
        header('Content-Type: application/json; charset=UTF-8');
        header("X-Content-Type-Options: nosniff");
        echo json_encode($response);
        exit;
    }
}

// Get the content type of the request
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
// Validate the content type
validateContentType($contentType);

// Include the database connection and the IdC class
include_once('../database/dbconnection.php');
include_once('../IdP-map/IdC.php');

// Get the JSON input from the request
$data = json_decode(file_get_contents("php://input"), true);
// Get all headers from the request
$headers = getallheaders();
// Get the Authorization header from the request
$authorization = isset($headers['Authorization']) ? $headers['Authorization'] : '';

// Check if a Bearer token is provided in the Authorization header
if (preg_match('/Bearer\s(\S+)/', $authorization, $matches)) {
    // If so, get the token
    $bearerToken = $matches[1];
} else {
    // If not, send a 401 Unauthorized response
    http_response_code(401);
    $response = array("message" => "No token provided.");
    header('Content-Type: application/json; charset=UTF-8');
    header("X-Content-Type-Options: nosniff");
    echo json_encode($response);
    exit;
}

// Create an array of credentials from the JSON input
$bearerCredentials = array(
    'username' => isset($data['username']) ? filter_var($data['username'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '',
    'password' => isset($data['password']) ? filter_var($data['password'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : ''
);
// Create a new instance of the IdC class with the token and the credentials
$idC = new IdC($bearerToken, $bearerCredentials);

// Verify the token
if (!$idC->decodeToken()) {
    // If the token is invalid, send a 401 Unauthorized response
    http_response_code(401);
    $response = array("message" => "Invalid token.");
    header('Content-Type: application/json; charset=UTF-8');
    header("X-Content-Type-Options: nosniff");
    echo json_encode($response);
    exit;
}

// Check if an id is provided in the JSON input
if (isset($data['id'])) {
    // If so, sanitize the id
    $id = filter_var($data['id'], FILTER_SANITIZE_NUMBER_INT);

    // Prepare a SQL DELETE statement
    $stmt = $conn->prepare("DELETE FROM ecars WHERE id = ?");
    if (!$stmt) {
        // If the statement preparation fails, send a 500 Internal Server Error response
        http_response_code(500);
        $response = array("message" => "Error: " . $conn->error, "status" => "500");
        header('Content-Type: application/json; charset=UTF-8');
        header("X-Content-Type-Options: nosniff");
        echo json_encode($response);
        exit();
    }

    // Bind the id parameter to the SQL statement
    if (!$stmt->bind_param("i", $id)) {
        // If the parameter binding fails, send a 500 Internal Server Error response
        http_response_code(500);
        $response = array("message" => "Error: " . $stmt->error, "status" => "500");
        header('Content-Type: application/json; charset=UTF-8');
        header("X-Content-Type-Options: nosniff");
        echo json_encode($response);
        exit();
    }

    // Execute the SQL statement
    if ($stmt->execute()) {
        // If the execution is successful, send a 200 OK response
        $response = array("message" => "Record deleted successfully.", "status" => "200");
    } else {
        // If the execution fails, send a 500 Internal Server Error response
        http_response_code(500);
        $response = array("message" => "Error: " . $stmt->error, "status" => "500");
        header('Content-Type: application/json; charset=UTF-8');
        header("X-Content-Type-Options: nosniff");
        echo json_encode($response);
        exit();
    }

    // Close the SQL statement
    $stmt->close();
} else {
    // If no id is provided, send a 400 Bad Request response
    http_response_code(400);
    $response = array("message" => "Invalid input.", "status" => "400");
    header('Content-Type: application/json; charset=UTF-8');
    header("X-Content-Type-Options: nosniff");
    echo json_encode($response);
    exit;
}

// Close the database connection
mysqli_close($conn);

// Send the JSON response
header("HTTP/1.1 " . $response['status']);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("Cache-Control: max-age=100");
echo json_encode($response);
exit;
?>