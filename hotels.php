<?php
session_start();
require 'db.php';

$destination = isset($_GET['destination']) ? filter_var($_GET['destination'], FILTER_SANITIZE_STRING) : '';
$checkin = isset($_GET['checkin']) ? filter_var($_GET['checkin'], FILTER_SANITIZE_STRING) : '';
$checkout = isset($_GET['checkout']) ? filter_var($_GET['checkout'], FILTER_SANITIZE_STRING) : '';
$sort = isset($_GET['sort']) ? filter_var($_GET['sort'], FILTER_SANITIZE_STRING) : 'price_asc';

// Debug: Check connection and input
if (!$conn) {
    die("Database connection failed.");
}
echo "<!-- Debug: Destination: $destination, Check-in: $checkin, Check-out: $checkout, Sort: $sort -->";

// Fetch hotels from database
$hotels = [];
try {
    if (empty($destination)) {
        $query = "SELECT * FROM hotels";
        $stmt = $conn->prepare($query);
    } else {
        $query = "SELECT * FROM hotels WHERE LOWER(location) LIKE LOWER(?)";
        $stmt = $conn->prepare($query);
        $likeDestination = "%$destination%";
        $stmt->bind_param("s", $likeDestination);
    }
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    if (!$stmt->execute()) {
        die("Query execution failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    $hotels = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    echo "<!-- Debug: Found " . count($hotels) . " hotels -->";
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Sort hotels
if ($sort == 'price_desc') {
    usort($hotels, function($a, $b) { return $b['price'] - $a['price']; });
} else if ($sort == 'rating_desc') {
    usort($hotels, function($a, $b) { return $b['rating'] - $a['rating']; });
} else {
    usort($hotels, function($a, $b) { return $a['price'] - $b['price']; });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hilton Hotels - Listings</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }
        header {
            background: linear-gradient(to right, #004aad, #0073e6);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .filters {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 20px;
        }
        .filters select, .filters button {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .filters button {
            background: #004aad;
            color: white;
            cursor: pointer;
        }
        .filters button:hover {
            background: #0073e6;
        }
        .hotel-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            padding: 20px;
        }
        .hotel-card {
            background: white;
            width: 300px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        .hotel-card:hover {
            transform: scale(1.05);
        }
        .hotel-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .hotel-card h3, .hotel-card p {
            padding: 0 10px;
        }
        .hotel-card button {
            width: 100%;
            padding: 10px;
            background: #004aad;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 1em;
        }
        .hotel-card button:hover {
            background: #0073e6;
        }
        .no-results {
            text-align: center;
            font-size: 1.2em;
            color: #555;
            padding: 20px;
        }
        @media (max-width: 768px) {
            .hotel-card {
                width: 100%;
            }
            .filters {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Hilton Hotels - Listings</h1>
    </header>
    <div class="filters">
        <select id="sort" onchange="applySort()">
            <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
            <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
            <option value="rating_desc" <?php echo $sort == 'rating_desc' ? 'selected' : ''; ?>>Best Rated</option>
        </select>
        <button onclick="window.location.href='index.php'">Back to Home</button>
    </div>
    <div class="hotel-list">
        <?php if (empty($hotels)): ?>
            <p class="no-results">No hotels found. Try a different destination or clear filters.</p>
        <?php else: ?>
            <?php foreach ($hotels as $hotel): ?>
                <div class="hotel-card">
                    <img src="<?php echo htmlspecialchars($hotel['image'] ?? 'https://via.placeholder.com/300x200?text=No+Image'); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>">
                    <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
                    <p>Location: <?php echo htmlspecialchars($hotel['location']); ?></p>
                    <p>Price: $<?php echo htmlspecialchars(number_format($hotel['price'], 2)); ?>/night</p>
                    <p>Rating: <?php echo htmlspecialchars($hotel['rating']); ?></p>
                    <button onclick="bookHotel(<?php echo (int)$hotel['id']; ?>, '<?php echo urlencode($checkin); ?>', '<?php echo urlencode($checkout); ?>')">Book Now</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <script>
        function bookHotel(hotelId, checkin, checkout) {
            if (!hotelId || isNaN(hotelId) || hotelId <= 0) {
                console.error('Invalid hotel ID:', hotelId);
                alert('Error: Invalid hotel selection. Please try again.');
                return;
            }
            if (!checkin || !checkout) {
                console.error('Invalid dates:', { checkin, checkout });
                alert('Error: Missing check-in or check-out date.');
                return;
            }
            const url = 'booking.php?hotel_id=' + encodeURIComponent(hotelId) + '&checkin=' + encodeURIComponent(checkin) + '&checkout=' + encodeURIComponent(checkout);
            console.log('Redirecting to:', url);
            window.location.href = url;
        }
        function applySort() {
            const sort = document.getElementById('sort').value;
            const url = 'hotels.php?destination=<?php echo urlencode($destination); ?>&checkin=<?php echo urlencode($checkin); ?>&checkout=<?php echo urlencode($checkout); ?>&sort=' + encodeURIComponent(sort);
            console.log('Sorting:', url);
            window.location.href = url;
        }
    </script>
</body>
</html>
