<?php
// Define a whitelist of allowed HTTP methods
$allowed_methods = array('GET');

// Get the HTTP method from the server
$method = $_SERVER['REQUEST_METHOD'];

// Check if the method is in the whitelist
if (!in_array($method, $allowed_methods)) {
    // If not, send a 405 Method Not Allowed response
    http_response_code(405);
    $response = array("message" => "Method not allowed.");
    header('Content-Type: application/json; charset=UTF-8');
    header("X-Content-Type-Options: nosniff");
    echo json_encode($response);
    exit;
}

// Function to validate the content-type of the request
function validateContentType($contentType)
{
    // Define a list of valid content types
    $validContentTypes = ['application/json; charset=UTF-8'];
    // Check if the content type of the request is in the list of valid content types
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

// Get the content type from the server
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
// Validate the content type
validateContentType($contentType);

// Include the database connection and the IdC class
include_once('../database/dbconnection.php');
include_once('../IdP-map/IdC.php');

// Get the request data and headers
$data = json_decode(file_get_contents("php://input"), true);
$headers = getallheaders();
// Get the Authorization header
$authorization = isset($headers['Authorization']) ? $headers['Authorization'] : '';

// Check if a bearer token is provided
if (preg_match('/Bearer\s(\S+)/', $authorization, $matches)) {
    // If so, extract the token
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

// Create an array of credentials from the request data
$bearerCredentials = array(
    'username' => isset($data['username']) ? filter_var($data['username'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '',
    'password' => isset($data['password']) ? filter_var($data['password'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : ''
);
// Create a new IdC object with the bearer token and credentials
$idC = new IdC($bearerToken, $bearerCredentials);

// Try to decode the token
if (!$idC->decodeToken()) {
    // If the token is invalid, send a 401 Unauthorized response
    http_response_code(401);
    $response = array("message" => "Invalid token.");
    header('Content-Type: application/json; charset=UTF-8');
    header("X-Content-Type-Options: nosniff");
    echo json_encode($response);
    exit;
}

// Get the user, top, and autoid parameters from the request data
$user = isset($data['user']) ? filter_var($data['user'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';
$top = isset($data['top']) ? filter_var($data['top'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';
$autoid = isset($data['autoid']) ? filter_var($data['autoid'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';

// Prepare the SQL statement based on the provided parameters
if (!empty($autoid)) {
    // If an autoid is provided, select the corresponding record
    $stmt = $conn->prepare("SELECT ecars.*, credentials.voornaam, credentials.tussenvoegsel, credentials.achternaam FROM ecars LEFT JOIN credentials ON ecars.verhuurder = credentials.id WHERE ecars.id = ?");
} elseif (!empty($top)) {
    // If a top parameter is provided, select the top 5 records
    $stmt = $conn->prepare("SELECT ecars.*, credentials.voornaam, credentials.tussenvoegsel, credentials.achternaam FROM ecars LEFT JOIN credentials ON ecars.verhuurder = credentials.id ORDER BY ecars.prijs ASC LIMIT 5");
} elseif (empty($user)) {
    // If no user is provided, select all records
    $stmt = $conn->prepare("SELECT ecars.*, credentials.voornaam, credentials.tussenvoegsel, credentials.achternaam FROM ecars LEFT JOIN credentials ON ecars.verhuurder = credentials.id ORDER BY ecars.prijs ASC");
} else {
    // If a user is provided, select the records for that user
    $stmt = $conn->prepare("SELECT ecars.*, credentials.voornaam, credentials.tussenvoegsel, credentials.achternaam FROM ecars LEFT JOIN credentials ON ecars.verhuurder = credentials.id WHERE ecars.verhuurder = ?");
}

// If the statement preparation failed, send a 500 Internal Server Error response
if (!$stmt) {
    http_response_code(500);
    $response = array("message" => "Error: " . $conn->error);
    header('Content-Type: application/json; charset=UTF-8');
    header("X-Content-Type-Options: nosniff");
    echo json_encode($response);
    exit();
}

// Bind the user or autoid parameter to the prepared statement
if (!empty($autoid)) {
    $stmt->bind_param("s", $autoid);
} elseif (!empty($user)) {
    $stmt->bind_param("s", $user);
}

// Execute the statement
if (!$stmt->execute()) {
    // If the execution failed, send a 500 Internal Server Error response
    http_response_code(500);
    $response = array("message" => "Error: " . $stmt->error);
    header('Content-Type: application/json; charset=UTF-8');
    header("X-Content-Type-Options: nosniff");
    echo json_encode($response);
    exit();
}

// Get the result of the statement
$result = $stmt->get_result();
$data = array();

// If there are rows in the result, add them to the data array
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['verhuurder'] = $row['voornaam'] . ' ' . $row['tussenvoegsel'] . ' ' . $row['achternaam'];
        unset($row['voornaam'], $row['tussenvoegsel'], $row['achternaam']);
        $data[] = $row;
    }
    // Send a 200 OK response with the data
    $response = array("message" => "Data retrieved successfully.", "status" => "200", "data" => $data);
} else {
    // If there are no rows in the result, send a 404 Not Found response
    http_response_code(404);
    $response = array("message" => "No records found.", "status" => "404", "data" => $data);
}

// Close the statement and the database connection
$stmt->close();
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