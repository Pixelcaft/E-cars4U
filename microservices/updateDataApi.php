<?php
ini_set('display_errors', '0');     // Don't display errors
error_reporting(E_ALL | E_STRICT);  // Report all errors

// Define a whitelist of allowed HTTP methods
$allowed_methods = array('PUT');

// Get the HTTP method from the server
$method = $_SERVER['REQUEST_METHOD'];

// Check if the method is in the whitelist
// If not, return a 405 Method Not Allowed response
if (!in_array($method, $allowed_methods)) {
    error_log("Method not allowed: " . $method);  // Audit log
    http_response_code(405);
    $response = array("message" => "Method not allowed.");
    header('Content-Type: application/json; charset=UTF-8');
    header("X-Content-Type-Options: nosniff");
    echo json_encode($response);
    exit;
}

// Function to validate the content-type of the request
// If the content-type is not supported, return a 415 Unsupported Media Type response
function validateContentType($contentType)
{
    $validContentTypes = ['application/json; charset=UTF-8'];
    if (!in_array($contentType, $validContentTypes)) {
        error_log("Unsupported Media Type: " . $contentType);  // Audit log
        http_response_code(415);
        $response = array("message" => "Unsupported Media Type");
        header('Content-Type: application/json; charset=UTF-8');
        header("X-Content-Type-Options: nosniff");
        echo json_encode($response);
        exit;
    }
}

// Get the content-type from the server and validate it
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
validateContentType($contentType);

// Include the database connection and the IdC class
include_once('../database/dbconnection.php');
include_once('../IdP-map/IdC.php');

// Get the JSON input and the headers from the request
$data = json_decode(file_get_contents("php://input"), true);
$headers = getallheaders();
$authorization = isset($headers['Authorization']) ? $headers['Authorization'] : '';

// Check if a bearer token is provided in the Authorization header
// If not, return a 401 Unauthorized response
if (preg_match('/Bearer\s(\S+)/', $authorization, $matches)) {
    $bearerToken = $matches[1];
} else {
    error_log("No token provided");  // Audit log
    http_response_code(401);
    $response = array("message" => "No token provided.");
    header('Content-Type: application/json; charset=UTF-8');
    header("X-Content-Type-Options: nosniff");
    echo json_encode($response);
    exit;
}

// Verify the bearer token
// Create a new IdC instance with the bearer token and the credentials from the JSON input
$bearerCredentials = array(
    'username' => isset($data['username']) ? filter_var($data['username'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '',
    'password' => isset($data['password']) ? filter_var($data['password'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : ''
);
$idC = new IdC($bearerToken, $bearerCredentials);

// Decode the token
// If the token is invalid, return a 401 Unauthorized response
if (!$idC->decodeToken()) {
    http_response_code(401);
    $response = array("message" => "Invalid token.");
    header('Content-Type: application/json; charset=UTF-8');
    header("X-Content-Type-Options: nosniff");
    echo json_encode($response);
    exit;
}

// Check if the necessary data is provided in the JSON input
// If not, return a 400 Bad Request response
if (isset($data['id']) && isset($data['autonaam']) && isset($data['verhuurder']) && isset($data['prijs']) && isset($data['zitplaatsen']) && isset($data['type'])) {
    // Sanitize the input data
    $id = filter_var($data['id'], FILTER_SANITIZE_NUMBER_INT);
    $autonaam = filter_var($data['autonaam'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $verhuurder = filter_var($data['verhuurder'], FILTER_SANITIZE_NUMBER_INT);
    $prijs = filter_var($data['prijs'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $zitplaatsen = filter_var($data['zitplaatsen'], FILTER_SANITIZE_NUMBER_INT);
    $type = filter_var($data['type'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Prepare an SQL statement to update the ecars table
    $stmt = $conn->prepare("UPDATE ecars SET autonaam=?, prijs=?, zitplaatsen=?, type=? WHERE id=?");
    if (!$stmt) {
        http_response_code(500);
        $response = array("message" => "An error occurred.", "status" => "500");
        header("X-Content-Type-Options: nosniff");
        echo json_encode($response);
        exit();
    }

    // Bind the parameters to the SQL statement
    // If there is an error, return a 500 Internal Server Error response
    if (!$stmt->bind_param("sdiss", $autonaam, $prijs, $zitplaatsen, $type, $id)) {
        http_response_code(500);
        $response = array("message" => "An error occurred.", "status" => "500");
        header("X-Content-Type-Options: nosniff");
        echo json_encode($response);
        exit();
    }

    // Execute the SQL statement
    // If the statement is executed successfully, return a success message
    // If there is an error, return a 500 Internal Server Error response
    if ($stmt->execute()) {
        $response = array("message" => "Records updated successfully.", "status" => "200");
    } else {
        http_response_code(500);
        $response = array("message" => "An error occurred.", "status" => "500");
        header("X-Content-Type-Options: nosniff");
        echo json_encode($response);
    }

    // Close the SQL statement
    $stmt->close();
} else {
    error_log("Invalid input");  // Audit log
    http_response_code(400);
    $response = array("message" => "Invalid input.", "status" => "400");
    header("X-Content-Type-Options: nosniff");
    echo json_encode($response);
}

// Close the database connection
mysqli_close($conn);

// Send the JSON response with the appropriate headers
header("HTTP/1.1 " . $response['status']);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("Cache-Control: max-age=100");
echo json_encode($response);
exit;
?>