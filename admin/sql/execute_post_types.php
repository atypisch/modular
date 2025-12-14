<?php
require_once dirname(__DIR__) . '/config/config.php';

try {
    // First check if tables exist
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $existing_tables = [
        'post_types' => in_array('post_types', $tables),
        'posts' => in_array('posts', $tables)
    ];

    if ($existing_tables['post_types'] && $existing_tables['posts']) {
        echo "Tables already exist. No changes needed.\n";
        exit(0);
    }

    // Get SQL content
    $sql = file_get_contents(__DIR__ . '/post_types.sql');
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    // Execute each statement
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $db->exec($statement);
                echo "Executed statement successfully.\n";
            } catch (PDOException $e) {
                echo "Error executing statement: " . $e->getMessage() . "\n";
                echo "Statement was: " . $statement . "\n";
                // Continue with next statement rather than stopping
                continue;
            }
        }
    }
    
    echo "Database update completed successfully!\n";
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
