<?php
    class YouTubeAPI {
        private $apiKey;
        private $apiUrl;
        
        public function __construct() {
            $this->apiKey = Config::YOUTUBE_API_KEY;
            $this->apiUrl = Config::YOUTUBE_API_URL;
        }
        
        // Основний метод для пошуку відео
        public function searchVideos($query, $maxResults = 10) {
            // Підготовка параметрів для API запиту
            $params = [
                'part' => 'snippet',           // Частина відповіді (snippet містить основну інформацію)
                'q' => $query,                 // Пошуковий запит
                'type' => 'video',             // Тип контенту (тільки відео)
                'maxResults' => $maxResults,   // Максимальна кількість результатів
                'key' => $this->apiKey         // API ключ
            ];
            
            // Формування URL з параметрами
            $url = $this->apiUrl . '?' . http_build_query($params);
            
            // Виконання HTTP запиту
            $response = $this->makeRequest($url);
            
            if ($response === false) {
                return ['error' => 'Помилка виконання запиту до YouTube API'];
            }
            
            // Декодування JSON відповіді
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['error' => 'Помилка декодування JSON'];
            }
            
            return $data;
        }
        
        // Виконання HTTP запиту
        private function makeRequest($url) {
            // Використовуємо cURL для HTTP запиту
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_error($ch)) {
                echo "cURL помилка: " . curl_error($ch);
                curl_close($ch);
                return false;
            }
            
            curl_close($ch);
            
            if ($httpCode !== 200) {
                echo "HTTP помилка: $httpCode";
                return false;
            }
            
            return $response;
        }
    }

    // Функція для редіректу (POST-Redirect-GET pattern)
    function redirectToSelf() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        $url = $protocol . "://" . $host . $uri;
        header("Location: " . $url);
        exit();
    }

    // Ініціалізація
    $database = new Database();
    $database->createTable(); // Створюємо таблицю при першому запуску

    $youtubeAPI = new YouTubeAPI();

    // Обробка форми пошуку з POST-Redirect-GET pattern
    if ($_POST && isset($_POST['search_query']) && !empty(trim($_POST['search_query']))) {
        $searchQuery = trim($_POST['search_query']);
        $maxResults = isset($_POST['max_results']) ? intval($_POST['max_results']) : 10;
        
        // Виконуємо пошук через YouTube API
        $apiResponse = $youtubeAPI->searchVideos($searchQuery, $maxResults);
        
        session_start();
        
        if (isset($apiResponse['error'])) {
            // Зберігаємо помилку в сесії та редіректимо
            $_SESSION['error_message'] = $apiResponse['error'];
        } elseif (isset($apiResponse['items']) && !empty($apiResponse['items'])) {
            // Зберігаємо результати в базу даних
            $savedCount = 0;
            foreach ($apiResponse['items'] as $video) {
                if ($database->saveSearchResult($searchQuery, $video)) {
                    $savedCount++;
                }
            }
            
            // Зберігаємо повідомлення про успіх в сесії
            $_SESSION['success_message'] = "Знайдено " . count($apiResponse['items']) . " результатів. Збережено в базу: " . $savedCount . " записів.";
            $_SESSION['search_results'] = $apiResponse;
        } else {
            $_SESSION['error_message'] = "Результатів не знайдено";
        }
        
        // Редіректимо на цю ж сторінку (POST-Redirect-GET)
        redirectToSelf();
    }

    // Обробка повідомлень з сесії
    session_start();
    $errorMessage = null;
    $successMessage = null;
    $searchResults = null;

    if (isset($_SESSION['error_message'])) {
        $errorMessage = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    }

    if (isset($_SESSION['success_message'])) {
        $successMessage = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    }

    if (isset($_SESSION['search_results'])) {
        $searchResults = $_SESSION['search_results'];
        unset($_SESSION['search_results']);
    }

    // Отримуємо історію пошуків з бази даних
    $searchHistory = $database->getAllSearches();
?>