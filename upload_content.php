<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: home.php");
    exit;
}

// Create uploads directory if it doesn't exist
$upload_dirs = ['uploads/markers/', 'uploads/models/', 'uploads/videos/', 'uploads/audio/'];
foreach ($upload_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    
    // Handle file uploads
    $image_marker = '';
    $model_3d = '';
    $video = '';
    $audio = '';
    
    // Upload marker image
    if (isset($_FILES['image_marker']) && $_FILES['image_marker']['error'] === 0) {
        $marker_name = time() . '_' . basename($_FILES['image_marker']['name']);
        $marker_target = 'uploads/markers/' . $marker_name;
        if (move_uploaded_file($_FILES['image_marker']['tmp_name'], $marker_target)) {
            $image_marker = $marker_target;
        }
    }
    
    // Upload 3D model
    if (isset($_FILES['model_3d']) && $_FILES['model_3d']['error'] === 0) {
        $model_name = time() . '_' . basename($_FILES['model_3d']['name']);
        $model_target = 'uploads/models/' . $model_name;
        if (move_uploaded_file($_FILES['model_3d']['tmp_name'], $model_target)) {
            $model_3d = $model_target;
        }
    }
    
    // Upload video
    if (isset($_FILES['video']) && $_FILES['video']['error'] === 0) {
        $video_name = time() . '_' . basename($_FILES['video']['name']);
        $video_target = 'uploads/videos/' . $video_name;
        if (move_uploaded_file($_FILES['video']['tmp_name'], $video_target)) {
            $video = $video_target;
        }
    }
    
    // Upload audio
    if (isset($_FILES['audio']) && $_FILES['audio']['error'] === 0) {
        $audio_name = time() . '_' . basename($_FILES['audio']['name']);
        $audio_target = 'uploads/audio/' . $audio_name;
        if (move_uploaded_file($_FILES['audio']['tmp_name'], $audio_target)) {
            $audio = $audio_target;
        }
    }
    
    // Insert into database using admin_dashboard structure
    $stmt = $conn->prepare("INSERT INTO ar_content (title, description, category, image_marker, model_3d, video, audio, active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("sssssss", $title, $description, $category, $image_marker, $model_3d, $video, $audio);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "AR content uploaded successfully!";
    } else {
        $_SESSION['error'] = "Error uploading content: " . $conn->error;
    }
    
    header("Location: admin_dashboard.php");
    exit;
}
?>