<?php
// Simple CMS in a single PHP file

// Configuration
$data_dir = 'data/';
$content_file = $data_dir . 'content.json';

// Ensure data directory exists
if (!file_exists($data_dir)) {
    mkdir($data_dir, 0777, true);
}

// Initialize or load content
if (!file_exists($content_file)) {
    $content = [];
    file_put_contents($content_file, json_encode($content));
} else {
    $content = json_decode(file_get_contents($content_file), true);
}


if (preg_match('/^\/blog\/([a-zA-Z0-9]+)$/', $_SERVER['REQUEST_URI'], $matches)) {
    $post_id = $matches[1];
    $post = null;
    foreach ($content as $item) {
        if ($item['id'] === $post_id) {
            $post = $item;
            break;
        }
    }
    
    if ($post) {
        // Display the post
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($post['title']) ?></title>
        </head>
        <body>
            <h1><?= htmlspecialchars($post['title']) ?></h1>
            <div><?= nl2br(htmlspecialchars($post['content'])) ?></div>
            <a href="/">Back to home</a>
        </body>
        </html>
        <?php
        exit;
    } else {
        // Post not found
        header("HTTP/1.0 404 Not Found");
        echo "Post not found";
        exit;
    }
}


// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $new_item = [
                    'id' => uniqid(),
                    'title' => $_POST['title'],
                    'content' => $_POST['content']
                ];
                $content[] = $new_item;
                break;
            case 'update':
                foreach ($content as &$item) {
                    if ($item['id'] === $_POST['id']) {
                        $item['title'] = $_POST['title'];
                        $item['content'] = $_POST['content'];
                        break;
                    }
                }
                break;
            case 'delete':
                $content = array_filter($content, function($item) {
                    return $item['id'] !== $_POST['id'];
                });
                break;
        }
        file_put_contents($content_file, json_encode($content));
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple CMS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        form { margin-bottom: 20px; }
        input, textarea { display: block; margin-bottom: 10px; width: 100%; }
    </style>
</head>
<body>
    <h1>Simple CMS</h1>

    <!-- Create form -->
    <h2>Create New Item</h2>
    <form method="post">
        <input type="hidden" name="action" value="create">
        <input type="text" name="title" placeholder="Title" required>
        <textarea name="content" placeholder="Content" required></textarea>
        <input type="submit" value="Create">
    </form>

    <!-- List and edit items -->
    <h2>Content Items</h2>
    <?php foreach ($content as $item): ?>
        <form method="post">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">
            <input type="text" name="title" value="<?= htmlspecialchars($item['title']) ?>" required>
            <textarea name="content" required><?= htmlspecialchars($item['content']) ?></textarea>
            <input type="submit" value="Update">
        </form>
        <form method="post" style="display: inline;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">
            <input type="submit" value="Delete" onclick="return confirm('Are you sure?')">
        </form>
        <hr>
    <?php endforeach; ?>
</body>
</html>
