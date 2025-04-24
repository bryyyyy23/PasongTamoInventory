<?php
session_start();
require_once 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get counts for dashboard
$user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$product_count = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$category_count = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'];

// Get recent products
$recent_products = $conn->query("SELECT p.*, c.name as category_name FROM products p 
                                LEFT JOIN categories c ON p.category_id = c.id 
                                ORDER BY p.date_added DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory System</title>
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
        
        .menu-item.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .menu-item.disabled:hover {
            background-color: transparent;
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
        
        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            flex: 1;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            background-color: #e9f5f2;
            color: #0a6b50;
            height: 60px;
            width: 60px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 20px;
        }
        
        .stat-details h3 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #333;
        }
        
        .stat-details p {
            color: #777;
            font-size: 14px;
        }
        
        .table-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .table-container h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
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
        
        .logout-btn {
            margin-left: 15px;
            color: #d9534f;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
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
    <a href="dashboard.php" class="menu-item active">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a href="profile.php" class="menu-item">
        <i class="fas fa-user"></i> Profile
    </a>
    <?php if ($_SESSION['role'] == 'admin'): ?>
    <a href="categories.php" class="menu-item">
        <i class="fas fa-th-large"></i> Categories
    </a>
    <a href="products.php" class="menu-item">
        <i class="fas fa-boxes"></i> Products
    </a>
    <?php endif; ?>
    <a href="inventory.php" class="menu-item">
        <i class="fas fa-warehouse"></i> Inventory
    </a>
    <?php if ($_SESSION['role'] == 'admin'): ?>
    <a href="manage_users.php" class="menu-item">
        <i class="fas fa-users-cog"></i> Manage Users
    </a>
    <?php endif; ?>
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
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
        
        <h1 class="page-title">Dashboard</h1>
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $user_count; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $product_count; ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-th-large"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $category_count; ?></h3>
                    <p>Total Categories</p>
                </div>
            </div>
        </div>
        
        <div class="table-container">
            <h2>Recent Products</h2>
            <table>
                <thead>
                    <tr>
                        <th>Name of the Product</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Total Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($product = $recent_products->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $product['name']; ?></td>
                        <td><?php echo $product['category_name']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($product['date_added'])); ?></td>
                        <td><?php echo $product['quantity']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>