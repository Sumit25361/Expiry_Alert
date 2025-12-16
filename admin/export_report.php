<?php
require_once 'config/admin_auth.php';

// Initialize auth
$auth = new AdminAuth();
$auth->requireLogin();
$db = $auth->getDb();

$format = isset($_GET['format']) ? $_GET['format'] : 'csv';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// helper function to output CSV
function outputCSV($data, $filename)
{
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    $output = fopen('php://output', 'w');

    // Write Header
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
    }

    // Write Rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// helper function for Excel (HTML format usually works well for simple exports)
function outputExcel($data, $filename)
{
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');

    echo '<table border="1">';
    // Header
    if (!empty($data)) {
        echo '<tr>';
        foreach (array_keys($data[0]) as $key) {
            echo '<th style="background-color:#f0f0f0;">' . strtoupper($key) . '</th>';
        }
        echo '</tr>';
    }
    // Rows
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
    exit;
}

// Gather Data (Simplified version of reports.php logic)
$exportData = [];

// 1. User Summary
$result = $db->query("SELECT id, username, email, account_status, created_at FROM users WHERE created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'");
while ($row = $result->fetch_assoc()) {
    $row['data_type'] = 'User Registration';
    $exportData[] = $row;
}

// 2. Items Summary (combining tables)
$tables = [
    'documents' => 'Documents',
    'medicines' => 'Medicines',
    'foods' => 'Foods',
    'books' => 'Books',
    'cosmetics' => 'Cosmetics',
    'other_items' => 'Other Items'
];

foreach ($tables as $table => $categoryLabel) {
    // Determine the name column based on table
    $nameColumn = $table === 'documents' ? 'document_name' :
        ($table === 'medicines' ? 'medicine_name' :
            ($table === 'foods' ? 'food_name' :
                ($table === 'books' ? 'book_name' :
                    ($table === 'cosmetics' ? 'cosmetic_name' : 'item_name'))));

    // category column likely doesn't exist in these tables, strictly selecting existing columns
    $query = "SELECT id, $nameColumn as name, expiry_date, created_at FROM $table WHERE created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";

    $result = $db->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $exportRow = [
                'id' => $row['id'],
                'username' => 'N/A', // Items don't directly link to username in this simple view
                'email' => 'N/A',
                'account_status' => 'N/A',
                'created_at' => $row['created_at'],
                'data_type' => 'Item: ' . $categoryLabel,
                'details' => $row['name'] . ' (Expires: ' . $row['expiry_date'] . ')'
            ];
            $exportData[] = $exportRow;
        }
    }
}

if ($format === 'excel') {
    outputExcel($exportData, 'EDR_Report_' . $startDate . '_to_' . $endDate);
} else {
    outputCSV($exportData, 'EDR_Report_' . $startDate . '_to_' . $endDate);
}
?>