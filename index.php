<?php
// File: index.php

session_start();

// ... (keep all the existing PHP code unchanged) ...

// HTML and routing logic
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generalized PHP CMS</title>
    <style>
        /* Modern CSS styles */
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
        <p>Welcome! You are logged in. <a href="?action=logout" class="btn">Logout</a></p>

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
            <a href="?page=create&model=<?= $model ?>" class="btn">Create New <?= ucfirst($model) ?></a>
            <?php
            $paginationResult = paginateData($model, $currentPage);
            $data = $paginationResult['data'];
            ?>
            <table>
                <tr>
                    <?php foreach ($models[$model] as $field => $type): ?>
                        <th><?= ucfirst($field) ?></th>
                    <?php endforeach; ?>
                    <th>Actions</th>
                </tr>
                <?php foreach ($data as $id => $entry): ?>
                    <tr>
                        <?php foreach ($models[$model] as $field => $type): ?>
                            <td data-label="<?= ucfirst($field) ?>">
                                <?php if ($type === '@html'): ?>
                                    <div class="html-content"><?= safeHtml($entry[$field] ?? '') ?></div>
                                <?php else: ?>
                                    <?= htmlspecialchars($entry[$field] ?? '') ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td data-label="Actions">
                            <a href="?page=edit&model=<?= $model ?>&id=<?= $id ?>" class="btn">Edit</a>
                            <a href="?page=delete&model=<?= $model ?>&id=<?= $id ?>" class="btn" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <div class="pagination">
                <?= paginationLinks($model, $paginationResult['currentPage'], $paginationResult['totalPages']) ?>
            </div>
            <a href="?page=home" class="btn">Back to Home</a>

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
                        </label>
                    <?php endif; ?>
                <?php endforeach; ?>
                <button type="submit">Save</button>
            </form>
            <a href="?page=list&model=<?= $model ?>" class="btn">Back to List</a>

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
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <button type="submit">Update</button>
                </form>
                <a href="?page=list&model=<?= $model ?>" class="btn">Back to List</a>
            <?php else: ?>
                <p>Entry not found.</p>
                <a href="?page=list&model=<?= $model ?>" class="btn">Back to List</a>
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
                <a href="?page=list&model=<?= $model ?>" class="btn">Cancel</a>
            <?php else: ?>
                <p>Entry not found.</p>
                <a href="?page=list&model=<?= $model ?>" class="btn">Back to List</a>
            <?php endif; ?>

        <?php else: ?>
            <!-- Fallback for invalid routes -->
            <p>Page not found.</p>
            <a href="?page=home" class="btn">Back to Home</a>
        <?php endif; ?>
    </div>
</body>
</html>
