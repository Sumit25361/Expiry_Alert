<?php
// Debug script for notifications logic
require_once 'admin/config/admin_auth.php';

// Mock AdminAuth to get DB connection
class MockAuth extends AdminAuth
{
    public function getDbConnection()
    {
        return $this->db;
    }
}

$auth = new MockAuth();
$db = $auth->getDbConnection();

$daysAhead = 1; // Testing for 1 day ahead as per user complaint

echo "Checking for items expiring in next $daysAhead days...\n";

// Function copy-pasted (or logic replicated) from notifications.php for testing
function getAllUsersWithExpiringItemsDebug($db, $daysAhead)
{
    $tables = [
        'documents' => 'document_name',
        'medicines' => 'medicine_name',
        'foods' => 'food_name',
        'books' => 'book_name',
        'cosmetics' => 'cosmetic_name',
        'other_items' => 'item_name'
    ];

    $userEmails = [];

    foreach ($tables as $table => $nameColumn) {
        // Check if table exists first
        $tableCheck = $db->query("SHOW TABLES LIKE '$table'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            echo "Checking table: $table\n";

            // Debug query
            $debugQuery = "SELECT email, expiry_date FROM $table 
                     WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $daysAhead DAY)";
            echo "Query: $debugQuery\n";
            $result = $db->query($debugQuery);
            if ($result) {
                echo "Found " . $result->num_rows . " rows in $table.\n";
                while ($row = $result->fetch_assoc()) {
                    print_r($row);
                }
            } else {
                echo "Query failed: " . $db->error . "\n";
            }

            // Original logic query
            $query = "SELECT DISTINCT email FROM $table 
                     WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                     AND email IS NOT NULL AND email != ''";

            $stmt = $db->prepare($query);
            if ($stmt) {
                $stmt->bind_param('i', $daysAhead);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    if (!empty($row['email'])) {
                        $userEmails[] = $row['email'];
                    }
                }
                $stmt->close();
            }
        } else {
            echo "Table $table does not exist.\n";
        }
    }

    return array_unique($userEmails);
}

$users = getAllUsersWithExpiringItemsDebug($db, $daysAhead);
echo "\nTotal users found: " . count($users) . "\n";
print_r($users);

// Also check general counts
echo "\n--- General Stats ---\n";
$tables = ['documents', 'medicines', 'foods', 'books', 'cosmetics', 'other_items'];
foreach ($tables as $table) {
    $res = $db->query("SELECT COUNT(*) as cnt FROM $table WHERE expiry_date >= CURDATE()");
    if ($res) {
        $row = $res->fetch_assoc();
        echo "$table active items: " . $row['cnt'] . "\n";
    }
}
?>