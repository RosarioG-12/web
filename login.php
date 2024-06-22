<?php
session_start();

$host = 'localhost';
$db = 'taco_shop';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = hash('sha256', $_POST['password']);
    
    $stmt = $conn->prepare("SELECT id, role FROM users WHERE username=? AND password=?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $role);
        $stmt->fetch();
        $_SESSION['username'] = $username;
        $_SESSION['user_id'] = $user_id; // Asigna el ID de usuario a la sesión
        $_SESSION['role'] = $role;
        
        if ($role == 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: user.php");
        }
    } else {
        echo "Invalid username or password";
    }
    
    $stmt->close();
}
$conn->close();
?>
