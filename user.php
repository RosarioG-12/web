<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'user') {
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

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Depuración: Verifica el valor de $user_id
if (is_null($user_id)) {
    die("Error: user_id es nulo. Por favor, asegúrate de que la sesión esté iniciada correctamente.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $taco_id = $_POST['taco_id'];
    $quantity = $_POST['quantity'];

    $stmt = $conn->prepare("SELECT price FROM tacos WHERE id=?");
    $stmt->bind_param("i", $taco_id);
    $stmt->execute();
    $stmt->bind_result($price);
    $stmt->fetch();
    $stmt->close();

    $total_price = $quantity * $price;

    $stmt = $conn->prepare("INSERT INTO orders (user_id, taco_id, quantity, total_price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiid", $user_id, $taco_id, $quantity, $total_price);
    if (!$stmt->execute()) {
        die("Error: " . $stmt->error);
    }
    $stmt->close();
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $id = $_POST['id'];
    
    $stmt = $conn->prepare("DELETE FROM orders WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $stmt->close();
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'];
    $taco_id = $_POST['taco_id'];
    $quantity = $_POST['quantity'];

    $stmt = $conn->prepare("SELECT price FROM tacos WHERE id=?");
    $stmt->bind_param("i", $taco_id);
    $stmt->execute();
    $stmt->bind_result($price);
    $stmt->fetch();
    $stmt->close();

    $total_price = $quantity * $price;

    $stmt = $conn->prepare("UPDATE orders SET taco_id=?, quantity=?, total_price=? WHERE id=? AND user_id=?");
    $stmt->bind_param("iidii", $taco_id, $quantity, $total_price, $id, $user_id);
    $stmt->execute();
    $stmt->close();
}

$tacos = $conn->query("SELECT * FROM tacos");
$orders = $conn->prepare("SELECT orders.id, tacos.name, orders.quantity, orders.total_price 
                        FROM orders 
                        JOIN tacos ON orders.taco_id=tacos.id 
                        WHERE orders.user_id=?");
$orders->bind_param("i", $user_id);
$orders->execute();
$orders_result = $orders->get_result();
$orders->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User - Taco Shop</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            width: 80%;
            margin: 20px auto;
        }
        .logout {
            float: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        .form-container {
            margin: 20px 0;
        }
        .form-container input, .form-container select {
            display: block;
            width: 100%;
            margin: 10px 0;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome, User <?php echo $_SESSION['username']; ?></h1>
        <a class="logout" href="logout.php">Logout</a>
        
        <div class="form-container">
            <h2>Add New Order</h2>
            <form action="user.php" method="POST">
                <select name="taco_id" required>
                    <option value="">Select Taco</option>
                    <?php while ($taco = $tacos->fetch_assoc()): ?>
                        <option value="<?php echo $taco['id']; ?>"><?php echo $taco['name']; ?> - $<?php echo number_format($taco['price'], 2); ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="number" name="quantity" placeholder="Quantity" required>
                <button type="submit" name="add">Add Order</button>
            </form>
        </div>
        
        <h2>Your Orders</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Taco</th>
                <th>Quantity</th>
                <th>Total Price</th>
                <th>Actions</th>
            </tr>
            <?php while ($order = $orders_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $order['id']; ?></td>
                    <td><?php echo $order['name']; ?></td>
                    <td><?php echo $order['quantity']; ?></td>
                    <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                    <td>
                        <form action="user.php" method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                            <button type="submit" name="delete">Delete</button>
                        </form>
                        <button onclick="editOrder(<?php echo $order['id']; ?>, <?php echo $order['taco_id']; ?>, <?php echo $order['quantity']; ?>, '<?php echo $order['total_price']; ?>')">Edit</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>

        <div class="form-container" id="editFormContainer" style="display:none;">
            <h2>Edit Order</h2>
            <form action="user.php" method="POST">
                <input type="hidden" name="id" id="editId">
                <select name="taco_id" id="editTacoId" required>
                    <option value="">Select Taco</option>
                    <?php $tacos->data_seek(0); while ($taco = $tacos->fetch_assoc()): ?>
                        <option value="<?php echo $taco['id']; ?>"><?php echo $taco['name']; ?> - $<?php echo number_format($taco['price'], 2); ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="number" name="quantity" id="editQuantity" placeholder="Quantity" required>
                <button type="submit" name="update">Update Order</button>
            </form>
        </div>
    </div>

    <script>
        function editOrder(id, tacoId, quantity, totalPrice) {
            document.getElementById('editFormContainer').style.display = 'block';
            document.getElementById('editId').value = id;
            document.getElementById('editTacoId').value = tacoId;
            document.getElementById('editQuantity').value = quantity;
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>

