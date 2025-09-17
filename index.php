<?php
include 'config.php';
include 'database.php';
include 'youtube_api.php';
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube API Пошук</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>YouTube API Пошук</h1>
        
        <!-- Форма для пошуку -->
        <div class="search-form">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="search_query">Пошуковий запит:</label>
                    <input type="text" 
                           id="search_query" 
                           name="search_query" 
                           placeholder="Введіть те, що хочете знайти на YouTube..."
                           required>
                </div>
                
                <div class="form-group">
                    <label for="max_results">Кількість результатів (максимум 50):</label>
                    <input type="number" 
                           id="max_results" 
                           name="max_results" 
                           min="1" 
                           max="50" 
                           value="10">
                </div>
                
                <button type="submit" class="search-button">Пошук на YouTube</button>
            </form>
        </div>

        <!-- Показ помилок -->
        <?php if ($errorMessage): ?>
            <div class="error">
                <strong>Помилка:</strong> <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Показ успішних повідомлень -->
        <?php if ($successMessage): ?>
            <div class="success">
                <strong>Успішно!</strong> <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Показ результатів пошуку -->
        <?php if ($searchResults && isset($searchResults['items'])): ?>
            <h2>Результати пошуку</h2>
            <?php foreach ($searchResults['items'] as $video): ?>
                <div class="video-item">
                    <div class="video-thumbnail">
                        <img src="<?= htmlspecialchars($video['snippet']['thumbnails']['default']['url'] ?? '') ?>" 
                             alt="Thumbnail" 
                             width="120" 
                             height="90">
                    </div>
                    <div class="video-info">
                        <div class="video-title">
                            <?= htmlspecialchars($video['snippet']['title'] ?? 'Без назви') ?>
                        </div>
                        <div class="video-channel">
                            Канал: <?= htmlspecialchars($video['snippet']['channelTitle'] ?? 'Невідомий канал') ?>
                        </div>
                        <div class="video-description">
                            <?= htmlspecialchars(substr($video['snippet']['description'] ?? 'Без опису', 0, 200)) ?>
                            <?= strlen($video['snippet']['description'] ?? '') > 200 ? '...' : '' ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Історія пошуків з бази даних -->
    <?php if (!empty($searchHistory)): ?>
        <div class="container">
            <h2>Історія пошуків з бази даних</h2>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Пошуковий запит</th>
                        <th>Назва відео</th>
                        <th>Канал</th>
                        <th>Дата додавання</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($searchHistory, 0, 20) as $record): ?>
                        <tr>
                            <td data-label="ID"><?= $record['id'] ?></td>
                            <td data-label="Пошуковий запит"><?= htmlspecialchars($record['search_query']) ?></td>
                            <td data-label="Назва відео"><?= htmlspecialchars(substr($record['title'], 0, 50)) ?><?= strlen($record['title']) > 50 ? '...' : '' ?></td>
                            <td data-label="Канал"><?= htmlspecialchars($record['channel_title']) ?></td>
                            <td data-label="Дата додавання"><?= date('d.m.Y H:i', strtotime($record['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</body>
</html>