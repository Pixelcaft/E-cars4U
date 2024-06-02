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

$user = isset($data['user']) ? $data['user'] : '';
$top = isset($data['top']) ? $data['top'] : '';
$autoid = isset($data['autoid']) ? $data['autoid'] : '';

// Prepare SQL statement
if (!empty($autoid)) {
    $stmt = $conn->prepare("SELECT ecars.*, credentials.voornaam, credentials.tussenvoegsel, credentials.achternaam FROM ecars LEFT JOIN credentials ON ecars.verhuurder = credentials.id WHERE ecars.id = ?");
} elseif (!empty($top)) {
    $stmt = $conn->prepare("SELECT ecars.*, credentials.voornaam, credentials.tussenvoegsel, credentials.achternaam FROM ecars LEFT JOIN credentials ON ecars.verhuurder = credentials.id ORDER BY ecars.prijs ASC LIMIT 5");
} elseif (empty($user)) {
    $stmt = $conn->prepare("SELECT ecars.*, credentials.voornaam, credentials.tussenvoegsel, credentials.achternaam FROM ecars LEFT JOIN credentials ON ecars.verhuurder = credentials.id ORDER BY ecars.prijs ASC");
} else {
    $stmt = $conn->prepare("SELECT ecars.*, credentials.voornaam, credentials.tussenvoegsel, credentials.achternaam FROM ecars LEFT JOIN credentials ON ecars.verhuurder = credentials.id WHERE ecars.verhuurder = ?");
}

if (!$stmt) {
    $response = array("message" => "Error: " . $conn->error, "status" => "500");
    echo json_encode($response);
    exit();
}

// Bind the user parameter to the prepared statement
if (!empty($autoid)) {
    $stmt->bind_param("s", $autoid);
} elseif (!empty($user)) {
    $stmt->bind_param("s", $user);
}

// Execute statement
if (!$stmt->execute()) {
    $response = array("message" => "Error: " . $stmt->error, "status" => "500");
    echo json_encode($response);
    exit();
}
// Get result
$result = $stmt->get_result();
$data = array();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['verhuurder'] = $row['voornaam'] . ' ' . $row['tussenvoegsel'] . ' ' . $row['achternaam'];
        unset($row['voornaam'], $row['tussenvoegsel'], $row['achternaam']);
        $data[] = $row;
    }
    $response = array("message" => "Data retrieved successfully.", "status" => "200", "data" => $data);
} else {
    $response = array("message" => "No records found.", "status" => "404", "data" => $data);
}

// Close statement
$stmt->close();

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
