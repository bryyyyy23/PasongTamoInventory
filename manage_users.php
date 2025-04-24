<?php
session_start();
require_once 'db.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Handle user deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $delete_query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "User deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting user: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: manage_users.php");
    exit();
}

// Handle user creation/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'add_user') {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        
        // Check if username already exists
        $check_query = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['message'] = "Username already exists!";
            $_SESSION['message_type'] = "danger";
        } else {
            // Insert new user
            $insert_query = "INSERT INTO users (username, full_name, password, role) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("ssss", $username, $full_name, $password, $role);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "User added successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error adding user: " . $conn->error;
                $_SESSION['message_type'] = "danger";
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'edit_user') {
        $user_id = $_POST['user_id'];
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        
        // Check if username already exists (excluding current user)
        $check_query = "SELECT id FROM users WHERE username = ? AND id != ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("si", $username, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['message'] = "Username already exists!";
            $_SESSION['message_type'] = "danger";
        } else {
            // Update query
            if (!empty($_POST['password'])) {
                // Update with new password
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $update_query = "UPDATE users SET username = ?, full_name = ?, password = ?, role = ? WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("ssssi", $username, $full_name, $password, $role, $user_id);
            } else {
                // Update without changing password
                $update_query = "UPDATE users SET username = ?, full_name = ?, role = ? WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("sssi", $username, $full_name, $role, $user_id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "User updated successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error updating user: " . $conn->error;
                $_SESSION['message_type'] = "danger";
            }
        }
    }
    
    header("Location: manage_users.php");
    exit();
}

// Get user to edit if specified
$edit_user = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $edit_query = "SELECT id, username, full_name, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($edit_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $edit_user = $result->fetch_assoc();
    }
}

// Get all users
$users = $conn->query("SELECT id, username, full_name, role FROM users ORDER BY id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Inventory System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            display: flex;
        }
        
        .sidebar {
            background-color: #0a6b50;
            color: white;
            width: 250px;
            height: 100vh;
            position: fixed;
            padding: 20px 0;
        }
        
        .sidebar-header {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            padding: 15px 25px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: white;
        }
        
        .menu-item:hover, .menu-item.active {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .menu-item i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 30px;
            color: #333;
        }
        
        .form-container, .table-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .form-container h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: #0a6b50;
            outline: none;
            box-shadow: 0 0 0 2px rgba(10, 107, 80, 0.1);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: #0a6b50;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #085540;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
        }
        
        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            position: absolute;
            top: 20px;
            right: 30px;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .user-info .user-name {
            font-weight: 600;
            color: #333;
        }
        
        .user-info .user-role {
            font-size: 12px;
            color: #777;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            margin-right: 5px;
            display: inline-block;
            text-decoration: none;
        }
        
        .edit-btn {
            background-color: #ffc107;
            color: #212529;
        }
        
        .edit-btn:hover {
            background-color: #e0a800;
        }
        
        .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        
        .delete-btn:hover {
            background-color: #c82333;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        @media (max-width: 900px) {
    .stats-container {
        flex-direction: column;
        gap: 15px;
    }
    .main-content {
        padding: 15px;
    }
}

@media (max-width: 700px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        padding: 10px 0;
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
    .sidebar-header {
        padding: 10px;
        border-bottom: none;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .sidebar-header img {
        width: 50px;
        height: 50px;
    }
    .sidebar-menu {
        display: flex;
        flex-direction: row;
        gap: 8px;
        padding: 0 10px;
    }
    .menu-item {
        padding: 10px 12px;
        font-size: 14px;
        margin: 0;
    }
    body {
        flex-direction: column;
    }
    .main-content {
        margin-left: 0;
        padding: 12px;
    }
    .user-info {
        position: static;
        margin-bottom: 15px;
    }
}

@media (max-width: 500px) {
    .sidebar-header h3 {
        font-size: 16px;
    }
    .page-title {
        font-size: 18px;
    }
    .stat-card {
        flex-direction: column;
        align-items: flex-start;
        padding: 15px;
    }
    .stat-icon {
        margin-right: 0;
        margin-bottom: 10px;
        width: 40px;
        height: 40px;
        font-size: 18px;
    }
    .stat-details h3 {
        font-size: 18px;
    }
    .stat-details p {
        font-size: 12px;
    }
    .table-container {
        padding: 10px;
    }
    table th, table td {
        padding: 8px 6px;
        font-size: 12px;
    }
}

    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="images/logo.jpg" alt="School Logo">
            <h3>Inventory System</h3>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="profile.php" class="menu-item">
                <i class="fas fa-user"></i> Profile
            </a>
            <a href="categories.php" class="menu-item">
                <i class="fas fa-th-large"></i> Categories
            </a>
            <a href="products.php" class="menu-item">
                <i class="fas fa-boxes"></i> Products
            </a>
            <a href="inventory.php" class="menu-item">
                <i class="fas fa-warehouse"></i> Inventory
            </a>
            <a href="manage_users.php" class="menu-item active">
                <i class="fas fa-users-cog"></i> Manage Users
            </a>
            <a href="logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="user-info">
            <img src="images/profile.svg" alt="User">
            <div>
                <div class="user-name"><?php echo $_SESSION['full_name']; ?></div>
                <div class="user-role"><?php echo ucfirst($_SESSION['role']); ?></div>
            </div>
            <a href="logout.php" class="logout-btn" style="color: #d9534f; margin-left: 15px; text-decoration: none;">Logout</a>
        </div>
        
        <h1 class="page-title">Manage Users</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
            <?php 
                echo $_SESSION['message']; 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            ?>
        </div>
        <?php endif; ?>
        
        <div class="form-container">
            <h2><?php echo $edit_user ? 'Edit User' : 'Add New User'; ?></h2>
            <form action="manage_users.php" method="post">
                <?php if ($edit_user): ?>
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                <?php else: ?>
                <input type="hidden" name="action" value="add_user">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required 
                           value="<?php echo $edit_user ? $edit_user['username'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required 
                           value="<?php echo $edit_user ? $edit_user['full_name'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password"><?php echo $edit_user ? 'Password (leave blank to keep current)' : 'Password'; ?></label>
                    <input type="password" class="form-control" id="password" name="password" 
                           <?php echo $edit_user ? '' : 'required'; ?>>
                </div>
                
                <div class="form-group">
                    <label for="role">Role</label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="user" <?php echo ($edit_user && $edit_user['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo ($edit_user && $edit_user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $edit_user ? 'Update User' : 'Add User'; ?>
                    </button>
                    <?php if ($edit_user): ?>
                    <a href="manage_users.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="table-container">
            <h2>Current Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo $user['username']; ?></td>
                        <td><?php echo $user['full_name']; ?></td>
                        <td><?php echo ucfirst($user['role']); ?></td>
                        <td>
                            <a href="manage_users.php?action=edit&id=<?php echo $user['id']; ?>" class="action-btn edit-btn">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <?php if ($user['id'] != $_SESSION['user_id']): // Prevent self-deletion ?>
                            <a href="manage_users.php?action=delete&id=<?php echo $user['id']; ?>" 
                               onclick="return confirm('Are you sure you want to delete this user?');" 
                               class="action-btn delete-btn">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>