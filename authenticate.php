<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Validate input
    if (empty($username) || empty($password)) {
        header("Location: login.php?error=Both+fields+are+required");
        exit();
    }
    
    try {
        // Retrieve admin user
        $stmt = $conn->prepare("SELECT id, username, password FROM admin_users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if (password_verify($password, $admin['password'])) {
                // Successful login
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_logged_in'] = true;
                
                // Regenerate session ID
                session_regenerate_id(true);
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            }
        }
        
        // Failed login
        header("Location: login.php?error=Invalid+credentials");
        exit();
        
    } catch(PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        header("Location: login.php?error=System+error");
        exit();
    }
}