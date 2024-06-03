<?php
// Define an array of allowed HTTP methods
$allowed_methods = array('POST');

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
    // Define valid content types
    $validContentTypes = ['application/json; charset=UTF-8'];
    // If the content type is not valid, send a 415 Unsupported Media Type response
    if (!in_array($contentType, $validContentTypes)) {
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

// Get the JSON input and the headers from the request
$data = json_decode(file_get_contents("php://input"), true);
$headers = getallheaders();
$authorization = isset($headers['Authorization']) ? $headers['Authorization'] : '';

// Check if a token is provided in the Authorization header
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

// Verify the token using the IdC class
$bearerCredentials = array(
    'username' => isset($data['username']) ? filter_var($data['username'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '',
    'password' => isset($data['password']) ? filter_var($data['password'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : ''
);
$idC = new IdC($bearerToken, $bearerCredentials);

// If the token is invalid, send a 401 Unauthorized response
if (!$idC->decodeToken()) {
    http_response_code(401);
    $response = array("message" => "Invalid token.");
    header('Content-Type: application/json; charset=UTF-8');
    header("X-Content-Type-Options: nosniff");
    echo json_encode($response);
    exit;
}

// Get the 'huren' field from the request data
$huren = isset($data['huren']) ? filter_var($data['huren'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';

// If 'huren' is not set, process the request as a car submission
if (empty($huren)) {
    // Check if all required fields are present
    if (isset($data['autonaam']) && isset($data['type']) && isset($data['prijs']) && isset($data['zitplaatsen']) && isset($data['verhuurder'])) {
        // Sanitize the input
        $autonaam = filter_var($data['autonaam'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $type = filter_var($data['type'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $prijs = filter_var($data['prijs'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $zitplaatsen = filter_var($data['zitplaatsen'], FILTER_SANITIZE_NUMBER_INT);
        $verhuurder = filter_var($data['verhuurder'], FILTER_SANITIZE_NUMBER_INT);

        // Validate the input
        if (!is_numeric($prijs) || !ctype_digit((string)$zitplaatsen) || !is_numeric($verhuurder)) {
            // If the input is invalid, send a 400 Bad Request response
            http_response_code(400);
            $response = array("message" => "Invalid input.");
            header('Content-Type: application/json; charset=UTF-8');
            header("X-Content-Type-Options: nosniff");
            echo json_encode($response);
            exit();
        }

        // Prepare the SQL statement to insert the car into the database
        $stmt = $conn->prepare("INSERT INTO ecars (autonaam, type, prijs, zitplaatsen, verhuurder) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            // If the statement preparation fails, send a 500 Internal Server Error response
            http_response_code(500);
            $response = array("message" => "Internal Server Error");
            header('Content-Type: application/json; charset=UTF-8');
            header("X-Content-Type-Options: nosniff");
            echo json_encode($response);
            exit();
        }

        // Bind the parameters to the statement
        if (!$stmt->bind_param("ssdii", $autonaam, $type, $prijs, $zitplaatsen, $verhuurder)) {
            // If the parameter binding fails, send a 500 Internal Server Error response
            http_response_code(500);
            $response = array("message" => "Error: " . $stmt->error);
            header('Content-Type: application/json; charset=UTF-8');
            header("X-Content-Type-Options: nosniff");
            echo json_encode($response);
            exit();
        }

        // Execute the statement
        if ($stmt->execute()) {
            // If the execution is successful, send a 200 OK response
            $response = array("message" => "Records added successfully.", "status" => "200");
        } else {
            // If the execution fails, send a 500 Internal Server Error response
            http_response_code(500);
            $response = array("message" => "Error: " . $stmt->error);
        }

        // Close the statement
        $stmt->close();
    } else {
        // If not all required fields are present, send a 400 Bad Request response
        http_response_code(400);
        $response = array("message" => "Invalid input.", "status" => "400");
    }
} else {
    // If 'huren' is set, process the request as a rental submission
    if (isset($data['voornaam']) && isset($data['tussenvoegsel']) && isset($data['achternaam']) && isset($data['telefoonnummer']) && isset($data['plaats']) && isset($data['straat']) && isset($data['huisnummer']) && isset($data['autoid'])) {
        // Sanitize the input
        $voornaam = filter_var($data['voornaam'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $tussenvoegsel = filter_var($data['tussenvoegsel'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $achternaam = filter_var($data['achternaam'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $telefoonnummer = filter_var($data['telefoonnummer'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $plaats = filter_var($data['plaats'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $straat = filter_var($data['straat'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $huisnummer = filter_var($data['huisnummer'], FILTER_SANITIZE_NUMBER_INT);
        $autoid = filter_var($data['autoid'], FILTER_SANITIZE_NUMBER_INT);

        // Validate the input
        if (!is_numeric($huisnummer)) {
            // If the input is invalid, send a 400 Bad Request response
            http_response_code(400);
            $response = array("message" => "Invalid input.", "status" => "400");
            header('Content-Type: application/json; charset=UTF-8');
            header("X-Content-Type-Options: nosniff");
            echo json_encode($response);
            exit();
        }

        // Prepare the SQL statement to insert the rental into the database
        $stmt = $conn->prepare("INSERT INTO huren (voornaam, tussenvoegsel, achternaam, telefoonnummer, plaats, straat, huisnummer, autoid) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            // If the statement preparation fails, send a 500 Internal Server Error response
            http_response_code(500);
            $response = array("message" => "Error: " . $conn->error, "status" => "500");
            header('Content-Type: application/json; charset=UTF-8');
            header("X-Content-Type-Options: nosniff");
            echo json_encode($response);
            exit();
        }

        // Bind the parameters to the statement
        if (!$stmt->bind_param("ssssssii", $voornaam, $tussenvoegsel, $achternaam, $telefoonnummer, $plaats, $straat, $huisnummer, $autoid)) {
            // If the parameter binding fails, send a 500 Internal Server Error response
            http_response_code(500);
            $response = array("message" => "Error: " . $stmt->error, "status" => "500");
            header('Content-Type: application/json; charset=UTF-8');
            header("X-Content-Type-Options: nosniff");
            echo json_encode($response);
            exit();
        }

        // Execute the statement
        if ($stmt->execute()) {
            // If the execution is successful, send a 200 OK response
            $response = array("message" => "Records added successfully to 'huren' table.", "status" => "200");
        } else {
            // If the execution fails, send a 500 Internal Server Error response
            http_response_code(500);
            $response = array("message" => "Error: " . $stmt->error, "status" => "500");
        }

        // Close the statement
        $stmt->close();
    } else {
        // If not all required fields are present, send a 400 Bad Request response
        http_response_code(400);
        $response = array("message" => "Invalid input.", "status" => "400");
    }
}

// Close the database connection
mysqli_close($conn);

// Send the response
header("HTTP/1.1 " . $response['status']);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("Cache-Control: max-age=100");
echo json_encode($response);
exit;
?>
