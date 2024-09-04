<?php
// File: index.php

session_start();

// Define constants for file paths
define('MODELS_FILE', 'models.json');
define('DATA_DIRECTORY', 'data/');
define('PASSWORD_FILE', 'c.pwd');

// Function to check if the user is logged in
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Function to verify the password
function verifyPassword($password) {
    $hashedPassword = file_get_contents(PASSWORD_FILE);
    return password_verify($password, $hashedPassword);
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    if (verifyPassword($_POST['password'])) {
        $_SESSION['logged_in'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $loginError = "Invalid password. Please try again.";
    }
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// If not logged in, show login form
if (!isLoggedIn()) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>CMS Login</title>
        <style>
        .html-content { border: 1px solid #ccc; padding: 10px; margin: 10px 0; }
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --background-color: #ecf0f1;
            --text-color: #34495e;
            --border-color: #bdc3c7;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1, h2 {
            color: var(--secondary-color);
        }

        a {
            color: var(--primary-color);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #2980b9;
            text-decoration: none;
        }

        form {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="password"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        button {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #2980b9;
        }

   
    </style>
    </head>
    <body>
        <div class="container">
        <h1>CMS Login</h1>
        <?php if (isset($loginError)) : ?>
            <p style="color: red;"><?= $loginError ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <label>
                Password: 
                <input type="password" name="password" required>
            </label>
            <button type="submit">Login</button>
        </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// The rest of your CMS code goes here
// Helper function to load models
function loadModels() {
    return file_exists(MODELS_FILE) ? json_decode(file_get_contents(MODELS_FILE), true) : [];
}

// Helper function to load data for a specific model
function loadData($model) {
    $filename = DATA_DIRECTORY . $model . '.json';
    return file_exists($filename) ? json_decode(file_get_contents($filename), true) : [];
}

// Helper function to save data for a specific model
function saveData($model, $data) {
    $filename = DATA_DIRECTORY . $model . '.json';
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
}

// Pagination function
function paginateData($model, $currentPage = 1, $perPage = 3) {
    $data = loadData($model);
    $totalItems = count($data);
    $totalPages = ceil($totalItems / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    $paginatedData = array_slice($data, $offset, $perPage, true);
    
    return [
        'data' => $paginatedData,
        'totalItems' => $totalItems,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage,
        'perPage' => $perPage
    ];
}

// Load models
$models = loadModels();

// Basic routing
$page = $_GET['page'] ?? 'home';
$model = $_GET['model'] ?? null;
$id = $_GET['id'] ?? null;
$currentPage = isset($_GET['p']) ? (int)$_GET['p'] : 1;

// Handle form submissions for CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['model']) && isset($models[$_POST['model']])) {
        $currentModel = $_POST['model'];
        $data = loadData($currentModel);
        $timestamp = date('Y-m-d H:i:s');

        if ($_POST['action'] === 'create') {
            // Create new entry
            $id = uniqid();
            $data[$id] = ['created_at' => $timestamp, 'updated_at' => $timestamp];
            foreach ($models[$currentModel] as $field => $type) {
                if ($field !== 'id') {
                    $data[$id][$field] = $_POST[$field] ?? '';
                }
            }
            $data[$id]['id'] = $id;
        } elseif ($_POST['action'] === 'update' && isset($_POST['id'])) {
            // Update existing entry
            foreach ($models[$currentModel] as $field => $type) {
                if ($field !== 'id') {
                    $data[$_POST['id']][$field] = $_POST[$field] ?? '';
                }
            }
            $data[$_POST['id']]['updated_at'] = $timestamp; // Update the updated_at timestamp
        } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            // Delete entry
            unset($data[$_POST['id']]);
        }

        saveData($currentModel, $data);
        header("Location: ?page=list&model=$currentModel");
        exit;
    }
}

// Function to safely output HTML content
function safeHtml($content) {
    return htmlspecialchars_decode($content);
}

// Function to generate pagination links
function paginationLinks($model, $currentPage, $totalPages) {
    $links = '';
    $links .= $currentPage > 1 ? "<a href='?page=list&model=$model&p=" . ($currentPage - 1) . "'>Previous</a> " : "";
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $currentPage) {
            $links .= "<strong>$i</strong> ";
        } else {
            $links .= "<a href='?page=list&model=$model&p=$i'>$i</a> ";
        }
    }
    $links .= $currentPage < $totalPages ? "<a href='?page=list&model=$model&p=" . ($currentPage + 1) . "'>Next</a>" : "";
    return $links;
}

