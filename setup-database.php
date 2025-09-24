<?php
require_once 'db.php';

echo "<h2>Setting up Hilton Hotels Database...</h2>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";

try {
    // Read the SQL file
    $sql_file = 'database_schema.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    $sql_content = file_get_contents($sql_file);
    if ($sql_content === false) {
        throw new Exception("Could not read SQL file");
    }
    
    echo "<p>✓ SQL file loaded successfully</p>";
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql_content)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    echo "<p>✓ Found " . count($statements) . " SQL statements</p>";
    
    // Execute each statement
    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        if (trim($statement)) {
            try {
                $database->execute($statement);
                $executed++;
                
                // Show progress for major operations
                if (stripos($statement, 'CREATE TABLE') !== false) {
                    preg_match('/CREATE TABLE\s+(\w+)/i', $statement, $matches);
                    if ($matches) {
                        echo "<p>✓ Created table: " . $matches[1] . "</p>";
                    }
                } elseif (stripos($statement, 'INSERT INTO') !== false) {
                    preg_match('/INSERT INTO\s+(\w+)/i', $statement, $matches);
                    if ($matches) {
                        echo "<p>✓ Inserted data into: " . $matches[1] . "</p>";
                    }
                }
            } catch (Exception $e) {
                $errors++;
                echo "<p style='color: red;'>✗ Error executing statement: " . $e->getMessage() . "</p>";
                // Continue with other statements
            }
        }
    }
    
    echo "<hr>";
    echo "<h3>Database Setup Complete!</h3>";
    echo "<p><strong>Executed:</strong> $executed statements</p>";
    echo "<p><strong>Errors:</strong> $errors</p>";
    
    // Verify the setup by checking some data
    echo "<h3>Verification:</h3>";
    
    // Check hotels
    $hotels = $database->getMultiple("SELECT COUNT(*) as count FROM hotels");
    $hotel_count = $hotels[0]['count'] ?? 0;
    echo "<p>✓ Hotels in database: $hotel_count</p>";
    
    // Check locations
    $locations = $database->getMultiple("SELECT COUNT(*) as count FROM locations");
    $location_count = $locations[0]['count'] ?? 0;
    echo "<p>✓ Locations in database: $location_count</p>";
    
    // Check rooms
    $rooms = $database->getMultiple("SELECT COUNT(*) as count FROM rooms");
    $room_count = $rooms[0]['count'] ?? 0;
    echo "<p>✓ Rooms in database: $room_count</p>";
    
    // Check amenities
    $amenities = $database->getMultiple("SELECT COUNT(*) as count FROM amenities");
    $amenity_count = $amenities[0]['count'] ?? 0;
    echo "<p>✓ Amenities in database: $amenity_count</p>";
    
    if ($hotel_count > 0 && $room_count > 0) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4 style='color: #155724; margin: 0 0 10px 0;'>🎉 Success!</h4>";
        echo "<p style='color: #155724; margin: 0;'>Your database has been set up successfully with sample hotels and rooms. You can now search for hotels on your website!</p>";
        echo "</div>";
        
        echo "<p><strong>Next steps:</strong></p>";
        echo "<ul>";
        echo "<li><a href='index.php'>Go to Homepage</a> to start searching</li>";
        echo "<li><a href='hotels.php'>View All Hotels</a> to see the listings</li>";
        echo "<li>Try searching for cities like 'New York', 'London', or 'Dubai'</li>";
        echo "</ul>";
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4 style='color: #721c24; margin: 0 0 10px 0;'>⚠️ Warning</h4>";
        echo "<p style='color: #721c24; margin: 0;'>The database setup completed but no hotels were found. Please check the database connection and try again.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4 style='color: #721c24; margin: 0 0 10px 0;'>❌ Error</h4>";
    echo "<p style='color: #721c24; margin: 0;'>Database setup failed: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";

// Add some styling
echo "<style>
body { font-family: Arial, sans-serif; background: #f8f9fa; margin: 0; padding: 20px; }
h2 { color: #003366; border-bottom: 2px solid #d4af37; padding-bottom: 10px; }
h3 { color: #0066cc; margin-top: 30px; }
p { margin: 8px 0; }
a { color: #0066cc; text-decoration: none; }
a:hover { text-decoration: underline; }
hr { border: none; border-top: 1px solid #ddd; margin: 30px 0; }
ul { margin: 10px 0; padding-left: 20px; }
li { margin: 5px 0; }
</style>";
?>
