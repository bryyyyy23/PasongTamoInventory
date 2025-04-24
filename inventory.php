<?php
session_start();
require_once 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Process inventory operations
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add inventory transaction
    if (isset($_POST['add_transaction'])) {
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        $type = $_POST['type'];
        $notes = $_POST['notes'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert inventory transaction
            $stmt = $conn->prepare("INSERT INTO inventory_transactions (product_id, quantity, type, notes, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iissi", $product_id, $quantity, $type, $notes, $_SESSION['user_id']);
            $stmt->execute();
            
            // Update product quantity
            if ($type == 'in') {
                $update = $conn->prepare("UPDATE products SET quantity = quantity + ?, last_updated = CURRENT_TIMESTAMP() WHERE id = ?");
            } else {
                $update = $conn->prepare("UPDATE products SET quantity = quantity - ?, last_updated = CURRENT_TIMESTAMP() WHERE id = ?");
            }
            $update->bind_param("ii", $quantity, $product_id);
            $update->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Store success message in session
            $_SESSION['success_message'] = "Inventory transaction recorded successfully!";
            
            // Redirect to prevent form resubmission
            header("Location: inventory.php");
            exit();
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            
            // Store error message in session
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
            
            // Redirect to prevent form resubmission
            header("Location: inventory.php");
            exit();
        }
    }
}

// Display messages from session
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the message after displaying
}

if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Clear the message after displaying
}

// Get all products with their current inventory levels
$products = $conn->query("SELECT p.id, p.name, p.quantity, p.unit_price, c.name as category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         ORDER BY p.name");

// Get recent inventory transactions
$transactions = $conn->query("SELECT t.id, t.product_id, p.name as product_name, t.quantity, t.type, 
                             t.notes, t.created_at, u.username as created_by
                             FROM inventory_transactions t
                             JOIN products p ON t.product_id = p.id
                             JOIN users u ON t.created_by = u.id
                             ORDER BY t.created_at DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Inventory System</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn {
            background-color: #0a6b50;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 15px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn:hover {
            background-color: #085540;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
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
        
        .table-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 6px 10px;
            font-size: 12px;
        }
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .inventory-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
        }
        
        .stat-info h3 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            color: #6c757d;
            margin: 0;
        }
        
        .text-danger {
            color: #dc3545;
        }
        
        .bg-primary {
            background-color: rgba(10, 107, 80, 0.1);
            color: #0a6b50;
        }
        
        .bg-warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .bg-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 8px;
            width: 500px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #333;
        }
        
        .modal-footer {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #0a6b50;
            outline: none;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
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
        <div class="page-title">
            <h1>Inventory Management</h1>
            <button class="btn" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Record Transaction
            </button>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php
        // Calculate summary statistics
        $total_products = 0;
        $low_stock_count = 0;
        $out_of_stock_count = 0;
        $threshold = 10; // Low stock threshold
        
        if ($products->num_rows > 0) {
            $products_data = [];
            while($row = $products->fetch_assoc()) {
                $products_data[] = $row;
                $total_products++;
                if ($row['quantity'] == 0) {
                    $out_of_stock_count++;
                } else if ($row['quantity'] <= $threshold) {
                    $low_stock_count++;
                }
            }
            // Reset the pointer to beginning
            $products->data_seek(0);
        }
        ?>
        
        <div class="inventory-stats">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_products; ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $low_stock_count; ?></h3>
                    <p>Low Stock Items</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $out_of_stock_count; ?></h3>
                    <p>Out of Stock Items</p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Current Inventory</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total Value</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products->num_rows > 0): ?>
                        <?php while($product = $products->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['category_name']; ?></td>
                                <td><?php echo $product['quantity']; ?></td>
                                <td>$<?php echo number_format($product['unit_price'], 2); ?></td>
                                <td>$<?php echo number_format($product['quantity'] * $product['unit_price'], 2); ?></td>
                                <td>
                                    <?php if ($product['quantity'] == 0): ?>
                                        <span class="badge badge-danger">Out of Stock</span>
                                    <?php elseif ($product['quantity'] <= $threshold): ?>
                                        <span class="badge badge-danger">Low Stock</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <button class="btn btn-sm btn-success" onclick="openAddWithProduct(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', 'in')">
                                        <i class="fas fa-plus"></i> Stock In
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="openAddWithProduct(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', 'out')" <?php echo ($product['quantity'] == 0) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-minus"></i> Stock Out
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">No products found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Transactions</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Notes</th>
                        <th>Recorded By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transactions->num_rows > 0): ?>
                        <?php while($transaction = $transactions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $transaction['id']; ?></td>
                                <td><?php echo $transaction['product_name']; ?></td>
                                <td>
                                    <?php if ($transaction['type'] == 'in'): ?>
                                        <span class="badge badge-success">Stock In</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Stock Out</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $transaction['quantity']; ?></td>
                                <td><?php echo $transaction['notes']; ?></td>
                                <td><?php echo $transaction['created_by']; ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No recent transactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add Transaction Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="transaction-title">Record Inventory Transaction</h2>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="product_id">Product</label>
                    <select id="product_id" name="product_id" class="form-control" required>
                        <option value="">Select Product</option>
                        <?php 
                        $products->data_seek(0);
                        while($product = $products->fetch_assoc()): ?>
                            <option value="<?php echo $product['id']; ?>">
                                <?php echo $product['name']; ?> (Current Stock: <?php echo $product['quantity']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="type">Transaction Type</label>
                    <select id="type" name="type" class="form-control" required>
                        <option value="in">Stock In</option>
                        <option value="out">Stock Out</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" class="form-control" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" style="background-color: #6c757d;" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" name="add_transaction" class="btn">Record Transaction</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Add Transaction Modal Functions
        function openAddModal() {
            document.getElementById("transaction-title").textContent = "Record Inventory Transaction";
            document.getElementById("addModal").style.display = "block";
        }
        
        function openAddWithProduct(productId, productName, type) {
            document.getElementById("transaction-title").textContent = type === 'in' ? "Stock In: " + productName : "Stock Out: " + productName;
            document.getElementById("product_id").value = productId;
            document.getElementById("type").value = type;
            document.getElementById("addModal").style.display = "block";
        }
        
        function closeAddModal() {
            document.getElementById("addModal").style.display = "none";
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById("addModal")) {
                closeAddModal();
            }
        }
    </script>
</body>
</html>