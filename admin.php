<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: index.html");
    exit();
}

$host = 'localhost';
$db = 'taco_shop';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    
    $stmt = $conn->prepare("INSERT INTO tacos (name, description, price) VALUES (?, ?, ?)");
    $stmt->bind_param("ssd", $name, $description, $price);
    $stmt->execute();
    $stmt->close();
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $id = $_POST['id'];
    
    $stmt = $conn->prepare("DELETE FROM tacos WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];

    $stmt = $conn->prepare("UPDATE tacos SET name=?, description=?, price=? WHERE id=?");
    $stmt->bind_param("ssdi", $name, $description, $price, $id);
    $stmt->execute();
    $stmt->close();
}

// Selecciona las órdenes de los usuarios
$result = $conn->query("SELECT orders.id, users.username, tacos.name AS taco_name, orders.quantity, orders.total_price 
                        FROM orders 
                        JOIN users ON orders.user_id = users.id
                        JOIN tacos ON orders.taco_id = tacos.id");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Taco Shop</title>
    <style>
        <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logout {
            background-color: #f44336;
            color: #fff;
            border: none;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .logout:hover {
            background-color: #d32f2f;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        .edit-button, .delete-button {
            background-color: #4caf50;
            color: #fff;
            border: none;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .edit-button:hover, .delete-button:hover {
            background-color: #388e3c;
        }
        .form-container {
            margin-top: 20px;
        }
        input[type="text"], textarea, input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
        }
        button[type="submit"] {
            background-color: #008CBA;
            color: #fff;
            border: none;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button[type="submit"]:hover {
            background-color: #005f78;
        }
        .hidden {
            display: none;
        }
    </style>
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome, <?php echo $_SESSION['username']; ?></h1>
        <a class="logout" href="logout.php">Logout</a>
        
        <!-- Sección para mostrar órdenes de usuarios -->
        <h2>User Orders</h2>
        <table>
            <tr>
                <th>Order ID</th>
                <th>User</th>
                <th>Taco</th>
                <th>Quantity</th>
                <th>Total Price</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['username']; ?></td>
                    <td><?php echo $row['taco_name']; ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td><?php echo $row['total_price']; ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
        <!-- Fin de la sección de órdenes de usuarios -->
        
        <!-- Resto del contenido de admin.php -->
        
        <div class="form-container">
            <h2>Add New Taco</h2>
            <form action="admin.php" method="POST">
                <input type="text" name="name" placeholder="Name" required>
                <textarea name="description" placeholder="Description" required></textarea>
                <input type="number" step="0.01" name="price" placeholder="Price" required>
                <button type="submit" name="add">Add Taco</button>
            </form>
        </div>
        
        <h2>Taco List</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
            <?php
            $result = $conn->query("SELECT * FROM tacos");
            while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['description']; ?></td>
                    <td><?php echo $row['price']; ?></td>
                    <td>
                        <form action="admin.php" method="POST" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
<button type="submit" name="delete">Delete</button>
</form>
<button onclick="editTaco(<?php echo $row['id']; ?>, '<?php echo $row['name']; ?>', '<?php echo $row['description']; ?>', <?php echo $row['price']; ?>)">Edit</button>
</td>
</tr>
<?php endwhile; ?>
</table>

<div class="form-container" id="editFormContainer" style="display:none;">
    <h2>Edit Taco</h2>
    <form action="admin.php" method="POST">
        <input type="hidden" name="id" id="editId">
        <input type="text" name="name" id="editName" placeholder="Name" required>
        <textarea name="description" id="editDescription" placeholder="Description" required></textarea>
        <input type="number" step="0.01" name="price" id="editPrice" placeholder="Price" required>
        <button type="submit" name="update">Update Taco</button>
    </form>
</div>
</div>
<script>
function editTaco(id, name, description, price) {
    document.getElementById('editId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editDescription').value = description;
    document.getElementById('editPrice').value = price;
    document.getElementById('editFormContainer').style.display = 'block';
    window.scrollTo(0, document.getElementById('editFormContainer').offsetTop);
}
    </script>
</body>
</html>

</body>
</html>

<?php
$conn->close();
?>
                         
