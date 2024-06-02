<?php
include_once("IdP-map/IdP.php");

function getToken() {
    $username = "E-cars4U";
    $password = "123";
    $credentials = array(
        'username' => $username,
        'password' => $password,
        'exp' => time() + (60 * 60)
    );

    $idp = new IdP($credentials);
    return $idp->getToken();
}

function curlRequest($url, $method, $data = null) {
    $token = getToken();
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_USERPWD, "username:password");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $token, "Content-Type: application/json"));
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    if ($response === false) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        die("cURL error: $error_msg");
    }

    curl_close($ch);
    return json_decode($response, true);
}

// CRUD-acties
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = array(
        'naam' => $_POST['naam'],
        'achternaam' => $_POST['achternaam'],
        'prijs' => $_POST['prijs'],
        'zitplaatsen' => $_POST['zitplaatsen'],
        'type' => $_POST['type'],
        'username' => 'E-cars4U',
        'password' => '123'
    );

    if (!empty($_POST['id'])) {
        $data['id'] = $_POST['id'];
        $url = "https://localhost/E-cars4U/microservices/updateDataApi.php";
        $response = curlRequest($url, 'PUT', $data);
    } else {
        $url = "https://localhost/E-cars4U/microservices/submitApi.php";
        $response = curlRequest($url, 'POST', $data);
    }

    // Redirect om te voorkomen dat het formulier opnieuw wordt verzonden bij paginavernieuwing
    header("Location: manageData.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_id'])) {
    $url = "https://localhost/E-cars4U/microservices/deleteDataApi.php";
    $data = array('id' => $_GET['delete_id'], 'username' => 'E-cars4U', 'password' => '123');
    $response = curlRequest($url, 'DELETE', $data);
    echo "<p>" . htmlspecialchars($response['message']) . "</p>";
}

$url = "https://localhost/E-cars4U/microservices/getDataApi.php";
$data = array('username' => 'E-cars4U', 'password' => '123');
$response = curlRequest($url, 'GET', $data);
// echo "<pre>";
// print_r($response);
// echo "</pre>";
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
    </style>
</head>
<body>
    <h2>Beheer Reizen</h2>
    <button id="openModalBtn">Nieuwe Reis Toevoegen</button>
    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <form action="manageData.php" method="post" id="reisForm">
                <input type="hidden" id="id" name="id">
                <label for="naam">Naam:</label><br>
                <input type="text" id="naam" name="naam" required><br>
                <label for="achternaam">Achternaam:</label><br>
                <input type="text" id="achternaam" name="achternaam" required><br>
                <label for="prijs">Prijs:</label><br>
                <input type="number" id="prijs" name="prijs" required><br>
                <label for="zitplaatsen">Zitplaatsen:</label><br>
                <input type="number" id="zitplaatsen" name="zitplaatsen" required><br>
                <label for="type">Type:</label><br>
                <input type="text" id="type" name="type" required><br><br>
                <button type="submit">Submit</button>
            </form>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Naam</th>
                <th>Achternaam</th>
                <th>Prijs</th>
                <th>Zitplaatsen</th>
                <th>Type</th>
                <th>Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($response['data'])): ?>
                <?php foreach ($response['data'] as $row): ?>
                    <tr data-id="<?php echo $row['id']; ?>">
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['naam']); ?></td>
                        <td><?php echo htmlspecialchars($row['achternaam']); ?></td>
                        <td><?php echo htmlspecialchars($row['prijs']); ?></td>
                        <td><?php echo htmlspecialchars($row['zitplaatsen']); ?></td>
                        <td><?php echo htmlspecialchars($row['type']); ?></td>
                        <td>
                            <button onclick="editData(<?php echo $row['id']; ?>)">Edit</button>
                            <a href="manageData.php?delete_id=<?php echo $row['id']; ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
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
        var form = document.getElementById("reisForm");

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
            document.getElementById('naam').value = row.children[1].innerText;
            document.getElementById('achternaam').value = row.children[2].innerText;
            document.getElementById('prijs').value = row.children[3].innerText;
            document.getElementById('zitplaatsen').value = row.children[4].innerText;
            document.getElementById('type').value = row.children[5].innerText;
            modal.style.display = "block";
        }
    </script>
</body>
</html>