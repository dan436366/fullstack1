<?php
    class Database {
        private $pdo;
        
        public function __construct() {
            try {
                $pdoTemp = new PDO(
                    "mysql:host=" . Config::DB_HOST . ";charset=utf8",
                    Config::DB_USER,
                    Config::DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                $pdoTemp->exec("CREATE DATABASE IF NOT EXISTS " . Config::DB_NAME . " CHARACTER SET utf8 COLLATE utf8_general_ci");
                
                $this->pdo = new PDO(
                    "mysql:host=" . Config::DB_HOST . ";dbname=" . Config::DB_NAME . ";charset=utf8",
                    Config::DB_USER,
                    Config::DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                echo "<div class='db-status'>База даних успішно підключена!</div>";
                
            } catch (PDOException $e) {
                die("<div style='color: red; background: #ffebee; padding: 15px; margin: 10px 0; border-radius: 4px;'><strong>Помилка підключення до бази даних:</strong><br>" . $e->getMessage() . "<br><br><strong>Перевірте:</strong><ul><li>Чи запущений MySQL сервер?</li><li>Чи правильні дані для підключення (логін/пароль)?</li><li>Чи існує користувач з правами на створення баз даних?</li></ul></div>");
            }
        }
        
        // Створення таблиці для збереження результатів
        public function createTable() {
            $sql = "CREATE TABLE IF NOT EXISTS youtube_searches (
                id INT AUTO_INCREMENT PRIMARY KEY,
                search_query VARCHAR(255) NOT NULL,
                video_id VARCHAR(50),
                title TEXT,
                description TEXT,
                thumbnail_url VARCHAR(500),
                channel_title VARCHAR(255),
                published_at DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            try {
                $this->pdo->exec($sql);
            } catch (PDOException $e) {
                echo "Помилка створення таблиці: " . $e->getMessage();
            }
        }
        
        // Збереження результату в базу даних
        public function saveSearchResult($searchQuery, $videoData) {
            $sql = "INSERT INTO youtube_searches 
                    (search_query, video_id, title, description, thumbnail_url, channel_title, published_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            try {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $searchQuery,
                    $videoData['id']['videoId'] ?? '',
                    $videoData['snippet']['title'] ?? '',
                    $videoData['snippet']['description'] ?? '',
                    $videoData['snippet']['thumbnails']['default']['url'] ?? '',
                    $videoData['snippet']['channelTitle'] ?? '',
                    $videoData['snippet']['publishedAt'] ?? null
                ]);
                return true;
            } catch (PDOException $e) {
                echo "Помилка збереження в базу: " . $e->getMessage();
                return false;
            }
        }
        
        // Отримання всіх збережених результатів
        public function getAllSearches() {
            $sql = "SELECT * FROM youtube_searches ORDER BY id DESC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
    }
?>