<?php
// File: index.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Load models
$models = loadModels();

// Check if this is an API request
$request_uri = $_SERVER['REQUEST_URI'];
$is_api_request = strpos($request_uri, '/api/') === 0;

if ($is_api_request) {
    // API functionality
    header('Content-Type: application/json');

    $uri_parts = explode('/', trim($request_uri, '/'));
    array_shift($uri_parts); // Remove 'api'
    $model = $uri_parts[0] ?? '';
    $id = $uri_parts[1] ?? null;

    // Function to verify Bearer token
    function verifyToken($token) {
        $hashedPassword = file_get_contents(PASSWORD_FILE);
        return password_verify($token, $hashedPassword);
    }

    // Check authorization for POST, PATCH, and DELETE requests
    function checkAuthorization() {
        $method = $_SERVER['REQUEST_METHOD'];
        if (in_array($method, ['POST', 'PATCH', 'DELETE'])) {
            $headers = apache_request_headers();
            $authHeader = $headers['Authorization'] ?? '';
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $token = $matches[1];
                if (!verifyToken($token)) {
                    http_response_code(401);
                    echo json_encode(['error' => 'Unauthorized']);
                    exit;
                }
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }
        }
    }

    // Check authorization
    checkAuthorization();

    // Handle API requests
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($model && isset($models[$model])) {
                $data = loadData($model);
                if ($id !== null) {
                    if (isset($data[$id])) {
                        echo json_encode($data[$id]);
                    } else {
                        http_response_code(404);
                        echo json_encode(['error' => 'Not found']);
                    }
                } else {
                    echo json_encode($data);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid model']);
            }
            break;

        case 'POST':
            if ($model && isset($models[$model])) {
                $input = json_decode(file_get_contents('php://input'), true);
                $data = loadData($model);
                $id = uniqid();
                $timestamp = date('Y-m-d H:i:s');
                $newEntry = ['id' => $id, 'created_at' => $timestamp, 'updated_at' => $timestamp];
                foreach ($models[$model] as $field => $type) {
                    if ($field !== 'id') {
                        $newEntry[$field] = $input[$field] ?? '';
                    }
                }
                $data[$id] = $newEntry;
                saveData($model, $data);
                http_response_code(201);
                echo json_encode($newEntry);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid model']);
            }
            break;

        case 'PATCH':
            if ($model && isset($models[$model]) && $id !== null) {
                $input = json_decode(file_get_contents('php://input'), true);
                $data = loadData($model);
                if (isset($data[$id])) {
                    $timestamp = date('Y-m-d H:i:s');
                    foreach ($models[$model] as $field => $type) {
                        if ($field !== 'id' && isset($input[$field])) {
                            $data[$id][$field] = $input[$field];
                        }
                    }
                    $data[$id]['updated_at'] = $timestamp;
                    saveData($model, $data);
                    echo json_encode($data[$id]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Not found']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid model or ID']);
            }
            break;

        case 'DELETE':
            if ($model && isset($models[$model]) && $id !== null) {
                $data = loadData($model);
                if (isset($data[$id])) {
                    unset($data[$id]);
                    saveData($model, $data);
                    http_response_code(204);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Not found']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid model or ID']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    exit;
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
//function loadModels() {
  //  return file_exists(MODELS_FILE) ? json_decode(file_get_contents(MODELS_FILE), true) : [];
//}

// Helper function to load data for a specific model
//function loadData($model) {
 //   $filename = DATA_DIRECTORY . $model . '.json';
 //   return file_exists($filename) ? json_decode(file_get_contents($filename), true) : [];
//}

// Helper function to save data for a specific model
//function saveData($model, $data) {
//    $filename = DATA_DIRECTORY . $model . '.json';
//    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
//}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'upload') {
    // Ensure the upload directory exists
    $uploadDir = 'upload/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Check if a file was uploaded without errors
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['file']['tmp_name'];
        $fileName = basename($_FILES['file']['name']);
        $destination = $uploadDir . $fileName;

        // Move the uploaded file to the desired location
        if (move_uploaded_file($fileTmpPath, $destination)) {
            $uploadMessage = "File uploaded successfully: $fileName";
        } else {
            $uploadMessage = "Failed to move uploaded file.";
        }
    } else {
        $uploadMessage = "Error during file upload.";
    }
}

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
        .create-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                padding: 8px 12px;
                background-color: #28a745; /* Green button background */
                color: #fff;
                border: none;
                border-radius: 4px;
                font-size: 16px;
                cursor: pointer;
                transition: background-color 0.3s;
            }

        .create-button:hover {
            background-color: #218838; /* Darker green on hover */
        }

        .plus-icon {
            font-size: 18px;
            margin-right: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
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
            <!-- Upload Form: Add this before the Available Models section -->
    <h2>Upload File</h2>
    <form action="?action=upload" method="POST" enctype="multipart/form-data">
        <input type="file" name="file" required>
        <button type="submit">Upload</button>
    </form>
    
    <!-- Display Upload Success or Error Message -->
    <?php if (isset($uploadMessage)): ?>
        <p><?= htmlspecialchars($uploadMessage) ?></p>
    <?php endif; ?>

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
        <a href="?page=create&model=<?= $model ?>" class="create-button" title="Create New <?= ucfirst($model) ?>">
                <span class="plus-icon">+</span>
                <span><?= ucfirst($model) ?></span>
        </a>
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