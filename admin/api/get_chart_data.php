<?php
require_once('../includes/auth_check.php');
require_once('../../config/db.php');

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';

try {
    switch($type) {
        case 'user_growth':
            // Get user registrations over the last 12 months
            $query = "SELECT 
                        DATE_FORMAT(created_at, '%Y-%m') as month,
                        COUNT(*) as count
                      FROM users 
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                      GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                      ORDER BY month ASC";
            $result = $conn->query($query);
            
            if (!$result) {
                throw new Exception("Database query failed: " . $conn->error);
            }
            
            $data = [];
            while($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            // Log for debugging
            error_log("User growth data: " . json_encode($data));
            
            echo json_encode(['success' => true, 'data' => $data, 'count' => count($data)]);
            break;
            
        case 'transaction_volume':
            // Get income vs expenses for last 12 months
            $query = "SELECT 
                        DATE_FORMAT(t.created_at, '%Y-%m') as month,
                        t.type,
                        SUM(t.amount) as total
                      FROM transactions t
                      WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                      GROUP BY DATE_FORMAT(t.created_at, '%Y-%m'), t.type
                      ORDER BY month ASC, t.type";
            $result = $conn->query($query);
            $data = [];
            while($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'feedback_distribution':
            // Get rating distribution
            $query = "SELECT rating, COUNT(*) as count 
                      FROM user_feedback 
                      GROUP BY rating 
                      ORDER BY rating DESC";
            $result = $conn->query($query);
            $data = [];
            while($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'category_breakdown':
            // Get expense breakdown by category
            $query = "SELECT 
                        c.name as category,
                        SUM(t.amount) as total
                      FROM transactions t
                      JOIN categories c ON t.category_id = c.id
                      WHERE t.type = 'expense'
                      GROUP BY c.name
                      ORDER BY total DESC
                      LIMIT 10";
            $result = $conn->query($query);
            $data = [];
            while($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'users_by_country':
            // Get user distribution by country
            $query = "SELECT 
                        country,
                        COUNT(*) as count
                      FROM users 
                      GROUP BY country
                      ORDER BY count DESC";
            $result = $conn->query($query);
            
            if (!$result) {
                throw new Exception("Database query failed: " . $conn->error);
            }
            
            $data = [];
            while($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            // Log for debugging
            error_log("Users by country data: " . json_encode($data));
            
            echo json_encode(['success' => true, 'data' => $data, 'count' => count($data)]);
            break;
            
        case 'system_info':
            // Get system information
            $db_status = $conn->ping() ? 'Connected' : 'Disconnected';
            $php_version = phpversion();
            $mysql_version = $conn->server_info;
            
            // Get database size
            $db_name = $conn->query("SELECT DATABASE() as db")->fetch_assoc()['db'];
            $size_query = "SELECT 
                            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
                           FROM information_schema.TABLES 
                           WHERE table_schema = '$db_name'";
            $size_result = $conn->query($size_query);
            $db_size = $size_result->fetch_assoc()['size_mb'] ?? 0;
            
            $data = [
                'db_status' => $db_status,
                'php_version' => $php_version,
                'mysql_version' => $mysql_version,
                'db_size' => $db_size . ' MB'
            ];
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid chart type']);
            break;
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
