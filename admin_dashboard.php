<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: home.php");
    exit;
}

// ======= MESSAGE HANDLING =======
$message = '';
$error = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// ======= DATABASE QUERIES =======
// Fetch all visitors
$visitors_result = $conn->query("SELECT * FROM visitors ORDER BY id DESC");

// Fetch AR content
$content_result = $conn->query("SELECT * FROM ar_content ORDER BY created_at DESC");

// Fetch feedback
$feedback_result = $conn->query("SELECT * FROM feedback ORDER BY id DESC");

// Fetch stats for dashboard
$total_visitors = $conn->query("SELECT COUNT(*) as count FROM visitors")->fetch_assoc()['count'];
$total_content = $conn->query("SELECT COUNT(*) as count FROM ar_content")->fetch_assoc()['count'];
$total_feedback = $conn->query("SELECT COUNT(*) as count FROM feedback")->fetch_assoc()['count'];
$month_visitors = $conn->query("SELECT COUNT(*) as count FROM visitors WHERE MONTH(visit_date) = MONTH(CURRENT_DATE())")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | AR Museum</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0a3d2f;
            --secondary-color: #09735a;
            --accent-color: #00ff66;
            --light-color: #f5f5f5;
            --dark-color: #333;
            --sidebar-width: 250px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f0f2f5;
            color: var(--dark-color);
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--primary-color);
            color: white;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .menu-item:hover, .menu-item.active {
            background-color: rgba(255,255,255,0.1);
            border-left: 4px solid var(--accent-color);
        }
        
        .menu-item i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: margin-left 0.3s;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0 20px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .header h1 {
            color: var(--primary-color);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-logout {
            background: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-logout:hover {
            background: var(--secondary-color);
        }
        
        /* Page Styles - Only active page is visible */
        .page {
            display: none;
        }
        
        .page.active {
            display: block;
        }
        
        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .card .number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--secondary-color);
        }
        
        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: var(--secondary-color);
            color: white;
            font-weight: 600;
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .btn-action {
            padding: 5px 10px;
            background: var(--accent-color);
            color: var(--dark-color);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-right: 5px;
        }
        
        .btn-delete {
            background: #ff4d4d;
            color: white;
        }
        
        /* Content Management Styles */
        .upload-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .upload-area {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            margin: 20px 0;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .upload-area:hover, .upload-area.dragover {
            border-color: var(--secondary-color);
            background-color: rgba(9, 115, 90, 0.05);
        }
        
        .upload-area i {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .file-upload-group {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .file-upload-group h4 {
            color: var(--secondary-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .file-preview {
            margin-top: 10px;
            display: none;
        }
        
        .file-preview img, .file-preview video {
            max-width: 200px;
            max-height: 150px;
            border-radius: 5px;
        }
        
        .btn-primary {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: var(--primary-color);
        }
        
        .content-list {
            margin-top: 20px;
        }
        
        .content-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            background: white;
        }
        
        .content-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .content-icon {
            font-size: 2rem;
            color: var(--secondary-color);
        }
        
        .content-details h4 {
            margin-bottom: 5px;
        }
        
        .content-details p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .content-actions button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            margin-left: 10px;
        }
        
        .btn-edit {
            color: var(--secondary-color);
        }
        
        .btn-delete {
            color: #ff4d4d;
        }
        
        /* Alert Styles */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .alert i {
            margin-right: 10px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: visible;
            }
            
            .sidebar-header h2, .sidebar-header p, .menu-item span {
                display: none;
            }
            
            .menu-item {
                justify-content: center;
                padding: 15px;
            }
            
            .menu-item i {
                margin-right: 0;
                font-size: 1.5rem;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
        
        /* Toggle for mobile */
        .menu-toggle {
            display: none;
            background: var(--primary-color);
            color: white;
            border: none;
            font-size: 1.5rem;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>AR Museum</h2>
            <p>Admin Dashboard</p>
        </div>
        <div class="sidebar-menu">
            <a href="#" class="menu-item active" data-page="dashboard">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" class="menu-item" data-page="content-management">
                <i class="fas fa-cube"></i>
                <span>Content Management</span>
            </a>
            <a href="#" class="menu-item" data-page="visitors">
                <i class="fas fa-users"></i>
                <span>Visitors</span>
            </a>
            <a href="#" class="menu-item" data-page="feedback">
                <i class="fas fa-comment-alt"></i>
                <span>Feedback</span>
            </a>
            <a href="admin_logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="header">
            <h1>Admin Dashboard</h1>
            <div class="user-info">
                <span>Welcome, Admin</span>
                <a href="admin_logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Display Messages -->
        <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <!-- Dashboard Page -->
        <div id="dashboard" class="page active">
            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <div class="card">
                    <h3>Total Visitors</h3>
                    <div class="number"><?php echo $total_visitors; ?></div>
                    <p>This month: <?php echo $month_visitors; ?></p>
                </div>
                <div class="card">
                    <h3>AR Exhibits</h3>
                    <div class="number"><?php echo $total_content; ?></div>
                    <p>Active: <?php echo $total_content; ?></p>
                </div>
                <div class="card">
                    <h3>Feedback</h3>
                    <div class="number"><?php echo $total_feedback; ?></div>
                    <p>Avg. Rating: 4.7/5</p>
                </div>
                <div class="card">
                    <h3>Upcoming Events</h3>
                    <div class="number">3</div>
                    <p>Next: Aug 15</p>
                </div>
            </div>

            <!-- Visitor Registrations Table -->
            <div class="table-container">
                <h2>Visitor Registrations</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Contact</th>
                            <th>School</th>
                            <th>Visit Date</th>
                            <th>Visit Time</th>
                            <th>Purpose</th>
                            <th>Visitor ID</th>
                            <!-- TANGGAL NA ANG ACTIONS COLUMN -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($visitors_result && $visitors_result->num_rows > 0): ?>
                            <?php while($row = $visitors_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($row['contact']); ?></td>
                                <td><?php echo htmlspecialchars($row['school']); ?></td>
                                <td><?php echo $row['visit_date']; ?></td>
                                <td><?php echo $row['visit_time']; ?></td>
                                <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                <td><?php echo $row['unique_id']; ?></td>
                                <!-- TANGGAL NA ANG ACTIONS BUTTONS -->
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">No visitors registered yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Content Management Page -->
        <div id="content-management" class="page">
            <div class="upload-section">
                <h2>Upload New AR Content</h2>
                <p>Add complete AR experiences with markers, 3D models, media, and multi-language support</p>
                
                <!-- CHANGED: Now using PHP form with actual file upload -->
                <form id="contentForm" action="upload_content.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="contentTitle">Content Title *</label>
                        <input type="text" id="contentTitle" name="title" class="form-control" placeholder="Enter content title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contentDescription">Description</label>
                        <textarea id="contentDescription" name="description" class="form-control" rows="3" placeholder="Enter content description"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="contentCategory">Category *</label>
                        <select id="contentCategory" name="category" class="form-control" required>
                            <option value="">Select Category</option>
                            <option value="historical">Historical Artifacts</option>
                            <option value="scientific">Scientific Models</option>
                            <option value="art">Art Pieces</option>
                            <option value="interactive">Interactive Exhibits</option>
                        </select>
                    </div>
                    
                    <!-- Image Marker Upload -->
                    <div class="file-upload-group">
                        <h4><i class="fas fa-image"></i> Image Marker *</h4>
                        <p>Upload the image that will trigger the AR experience</p>
                        <input type="file" id="imageMarker" name="image_marker" class="form-control" accept="image/*" required>
                        <div class="file-preview" id="imageMarkerPreview"></div>
                    </div>
                    
                    <!-- 3D Model Upload -->
                    <div class="file-upload-group">
                        <h4><i class="fas fa-cube"></i> 3D Model</h4>
                        <p>Upload 3D model files (GLB, GLTF, OBJ)</p>
                        <input type="file" id="model3d" name="model_3d" class="form-control" accept=".glb,.gltf,.obj">
                        <div class="file-preview" id="model3dPreview"></div>
                    </div>
                    
                    <!-- Video Upload -->
                    <div class="file-upload-group">
                        <h4><i class="fas fa-video"></i> Video Content</h4>
                        <p>Upload explanatory or supplementary videos</p>
                        <input type="file" id="videoContent" name="video" class="form-control" accept="video/*">
                        <div class="file-preview" id="videoContentPreview"></div>
                    </div>
                    
                    <!-- Audio Upload -->
                    <div class="file-upload-group">
                        <h4><i class="fas fa-volume-up"></i> Audio Narration</h4>
                        <p>Upload audio files for narration (MP3, WAV)</p>
                        <input type="file" id="audioContent" name="audio" class="form-control" accept="audio/*">
                        <div class="file-preview" id="audioContentPreview"></div>
                    </div>
                    
                    <button type="submit" class="btn-primary" id="uploadBtn">
                        <i class="fas fa-upload"></i> Upload AR Content
                    </button>
                </form>
            </div>
            
            <!-- Existing Content List -->
            <div class="table-container">
                <h2>Existing AR Content</h2>
                <div class="content-list" id="contentList">
                    <?php if ($content_result && $content_result->num_rows > 0): ?>
                        <?php while($content = $content_result->fetch_assoc()): ?>
                        <div class="content-item">
                            <div class="content-info">
                                <i class="fas fa-cube content-icon"></i>
                                <div class="content-details">
                                    <h4><?php echo htmlspecialchars($content['title']); ?></h4>
                                    <p><strong>Category:</strong> <?php echo htmlspecialchars($content['category']); ?> | <strong>Uploaded:</strong> <?php echo $content['created_at']; ?></p>
                                    <p><strong>Assets:</strong> 
                                        Marker 
                                        <?php echo $content['model_3d'] ? '3D Model ' : ''; ?>
                                        <?php echo $content['video'] ? 'Video ' : ''; ?>
                                        <?php echo $content['audio'] ? 'Audio ' : ''; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="content-actions">
                                <button class="btn-edit" onclick="editContent(<?php echo $content['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-delete" onclick="deleteContent(<?php echo $content['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No AR content uploaded yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Visitors Page -->
        <div id="visitors" class="page">
            <div class="table-container">
                <h2>Visitor Management</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Contact</th>
                            <th>School</th>
                            <th>Visit Date</th>
                            <th>Visit Time</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <!-- TANGGAL NA ANG ACTIONS COLUMN -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $visitors_result = $conn->query("SELECT * FROM visitors ORDER BY id DESC");
                        if ($visitors_result && $visitors_result->num_rows > 0):
                            while($row = $visitors_result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($row['contact']); ?></td>
                            <td><?php echo htmlspecialchars($row['school']); ?></td>
                            <td><?php echo $row['visit_date']; ?></td>
                            <td><?php echo $row['visit_time']; ?></td>
                            <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                            <td><span style="color: green;">Registered</span></td>
                            <!-- TANGGAL NA ANG ACTIONS BUTTONS -->
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">No visitors registered yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Feedback Page -->
        <div id="feedback" class="page">
            <div class="table-container">
                <h2>Visitor Feedback</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Visitor Name</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Date</th>
                            <!-- TANGGAL NA ANG ACTIONS COLUMN -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($feedback_result && $feedback_result->num_rows > 0):
                            while($row = $feedback_result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['visitor_name']); ?></td>
                            <td><?php echo str_repeat('★', $row['rating']) . str_repeat('☆', 5 - $row['rating']); ?></td>
                            <td><?php echo htmlspecialchars($row['comment']); ?></td>
                            <td><?php echo $row['created_at']; ?></td>
                            <!-- TANGGAL NA ANG ACTIONS BUTTONS -->
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No feedback yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Page navigation
            const menuItems = document.querySelectorAll('.menu-item');
            const pages = document.querySelectorAll('.page');
            
            menuItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    if (this.getAttribute('href') === '#') {
                        e.preventDefault();
                    }
                    
                    // Update active menu item
                    menuItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show selected page
                    const pageId = this.getAttribute('data-page');
                    pages.forEach(page => {
                        page.classList.remove('active');
                        if (page.id === pageId) {
                            page.classList.add('active');
                        }
                    });
                    
                    // Update page title
                    const pageTitle = this.querySelector('span').textContent;
                    document.querySelector('.header h1').textContent = pageTitle;
                    
                    // Close sidebar on mobile
                    if (window.innerWidth <= 768) {
                        document.getElementById('sidebar').classList.remove('active');
                    }
                });
            });
            
            // Mobile menu toggle
            document.getElementById('menuToggle').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('active');
            });
            
            // File preview functionality
            document.getElementById('imageMarker').addEventListener('change', function(e) {
                previewFile(e.target.files[0], 'imageMarkerPreview');
            });
            
            document.getElementById('model3d').addEventListener('change', function(e) {
                previewFile(e.target.files[0], 'model3dPreview', '3D Model: ');
            });
            
            document.getElementById('videoContent').addEventListener('change', function(e) {
                previewFile(e.target.files[0], 'videoContentPreview');
            });
            
            document.getElementById('audioContent').addEventListener('change', function(e) {
                previewFile(e.target.files[0], 'audioContentPreview', 'Audio: ');
            });
            
            // Function to preview uploaded files
            function previewFile(file, previewId, prefix = '') {
                const preview = document.getElementById(previewId);
                
                if (!file) {
                    preview.innerHTML = '';
                    preview.style.display = 'none';
                    return;
                }
                
                preview.style.display = 'block';
                
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    };
                    reader.readAsDataURL(file);
                } else if (file.type.startsWith('video/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<video controls><source src="${e.target.result}" type="${file.type}"></video>`;
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.innerHTML = `<p><i class="fas fa-file"></i> ${prefix}${file.name}</p>`;
                }
            }
        });

        // Delete functions - TANGGAL NA ANG DELETE FUNCTIONS FOR VISITORS AND FEEDBACK
        function deleteContent(id) {
            if (confirm('Are you sure you want to delete this AR content?')) {
                window.location.href = 'delete_content.php?id=' + id;
            }
        }

        function editContent(id) {
            alert('Edit functionality for content ID: ' + id + ' would go here');
            // In a real implementation, you would redirect to an edit form
            // window.location.href = 'edit_content.php?id=' + id;
        }
    </script>
</body>
</html>