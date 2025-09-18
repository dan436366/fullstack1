<?php
    class YouTubeAPI {
        private $apiKey;
        private $apiUrl;
        
        public function __construct() {
            $this->apiKey = Config::YOUTUBE_API_KEY;
            $this->apiUrl = Config::YOUTUBE_API_URL;
        }
        
        // метод для пошуку відео
        public function searchVideos($query, $maxResults = 10) {

            $params = [
                'part' => 'snippet',           
                'q' => $query,                
                'type' => 'video',            
                'maxResults' => $maxResults,   
                'key' => $this->apiKey         
            ];
            
            
            $url = $this->apiUrl . '?' . http_build_query($params);
            
            
            $response = $this->makeRequest($url);
            
            if ($response === false) {
                return ['error' => 'Помилка виконання запиту до YouTube API'];
            }
            
           
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['error' => 'Помилка декодування JSON'];
            }
            
            return $data;
        }
        
        // Виконання HTTP запиту
        private function makeRequest($url) {
            
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

    // Функція для редіректу
    function redirectToSelf() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        $url = $protocol . "://" . $host . $uri;
        header("Location: " . $url);
        exit();
    }

   
    $database = new Database();
    $database->createTable(); 

    $youtubeAPI = new YouTubeAPI();

    // Обробка форми пошуку 
    if ($_POST && isset($_POST['search_query']) && !empty(trim($_POST['search_query']))) {
        $searchQuery = trim($_POST['search_query']);
        $maxResults = isset($_POST['max_results']) ? intval($_POST['max_results']) : 10;
        
        
        $apiResponse = $youtubeAPI->searchVideos($searchQuery, $maxResults);
        
        session_start();
        
        if (isset($apiResponse['error'])) {
            $_SESSION['error_message'] = $apiResponse['error'];
        } elseif (isset($apiResponse['items']) && !empty($apiResponse['items'])) {
            $savedCount = 0;
            foreach ($apiResponse['items'] as $video) {
                if ($database->saveSearchResult($searchQuery, $video)) {
                    $savedCount++;
                }
            }
            
            $_SESSION['success_message'] = "Знайдено " . count($apiResponse['items']) . " результатів. Збережено в базу: " . $savedCount . " записів.";
            $_SESSION['search_results'] = $apiResponse;
        } else {
            $_SESSION['error_message'] = "Результатів не знайдено";
        }
        
        redirectToSelf();
    }

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

    $searchHistory = $database->getAllSearches();
?>