<?php
// Add this to the top of categories.php
session_start();
require_once 'db.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Process category operations
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add new category
    if (isset($_POST['add_category'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        $stmt->execute();
        $success = "Category added successfully!";
    }
    
    // Edit category
    if (isset($_POST['edit_category'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $description, $id);
        $stmt->execute();
        $success = "Category updated successfully!";
    }
    
    // Delete category
    if (isset($_POST['delete_category'])) {
        $id = $_POST['id'];
        
        // Check if category is in use
        $check = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            $error = "Cannot delete category. It is associated with products.";
        } else {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $success = "Category deleted successfully!";
        }
    }
}

// Get all categories with product count
$categories = $conn->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Inventory System</title>
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
        
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .category-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .category-card-body {
            padding: 20px;
            cursor: pointer;
            height: 200px;
            display: flex;
            flex-direction: column;
        }
        
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .category-header h3 {
            color: #333;
            font-size: 20px;
            margin: 0;
            flex: 1;
        }
        
        .card-actions {
            display: flex;
            gap: 8px;
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
        
        .category-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            flex-grow: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        
        .category-footer {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .product-count {
            color: #0a6b50;
            font-weight: 600;
        }
        
        .category-date {
            color: #999;
            font-size: 12px;
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
            <h1>Categories</h1>
            <button class="btn" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add Category
            </button>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="grid-container">
            <?php if ($categories->num_rows > 0): ?>
                <?php while($category = $categories->fetch_assoc()): ?>
                    <div class="category-card">
                        <div class="category-card-body" onclick="window.location.href='products.php?category_id=<?php echo $category['id']; ?>'">
                            <div class="category-header">
                                <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                <div class="card-actions" onclick="event.stopPropagation();">
                                    <button class="btn btn-sm btn-warning" onclick="openEditModal(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>', '<?php echo addslashes($category['description']); ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="openDeleteModal(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="category-description">
                                <?php echo $category['description'] ? htmlspecialchars($category['description']) : 'No description available.'; ?>
                            </div>
                            <div class="category-footer">
                                <span class="product-count">
                                    <i class="fas fa-box"></i> <?php echo $category['product_count']; ?> Products
                                </span>
                                <span class="category-date">
                                    Created: <?php echo date('M d, Y', strtotime($category['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px 0; color: #666;">
                    No categories found. Click "Add Category" to create your first category.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Category Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add Category</h2>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="name">Category Name</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" style="background-color: #6c757d;" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" name="add_category" class="btn">Add Category</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Category</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_name">Category Name</label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" class="form-control" rows="4"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" style="background-color: #6c757d;" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="edit_category" class="btn">Update Category</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Category Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Delete Category</h2>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" id="delete_id" name="id">
                <p>Are you sure you want to delete category "<span id="delete_name"></span>"?</p>
                <p style="color: #dc3545; margin-top: 10px;">This action cannot be undone!</p>
                <div class="modal-footer">
                    <button type="button" class="btn" style="background-color: #6c757d;" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" name="delete_category" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Add Category Modal Functions
        function openAddModal() {
            document.getElementById("addModal").style.display = "block";
        }
        
        function closeAddModal() {
            document.getElementById("addModal").style.display = "none";
        }
        
        // Edit Category Modal Functions
        function openEditModal(id, name, description) {
            document.getElementById("edit_id").value = id;
            document.getElementById("edit_name").value = name;
            document.getElementById("edit_description").value = description;
            document.getElementById("editModal").style.display = "block";
        }
        
        function closeEditModal() {
            document.getElementById("editModal").style.display = "none";
        }
        
        // Delete Category Modal Functions
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