// HTML and routing logic
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generalized PHP CMS</title>
    <style>
        .html-content { border: 1px solid #ccc; padding: 10px; margin: 10px 0; }
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --background-color: #ecf0f1;
            --text-color: #34495e;
            --border-color: #bdc3c7;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1, h2 {
            color: var(--secondary-color);
        }

        a {
            color: var(--primary-color);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #2980b9;
            text-decoration: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--secondary-color);
            color: white;
        }

        form {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="password"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        button {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #2980b9;
        }

        .html-content {
            border: 1px solid var(--border-color);
            padding: 10px;
            margin: 10px 0;
            background-color: #f9f9f9;
            border-radius: 4px;
        }

        .pagination {
            margin-top: 20px;
            text-align: center;
        }

        .pagination a {
            display: inline-block;
            padding: 5px 10px;
            margin: 0 2px;
            border: 1px solid var(--border-color);
            border-radius: 3px;
        }

        .pagination strong {
            display: inline-block;
            padding: 5px 10px;
            margin: 0 2px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 3px;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .container {
                padding: 10px;
            }

            table, tr, td {
                display: block;
            }

            tr {
                margin-bottom: 10px;
            }

            td {
                border: none;
                position: relative;
                padding-left: 50%;
            }

            td:before {
                content: attr(data-label);
                position: absolute;
                left: 6px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: bold;
            }
        }
    </style>
</head>
<body>
    <div class="container">
    <h1>Generalized PHP CMS</h1>
    <p>Welcome! You are logged in. <a href="?action=logout">Logout</a></p>

    <?php if ($page === 'home'): ?>
        <!-- Home: List all models -->
        <h2>Available Models</h2>
        <ul>
            <?php foreach ($models as $modelName => $fields): ?>
                <li><a href="?page=list&model=<?= $modelName ?>"><?= ucfirst($modelName) ?></a></li>
            <?php endforeach; ?>
        </ul>

    <?php elseif ($page === 'list' && isset($models[$model])): ?>
        <!-- List all entries for a model -->
        <h2><?= ucfirst($model) ?> List</h2>
        <a href="?page=create&model=<?= $model ?>">Create New <?= ucfirst($model) ?></a>
        <?php
        $paginationResult = paginateData($model, $currentPage);
        $data = $paginationResult['data'];
        ?>
        <table border="1">
            <tr>
                <?php foreach ($models[$model] as $field => $type): ?>
                    <th><?= ucfirst($field) ?></th>
                <?php endforeach; ?>
                <th>Creat</th>
                <th>Actualizat</th>
                <th>Actions</th>

            </tr>
            <?php foreach ($data as $id => $entry): ?>
                <tr>
                    <?php foreach ($models[$model] as $field => $type): ?>
                        <td>
                            <?php if ($type === '@html'): ?>
                                <div class="html-content"><?= safeHtml($entry[$field] ?? '') ?></div>
                            <?php else: ?>
                                <?= htmlspecialchars($entry[$field] ?? '') ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td> <?= $entry['created_at'] ?? ''?></td>
                    <td> <?= $entry['updated_at'] ?? ''?></td>
                    <td>
                        <a href="?page=edit&model=<?= $model ?>&id=<?= $id ?>">Edit</a> |
                        <a href="?page=delete&model=<?= $model ?>&id=<?= $id ?>" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div>
            <?= paginationLinks($model, $paginationResult['currentPage'], $paginationResult['totalPages']) ?>
        </div>
        <a href="?page=home">Back to Home</a>

    <?php elseif ($page === 'create' && isset($models[$model])): ?>
        <!-- Create a new entry -->
        <h2>Create New <?= ucfirst($model) ?></h2>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="model" value="<?= $model ?>">
            <?php foreach ($models[$model] as $field => $type): ?>
                <?php if ($field !== 'id'): ?>
                    <label><?= ucfirst($field) ?>: 
                        <?php if ($type === '@html'): ?>
                            <textarea name="<?= $field ?>" required style="width: 100%; height: 200px;"></textarea>
                        <?php elseif ($type === 'text'): ?>
                            <textarea name="<?= $field ?>" required></textarea>
                        <?php else: ?>
                            <input type="text" name="<?= $field ?>" required>
                        <?php endif; ?>
                    </label><br>
                <?php endif; ?>
            <?php endforeach; ?>
            <button type="submit">Save</button>
        </form>
        <a href="?page=list&model=<?= $model ?>">Back to List</a>

    <?php elseif ($page === 'edit' && isset($models[$model])): ?>
        <!-- Edit an existing entry -->
        <?php
        $data = loadData($model);
        $entry = $data[$id] ?? null;
        if ($entry):
        ?>
            <h2>Edit <?= ucfirst($model) ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="model" value="<?= $model ?>">
                <input type="hidden" name="id" value="<?= $id ?>">
                <?php foreach ($models[$model] as $field => $type): ?>
                    <?php if ($field !== 'id'): ?>
                        <label><?= ucfirst($field) ?>: 
                            <?php if ($type === '@html'): ?>
                                <textarea name="<?= $field ?>" required style="width: 100%; height: 200px;"><?= htmlspecialchars($entry[$field] ?? '') ?></textarea>
                            <?php elseif ($type === 'text'): ?>
                                <textarea name="<?= $field ?>" required><?= htmlspecialchars($entry[$field] ?? '') ?></textarea>
                            <?php else: ?>
                                <input type="text" name="<?= $field ?>" value="<?= htmlspecialchars($entry[$field] ?? '') ?>" required>
                            <?php endif; ?>
                        </label><br>
                    <?php endif; ?>
                <?php endforeach; ?>
                <button type="submit">Update</button>
            </form>
            <a href="?page=list&model=<?= $model ?>">Back to List</a>
        <?php else: ?>
            <p>Entry not found.</p>
            <a href="?page=list&model=<?= $model ?>">Back to List</a>
        <?php endif; ?>

    <?php elseif ($page === 'delete' && isset($models[$model])): ?>
        <!-- Delete confirmation -->
        <?php
        $data = loadData($model);
        $entry = $data[$id] ?? null;
        if ($entry):
        ?>
            <h2>Delete <?= ucfirst($model) ?></h2>
            <p>Are you sure you want to delete this entry?</p>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="model" value="<?= $model ?>">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit">Yes, Delete</button>
            </form>
            <a href="?page=list&model=<?= $model ?>">Cancel</a>
        <?php else: ?>
            <p>Entry not found.</p>
            <a href="?page=list&model=<?= $model ?>">Back to List</a>
        <?php endif; ?>

    <?php else: ?>
        <!-- Fallback for invalid routes -->
        <p>Page not found.</p>
        <a href="?page=home">Back to Home</a>
    <?php endif; ?>
    </div>
</body>
</html>