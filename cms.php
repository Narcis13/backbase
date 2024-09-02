<?php
// File: index.php

// Define the path to the JSON file
define('POSTS_FILE', 'posts.json');

// Fetch existing posts or initialize an empty array if the file does not exist
$posts = file_exists(POSTS_FILE) ? json_decode(file_get_contents(POSTS_FILE), true) : [];
$by='Narcis';
// Basic routing
$page = $_GET['page'] ?? 'home';
$id = $_GET['id'] ?? null;

// Check if the request is an API request based on a query parameter or header
$isApiRequest = isset($_GET['api']) && $_GET['api'] === 'true';

// Helper function to save posts to JSON file
function savePosts($posts) {
    file_put_contents(POSTS_FILE, json_encode($posts, JSON_PRETTY_PRINT));
}

// Handle API request: Respond with all posts in JSON format
if ($isApiRequest) {
    header('Content-Type: application/json');
    echo json_encode($posts);
    exit;
}

// Handle form submissions for CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'create') {
        // Create new post
        $id = uniqid();
        $posts[$id] = [
            'title' => $_POST['title'],
            'content' => $_POST['content']
        ];
        savePosts($posts);
        header('Location: ?page=home');
        exit;
    } elseif ($_POST['action'] === 'update' && isset($_POST['id'])) {
        // Update existing post
        $posts[$_POST['id']]['title'] = $_POST['title'];
        $posts[$_POST['id']]['content'] = $_POST['content'];
        savePosts($posts);
        header('Location: ?page=home');
        exit;
    } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        // Delete post
        unset($posts[$_POST['id']]);
        savePosts($posts);
        header('Location: ?page=home');
        exit;
    }
}

// HTML and basic routing logic
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Simple PHP CMS by <?= $by ?></title>
</head>
<body>
    <h1>Simple PHP CMS</h1>

    <?php if ($page === 'home'): ?>
        <!-- Home: List all posts -->
        <h2>All Posts</h2>
        <a href="?page=create">Create New Post</a>
        <ul>
            <?php foreach ($posts as $id => $post): ?>
                <li>
                    <a href="?page=view&id=<?= $id ?>"><?= htmlspecialchars($post['title']) ?></a> 
                    | <a href="?page=edit&id=<?= $id ?>">Edit</a> 
                    | <a href="?page=delete&id=<?= $id ?>" onclick="return confirm('Are you sure?')">Delete</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php elseif ($page === 'view' && isset($posts[$id])): ?>
        <!-- View a single post -->
        <h2><?= htmlspecialchars($posts[$id]['title']) ?></h2>
        <p><?= nl2br(htmlspecialchars($posts[$id]['content'])) ?></p>
        <a href="?page=home">Back to Home</a>
    <?php elseif ($page === 'create'): ?>
        <!-- Create a new post -->
        <h2>Create New Post</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <label>Title: <input type="text" name="title" required></label><br>
            <label>Content: <textarea name="content" required></textarea></label><br>
            <button type="submit">Save</button>
        </form>
        <a href="?page=home">Back to Home</a>
    <?php elseif ($page === 'edit' && isset($posts[$id])): ?>
        <!-- Edit an existing post -->
        <h2>Edit Post</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $id ?>">
            <label>Title: <input type="text" name="title" value="<?= htmlspecialchars($posts[$id]['title']) ?>" required></label><br>
            <label>Content: <textarea name="content" required><?= htmlspecialchars($posts[$id]['content']) ?></textarea></label><br>
            <button type="submit">Update</button>
        </form>
        <a href="?page=home">Back to Home</a>
    <?php elseif ($page === 'delete' && isset($posts[$id])): ?>
        <!-- Delete confirmation -->
        <h2>Delete Post</h2>
        <p>Are you sure you want to delete "<?= htmlspecialchars($posts[$id]['title']) ?>"?</p>
        <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit">Yes, Delete</button>
        </form>
        <a href="?page=home">Cancel</a>
    <?php else: ?>
        <!-- Fallback for invalid routes -->
        <p>Page not found.</p>
        <a href="?page=home">Back to Home</a>
    <?php endif; ?>
</body>
</html>
