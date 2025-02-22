<?php
require_once '../config/db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Test database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    $term = isset($_GET['term']) ? trim($_GET['term']) : '';
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'name';

    // Validate filter
    $allowedFilters = ['name', 'address', 'phone'];
    if (!in_array($filter, $allowedFilters)) {
        $filter = 'name';
    }

    $searchTerm = "%$term%";
    
    // Build query based on filter
    $sql = "SELECT hospital_id, name, email, address, phone, region 
            FROM hospitals 
            WHERE ";
    
    switch ($filter) {
        case 'address':
            $sql .= "LOWER(address) LIKE LOWER(:term)";
            break;
        case 'phone':
            $sql .= "phone LIKE :term";
            break;
        default:
            $sql .= "LOWER(name) LIKE LOWER(:term)";
    }
    
    $sql .= " ORDER BY name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':term', $searchTerm);
    $stmt->execute();
    
    $hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return detailed response
    echo json_encode([
        'success' => true,
        'data' => $hospitals,
        'debug' => [
            'searchTerm' => $searchTerm,
            'filter' => $filter,
            'sql' => $sql,
            'resultCount' => count($hospitals),
            'results' => $hospitals
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
