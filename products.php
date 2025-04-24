<?php
session_start();
require_once 'db.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Get filter and sort parameters
$category_filter = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Process product operations
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add new product
    if (isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $category_id = $_POST['category_id'];
        $quantity = $_POST['quantity'];
        $unit_price = $_POST['unit_price'];
        
        $stmt = $conn->prepare("INSERT INTO products (name, description, category_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiid", $name, $description, $category_id, $quantity, $unit_price);
        $stmt->execute();
        $success = "Product added successfully!";
    }
    
    // Edit product
    if (isset($_POST['edit_product'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $category_id = $_POST['category_id'];
        $quantity = $_POST['quantity'];
        $unit_price = $_POST['unit_price'];
        
        $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, category_id = ?, quantity = ?, unit_price = ?, last_updated = CURRENT_TIMESTAMP() WHERE id = ?");
        $stmt->bind_param("ssiidi", $name, $description, $category_id, $quantity, $unit_price, $id);
        $stmt->execute();
        $success = "Product updated successfully!";
    }
    
    // Delete product
    if (isset($_POST['delete_product'])) {
        $id = $_POST['id'];
        
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = "Product deleted successfully!";
    }
}

// Build query based on filters and sorting
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id";
          
$where_clause = "";
if ($category_filter > 0) {
    $where_clause = " WHERE p.category_id = " . $category_filter;
}

$order_clause = " ORDER BY ";
switch ($sort_by) {
    case 'date':
        $order_clause .= "p.date_added";
        break;
    case 'quantity':
        $order_clause .= "p.quantity";
        break;
    case 'name':
    default:
        $order_clause .= "p.name";
        break;
}
$order_clause .= " " . ($sort_order === 'desc' ? 'DESC' : 'ASC');

$products = $conn->query($query . $where_clause . $order_clause);

// Get all categories for dropdown
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name");
$categoriesArray = array();
while($cat = $categories->fetch_assoc()) {
    $categoriesArray[] = $cat;
}

// Get selected category name for display
$selected_category_name = "";
if ($category_filter > 0) {
    $stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_filter);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $selected_category_name = $row['name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Inventory System</title>
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
            position: relative;
        }
        
        table th.sortable {
            cursor: pointer;
            user-select: none;
        }
        
        table th.sortable:hover {
            background-color: #eee;
        }
        
        table th.sortable i {
            margin-left: 5px;
            font-size: 12px;
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
        
        .btn-warning {
            background-color: #ffc107;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .filter-section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
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
            margin: 5% auto;
            padding: 30px;
            border-radius: 8px;
            width: 600px;
            max-width: 90%;
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
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
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
            <h1>Products <?php echo $selected_category_name ? "- " . htmlspecialchars($selected_category_name) : ""; ?></h1>
            <button class="btn" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add Product
            </button>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Filter and Sort Section -->
        <div class="filter-section">
            <div class="filter-group">
                <label for="category_filter">Filter by Category:</label>
                <select id="category_filter" class="filter-select" onchange="applyFilter()">
                    <option value="0">All Categories</option>
                    <?php foreach ($categoriesArray as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="sort_by">Sort by:</label>
                <select id="sort_by" class="filter-select" onchange="applySort()">
                    <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Name</option>
                    <option value="date" <?php echo $sort_by == 'date' ? 'selected' : ''; ?>>Date Added</option>
                    <option value="quantity" <?php echo $sort_by == 'quantity' ? 'selected' : ''; ?>>Quantity</option>
                </select>
                
                <select id="sort_order" class="filter-select" onchange="applySort()">
                    <option value="asc" <?php echo $sort_order == 'asc' ? 'selected' : ''; ?>>Ascending</option>
                    <option value="desc" <?php echo $sort_order == 'desc' ? 'selected' : ''; ?>>Descending</option>
                </select>
            </div>
        </div>
        
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Date Added</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products->num_rows > 0): ?>
                        <?php while($product = $products->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td><?php echo $product['quantity']; ?></td>
                                <td>$<?php echo number_format($product['unit_price'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($product['date_added'])); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($product['last_updated'])); ?></td>
                                <td class="table-actions">
                                    <button class="btn btn-sm btn-warning" onclick="openEditModal(
                                        <?php echo $product['id']; ?>, 
                                        '<?php echo addslashes($product['name']); ?>', 
                                        '<?php echo addslashes($product['description']); ?>', 
                                        <?php echo $product['category_id']; ?>, 
                                        <?php echo $product['quantity']; ?>, 
                                        <?php echo $product['unit_price']; ?>
                                    )">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="openDeleteModal(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
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
    </div>
    
    <!-- Add Product Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add Product</h2>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categoriesArray as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" min="0" value="0" required>
                    </div>
                    <div class="form-group">
                        <label for="unit_price">Unit Price ($)</label>
                        <input type="number" id="unit_price" name="unit_price" class="form-control" min="0" step="0.01" value="0.00" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" style="background-color: #6c757d;" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" name="add_product" class="btn">Add Product</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Product Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Product</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_name">Product Name</label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_category_id">Category</label>
                    <select id="edit_category_id" name="category_id" class="form-control" required>
                        <?php foreach ($categoriesArray as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_quantity">Quantity</label>
                        <input type="number" id="edit_quantity" name="quantity" class="form-control" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_unit_price">Unit Price ($)</label>
                        <input type="number" id="edit_unit_price" name="unit_price" class="form-control" min="0" step="0.01" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" style="background-color: #6c757d;" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="edit_product" class="btn">Update Product</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Product Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Delete Product</h2>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" id="delete_id" name="id">
                <p>Are you sure you want to delete product "<span id="delete_name"></span>"?</p>
                <p style="color: #dc3545; margin-top: 10px;">This action cannot be undone!</p>
                <div class="modal-footer">
                    <button type="button" class="btn" style="background-color: #6c757d;" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" name="delete_product" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Filter and Sort Functions
        function applyFilter() {
            const category_id = document.getElementById('category_filter').value;
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('category_id', category_id);
            // Keep the current sort and order when changing filter
            if (currentUrl.searchParams.has('sort')) {
                const sort = currentUrl.searchParams.get('sort');
                const order = currentUrl.searchParams.get('order');
                currentUrl.searchParams.set('sort', sort);
                currentUrl.searchParams.set('order', order);
            }
            window.location.href = currentUrl.toString();
        }
        
        function applySort() {
            const sort_by = document.getElementById('sort_by').value;
            const sort_order = document.getElementById('sort_order').value;
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('sort', sort_by);
            currentUrl.searchParams.set('order', sort_order);
            // Keep the current category filter when changing sort
            if (currentUrl.searchParams.has('category_id')) {
                const category_id = currentUrl.searchParams.get('category_id');
                currentUrl.searchParams.set('category_id', category_id);
            }
            window.location.href = currentUrl.toString();
        }
        
        // Add Product Modal Functions
        function openAddModal() {
            document.getElementById("addModal").style.display = "block";
        }
        
        function closeAddModal() {
            document.getElementById("addModal").style.display = "none";
        }
        
        // Edit Product Modal Functions
        function openEditModal(id, name, description, category_id, quantity, unit_price) {
            document.getElementById("edit_id").value = id;
            document.getElementById("edit_name").value = name;
            document.getElementById("edit_description").value = description;
            document.getElementById("edit_category_id").value = category_id;
            document.getElementById("edit_quantity").value = quantity;
            document.getElementById("edit_unit_price").value = unit_price;
            document.getElementById("editModal").style.display = "block";
        }
        
        function closeEditModal() {
            document.getElementById("editModal").style.display = "none";
        }
        
        // Delete Product Modal Functions
        function openDeleteModal(id, name) {
            document.getElementById("delete_id").value = id;
            document.getElementById("delete_name").innerHTML = name;
            document.getElementById("deleteModal").style.display = "block";
        }
        
        function closeDeleteModal() {
            document.getElementById("deleteModal").style.display = "none";
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById("addModal")) {
                closeAddModal();
            }
            if (event.target == document.getElementById("editModal")) {
                closeEditModal();
            }
            if (event.target == document.getElementById("deleteModal")) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>