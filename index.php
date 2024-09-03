
<?php
// File: index.php

// Define constants for file paths
define('MODELS_FILE', 'models.json');
define('DATA_DIRECTORY', 'data/');

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

// Basic routing
$page = $_GET['page'] ?? 'home';
$model = $_GET['model'] ?? null;
$id = $_GET['id'] ?? null;

// Handle form submissions for CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['model']) && isset($models[$_POST['model']])) {
        $currentModel = $_POST['model'];
        $data = loadData($currentModel);

        if ($_POST['action'] === 'create') {
            // Create new entry
            $id = uniqid();
            $data[$id] = [];
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
        } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            // Delete entry
            unset($data[$_POST['id']]);
        }

        saveData($currentModel, $data);
        header("Location: ?page=list&model=$currentModel");
        exit;
    }
}

// HTML and routing logic
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generalized PHP CMS</title>
</head>
<body>
    <h1>Generalized PHP CMS</h1>

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
        <table border="1">
            <tr>
                <?php foreach ($models[$model] as $field => $type): ?>
                    <th><?= ucfirst($field) ?></th>
                <?php endforeach; ?>
                <th>Actions</th>
            </tr>
            <?php
            $data = loadData($model);
            foreach ($data as $id => $entry):
            ?>
                <tr>
                    <?php foreach ($models[$model] as $field => $type): ?>
                        <td><?= htmlspecialchars($entry[$field] ?? '') ?></td>
                    <?php endforeach; ?>
                    <td>
                        <a href="?page=edit&model=<?= $model ?>&id=<?= $id ?>">Edit</a> |
                        <a href="?page=delete&model=<?= $model ?>&id=<?= $id ?>" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
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
                        <?php if ($type === 'text'): ?>
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
                            <?php if ($type === 'text'): ?>
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
</body>
</html>