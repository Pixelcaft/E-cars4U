<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

include_once("IdP-map/IdP.php");

function getToken()
{
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

function curlRequest($url, $method, $data = null)
{
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
        'autonaam' => $_POST['autonaam'],
        'type' => $_POST['type'],
        'zitplaatsen' => $_POST['zitplaatsen'],
        'prijs' => $_POST['prijs'],
        'verhuurder' => $_SESSION['id'], // Voeg deze regel toe
        'username' => 'E-cars4U',
        'password' => '123'
    );

    echo "dit de data ->";
    print_r($data);

    if (!empty($_POST['id'])) {
        $data['id'] = $_POST['id'];
        $url = "https://localhost/E-cars4U/microservices/updateDataApi.php";
        $response = curlRequest($url, 'PUT', $data);
    } else {
        $url = "https://localhost/E-cars4U/microservices/submitApi.php";
        $response = curlRequest($url, 'POST', $data);
    }

    // Redirect om te voorkomen dat het formulier opnieuw wordt verzonden bij paginavernieuwing
    header("Location: dashboard-landlords.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_id'])) {
    $url = "https://localhost/E-cars4U/microservices/deleteDataApi.php";
    $data = array('id' => $_GET['delete_id'], 'username' => 'E-cars4U', 'password' => '123');
    $response = curlRequest($url, 'DELETE', $data);
    echo "<p>" . htmlspecialchars($response['message']) . "</p>";
}

$url = "https://localhost/E-cars4U/microservices/getDataApi.php";
$data = array('username' => 'E-cars4U', 'password' => '123', 'user' => $_SESSION['id']);
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
    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <form action="dashboard-landlords.php" method="post" id="autoform">
                <input type="hidden" id="id" name="id">

                <label for="autonaam">AutoNaam:</label><br>
                <input type="text" id="autonaam" name="autonaam" required><br>

                <label for="type">Type:</label><br>
                <input type="text" id="type" name="type" required><br><br>

                <label for="zitplaatsen">Zitplaatsen:</label><br>
                <input type="number" id="zitplaatsen" name="zitplaatsen" required><br>

                <label for="prijs">Prijs:</label><br>
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
                <th>Prijs</th>
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