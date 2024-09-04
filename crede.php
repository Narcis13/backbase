<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST["password"] ?? "";
    
    if (!empty($password)) {
        // Hash the password using bcrypt
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        // Save the hashed password to c.pwd
        file_put_contents("c.pwd", $hashed_password);
        
        echo "Password has been hashed and saved successfully.";
    } else {
        echo "Please enter a password.";
    }
}
?>

<form method="POST">
    <input type="password" name="password" required>
    <input type="submit" value="Save Hashed Password">
</form>