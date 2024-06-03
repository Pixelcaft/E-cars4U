<?php
header("X-Content-Type-Options: nosniff");
// Start session
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Include IdP class
include_once("IdP-map/IdP.php");

// Function to get token
function getToken()
{
    try {
        // Credentials
        $username = "E-cars4U";
        $password = "123";
        $credentials = array(
            'username' => $username,
            'password' => $password,
            'exp' => time() + (60 * 60) // Token expiration time
        );

        // Create IdP instance and return token
        $idp = new IdP($credentials);
        return $idp->getToken();
    } catch (Exception $e) {
        die(json_encode(array("message" => "Error getting token")));
    }
}

// Function to validate content type
function validateContentType($contentType)
{
    // List of valid content types
    $validContentTypes = ['application/json; charset=UTF-8'];
    // If content type is not valid, send 415 status code and exit
    if (!in_array($contentType, $validContentTypes)) {
        http_response_code(415);
        die("Unsupported Media Type");
    }
}

// Function to make cURL request
function curlRequest($url, $method, $data = null)
{
    try {
        // List of allowed HTTP methods
        $allowed_methods = array('GET', 'POST', 'PUT', 'DELETE');

        // If method is not allowed, send 405 status code and exit
        if (!in_array($method, $allowed_methods)) {
            http_response_code(405);
            die("Method not allowed");
        }

        // Get token
        $token = getToken();

        // Initialize cURL
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, "username:password");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . $token,
            "Content-Type: application/json; charset=UTF-8",
            "Accept: application/json; charset=UTF-8",
            "X-Content-Type-Options: nosniff"
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        // If data is provided, add it to the request
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        // Execute request and get response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        // If there was an error with the request, close cURL and throw an exception
        if ($response === false) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL error: $error_msg");
        }

        // Validate response content type
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        validateContentType($contentType);

        // Close cURL and return response
        curl_close($ch);
        return json_decode($response, true);
    } catch (Exception $e) {
        // Log the exception message and rethrow it
        error_log('Caught exception: ' . $e->getMessage());
        throw $e;
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get data from form
    $data = array(
        'autonaam' => filter_input(INPUT_POST, 'autonaam', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        'type' => filter_input(INPUT_POST, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        'zitplaatsen' => filter_input(INPUT_POST, 'zitplaatsen', FILTER_SANITIZE_NUMBER_INT),
        'prijs' => filter_input(INPUT_POST, 'prijs', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
        'verhuurder' => $_SESSION['id'],
        'username' => 'E-cars4U',
        'password' => '123'
    );

    // If id is provided, update existing record
    if (!empty($_POST['id'])) {
        $data['id'] = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $url = "https://localhost/E-cars4U/microservices/updateDataApi.php";
        $response = curlRequest($url, 'PUT', $data);
    } else {
        // Otherwise, create new record
        $url = "https://localhost/E-cars4U/microservices/submitApi.php";
        $response = curlRequest($url, 'POST', $data);
    }

    // Redirect to prevent form resubmission on page refresh
    header("Location: dashboard-landlords.php");
    exit();
}

// Handle GET request for deleting record
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_id'])) {
    $url = "https://localhost/E-cars4U/microservices/deleteDataApi.php";
    $data = array(
        'id' => filter_input(INPUT_GET, 'delete_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        'username' => 'E-cars4U',
        'password' => '123'
    );
    $response = curlRequest($url, 'DELETE', $data);
    echo "<p>" . htmlspecialchars($response['message']) . "</p>";
}

// Get data for current user
$url = "https://localhost/E-cars4U/microservices/getDataApi.php";
$data = array(
    'username' => 'E-cars4U',
    'password' => '123',
    'user' => $_SESSION['id']
);
$response = curlRequest($url, 'GET', $data);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beheer Reizen</title>
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0, 0, 0);
            background-color: rgba(0, 0, 0, 0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }
    </style>
</head>

<body>
    <a href="index.php">Home</a>
    <h2>Beheer auto's</h2>
    <button id="openModalBtn">Nieuwe auto Toevoegen</button>
    <br><br>
    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <form action="dashboard-landlords.php" method="post" id="autoform">
                <input type="hidden" id="id" name="id">

                <label for="autonaam">AutoNaam:</label><br>
                <input type="text" id="autonaam" name="autonaam" required><br>

                <label for="type">Type:</label><br>
                <input type="text" id="type" name="type" required><br>

                <label for="zitplaatsen">Zitplaatsen:</label><br>
                <input type="number" id="zitplaatsen" name="zitplaatsen" required><br>

                <label for="prijs">Prijs, Per uur:</label><br>
                <input type="number" id="prijs" name="prijs" required><br>

                <button type="submit">Submit</button>
            </form>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>autoNaam</th>
                <th>Type</th>
                <th>Zitplaatsen</th>
                <th>Prijs, Per uur</th>
                <th>verhuurder</th>
                <th>Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($response['data'])) : ?>
                <?php foreach ($response['data'] as $row) : ?>
                    <tr data-id="<?php echo $row['id']; ?>">
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['autonaam']); ?></td>
                        <td><?php echo htmlspecialchars($row['type']); ?></td>
                        <td><?php echo htmlspecialchars($row['zitplaatsen']); ?></td>
                        <td><?php echo htmlspecialchars($row['prijs']); ?></td>
                        <td><?php echo htmlspecialchars($row['verhuurder']); ?></td>
                        <td>
                            <button onclick="editData(<?php echo $row['id']; ?>)">Edit</button>
                            <a href="dashboard-landlords.php?delete_id=<?php echo $row['id']; ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7">No records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script>
        var modal = document.getElementById("myModal");
        var btn = document.getElementById("openModalBtn");
        var span = document.getElementsByClassName("close")[0];
        var form = document.getElementById("autoform");

        btn.onclick = function() {
            modal.style.display = "block";
        }

        span.onclick = function() {
            modal.style.display = "none";
            form.reset();
            document.getElementById('id').value = '';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
                form.reset();
                document.getElementById('id').value = '';
            }
        }

        function editData(id) {
            var row = document.querySelector(`tr[data-id="${id}"]`);
            document.getElementById('id').value = row.children[0].innerText;
            document.getElementById('autonaam').value = row.children[1].innerText;
            document.getElementById('type').value = row.children[2].innerText;
            document.getElementById('zitplaatsen').value = row.children[3].innerText;
            document.getElementById('prijs').value = row.children[4].innerText;
            modal.style.display = "block";
        }
    </script>
</body>

</html>