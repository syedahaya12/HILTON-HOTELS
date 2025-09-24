<?php
require_once 'db.php';

// Get hotel ID and search parameters
$hotel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$checkin = isset($_GET['checkin']) ? $_GET['checkin'] : date('Y-m-d', strtotime('+1 day'));
$checkout = isset($_GET['checkout']) ? $_GET['checkout'] : date('Y-m-d', strtotime('+2 days'));
$guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 2;

if (!$hotel_id) {
    header('Location: hotels.php');
    exit;
}

// Get hotel details
$hotel_query = "
    SELECT h.*, l.city, l.country, l.state
    FROM hotels h 
    LEFT JOIN locations l ON h.location_id = l.location_id
    WHERE h.hotel_id = ? AND h.is_active = 1
";
$hotel = $database->getSingle($hotel_query, [$hotel_id]);

if (!$hotel) {
    header('Location: hotels.php');
    exit;
}

// Get hotel images
$images_query = "SELECT * FROM hotel_images WHERE hotel_id = ? ORDER BY is_primary DESC, display_order ASC";
$hotel_images = $database->getMultiple($images_query, [$hotel_id]);

// Get hotel amenities
$amenities_query = "
    SELECT a.* FROM amenities a
    JOIN hotel_amenities ha ON a.amenity_id = ha.amenity_id
    WHERE ha.hotel_id = ?
    ORDER BY a.name
";
$hotel_amenities = $database->getMultiple($amenities_query, [$hotel_id]);

// Get available rooms
$rooms_query = "
    SELECT r.*, 
           GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') as room_amenities,
           ri.image_url as room_image
    FROM rooms r
    LEFT JOIN room_amenities ra ON r.room_id = ra.room_id
    LEFT JOIN amenities a ON ra.amenity_id = a.amenity_id
    LEFT JOIN room_images ri ON r.room_id = ri.room_id AND ri.is_primary = 1
    WHERE r.hotel_id = ? AND r.is_active = 1 AND r.max_occupancy >= ?
    GROUP BY r.room_id
    ORDER BY r.base_price ASC
";
$rooms = $database->getMultiple($rooms_query, [$hotel_id, $guests]);

// Calculate nights
$checkin_date = new DateTime($checkin);
$checkout_date = new DateTime($checkout);
$nights = $checkin_date->diff($checkout_date)->days;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hotel['name']); ?> - Hilton Hotels</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-blue: #003366;
            --secondary-blue: #0066cc;
            --accent-gold: #d4af37;
            --light-gold: #f5e6a3;
            --text-dark: #1a1a1a;
            --text-light: #666666;
            --background-light: #f8f9fa;
            --white: #ffffff;
            --border-light: #e1e5e9;
            --shadow: 0 4px 20px rgba(0, 51, 102, 0.1);
            --shadow-hover: 0 8px 30px rgba(0, 51, 102, 0.15);
            --success-green: #28a745;
            --warning-orange: #fd7e14;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background-color: var(--background-light);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            color: var(--white);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow);
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent-gold);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 25px;
        }

        .nav-links a:hover {
            color: var(--accent-gold);
            background: rgba(255, 255, 255, 0.1);
        }

        /* Breadcrumb */
        .breadcrumb {
            background: var(--white);
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-light);
        }

        .breadcrumb-nav {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-light);
        }

        .breadcrumb-nav a {
            color: var(--secondary-blue);
            text-decoration: none;
        }

        .breadcrumb-nav a:hover {
            text-decoration: underline;
        }

        /* Hotel Header */
        .hotel-header {
            background: var(--white);
            padding: 2rem 0;
        }

        .hotel-title {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .hotel-info h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
        }

        .hotel-location {
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .hotel-rating {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .rating-stars {
            color: var(--accent-gold);
            font-size: 1.2rem;
        }

        .rating-text {
            font-weight: 600;
            color: var(--text-dark);
        }

        .rating-count {
            color: var(--text-light);
        }

        .booking-summary {
            background: var(--background-light);
            padding: 1.5rem;
            border-radius: 10px;
            border: 2px solid var(--accent-gold);
        }

        .booking-summary h3 {
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .booking-detail {
            text-align: center;
        }

        .booking-detail .label {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }

        .booking-detail .value {
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Image Gallery */
        .image-gallery {
            background: var(--white);
            padding: 2rem 0;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            grid-template-rows: 300px 300px;
            gap: 1rem;
            border-radius: 15px;
            overflow: hidden;
        }

        .gallery-item {
            background-size: cover;
            background-position: center;
            cursor: pointer;
            transition: transform 0.3s ease;
            position: relative;
        }

        .gallery-item:hover {
            transform: scale(1.02);
        }

        .gallery-item.main {
            grid-row: span 2;
        }

        .gallery-overlay {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.7);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
        }

        /* Hotel Details */
        .hotel-details {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
            padding: 3rem 0;
        }

        .details-content {
            background: var(--white);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow);
        }

        .details-sidebar {
            background: var(--white);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow);
            height: fit-content;
            position: sticky;
            top: 120px;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: var(--primary-blue);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--accent-gold);
        }

        .hotel-description {
            color: var(--text-dark);
            line-height: 1.8;
            margin-bottom: 2rem;
        }

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .amenity-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem;
            background: var(--background-light);
            border-radius: 8px;
            border-left: 4px solid var(--accent-gold);
        }

        .amenity-item i {
            color: var(--secondary-blue);
            width: 20px;
        }

        /* Rooms Section */
        .rooms-section {
            background: var(--white);
            padding: 3rem 0;
        }

        .rooms-grid {
            display: grid;
            gap: 2rem;
            margin-top: 2rem;
        }

        .room-card {
            background: var(--white);
            border: 1px solid var(--border-light);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .room-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }

        .room-content {
            display: grid;
            grid-template-columns: 300px 1fr auto;
            gap: 2rem;
            padding: 2rem;
        }

        .room-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            border-radius: 10px;
        }

        .room-info h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
        }

        .room-features {
            display: flex;
            gap: 1.5rem;
            margin: 1rem 0;
            color: var(--text-light);
        }

        .room-features span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .room-description {
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .room-amenities {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .room-amenity {
            background: var(--background-light);
            color: var(--text-dark);
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            border: 1px solid var(--border-light);
        }

        .room-pricing {
            text-align: right;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 200px;
        }

        .price-info {
            margin-bottom: 1.5rem;
        }

        .original-price {
            color: var(--text-light);
            text-decoration: line-through;
            font-size: 0.9rem;
        }

        .current-price {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent-gold);
            line-height: 1;
        }

        .price-label {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .total-price {
            color: var(--text-dark);
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .availability-status {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .available {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-green);
        }

        .limited {
            background: rgba(253, 126, 20, 0.1);
            color: var(--warning-orange);
        }

        .book-room-btn {
            background: linear-gradient(135deg, var(--accent-gold) 0%, #b8941f 100%);
            color: var(--white);
            border: none;
            padding: 1rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .book-room-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        /* Contact Info */
        .contact-info {
            margin-top: 2rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: var(--background-light);
            border-radius: 8px;
        }

        .contact-item i {
            color: var(--secondary-blue);
            width: 20px;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .hotel-details {
                grid-template-columns: 1fr;
            }
            
            .details-sidebar {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .hotel-title {
                flex-direction: column;
                gap: 1rem;
            }

            .gallery-grid {
                grid-template-columns: 1fr;
                grid-template-rows: repeat(5, 200px);
            }

            .gallery-item.main {
                grid-row: span 1;
            }

            .room-content {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .room-pricing {
                text-align: left;
            }

            .booking-details {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 15px;
            }

            .hotel-info h1 {
                font-size: 2rem;
            }

            .room-content {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav container">
            <a href="index.php" class="logo">
                <i class="fas fa-hotel"></i>
                Hilton Hotels
            </a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="hotels.php">Hotels</a></li>
                <li><a href="#destinations">Destinations</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="booking.php">My Bookings</a></li>
            </ul>
        </nav>
    </header>

    <!-- Breadcrumb -->
    <section class="breadcrumb">
        <div class="container">
            <nav class="breadcrumb-nav">
                <a href="index.php">Home</a>
                <i class="fas fa-chevron-right"></i>
                <a href="hotels.php">Hotels</a>
                <i class="fas fa-chevron-right"></i>
                <span><?php echo htmlspecialchars($hotel['name']); ?></span>
            </nav>
        </div>
    </section>

    <!-- Hotel Header -->
    <section class="hotel-header">
        <div class="container">
            <div class="hotel-title">
                <div class="hotel-info">
                    <h1><?php echo htmlspecialchars($hotel['name']); ?></h1>
                    <div class="hotel-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($hotel['address']); ?>
                    </div>
                    <div class="hotel-rating">
                        <div class="rating-stars">
                            <?php
                            $rating = $hotel['average_rating'];
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating) {
                                    echo '<i class="fas fa-star"></i>';
                                } elseif ($i - 0.5 <= $rating) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <span class="rating-text"><?php echo number_format($hotel['average_rating'], 1); ?>/5</span>
                        <span class="rating-count">(<?php echo $hotel['total_reviews']; ?> reviews)</span>
                    </div>
                </div>
                
                <div class="booking-summary">
                    <h3>Your Stay</h3>
                    <div class="booking-details">
                        <div class="booking-detail">
                            <div class="label">Check-in</div>
                            <div class="value"><?php echo formatDate($checkin); ?></div>
                        </div>
                        <div class="booking-detail">
                            <div class="label">Check-out</div>
                            <div class="value"><?php echo formatDate($checkout); ?></div>
                        </div>
                        <div class="booking-detail">
                            <div class="label">Guests</div>
                            <div class="value"><?php echo $guests; ?> Guest<?php echo $guests > 1 ? 's' : ''; ?></div>
                        </div>
                        <div class="booking-detail">
                            <div class="label">Nights</div>
                            <div class="value"><?php echo $nights; ?> Night<?php echo $nights > 1 ? 's' : ''; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Image Gallery -->
    <section class="image-gallery">
        <div class="container">
            <div class="gallery-grid">
                <?php if (!empty($hotel_images)): ?>
                    <?php foreach (array_slice($hotel_images, 0, 5) as $index => $image): ?>
                        <div class="gallery-item <?php echo $index === 0 ? 'main' : ''; ?>" 
                             style="background-image: url('<?php echo $image['image_url'] ?: '/placeholder.svg?height=300&width=400'; ?>')"
                             onclick="openImageModal('<?php echo $image['image_url']; ?>')">
                            <?php if ($index === 4 && count($hotel_images) > 5): ?>
                                <div class="gallery-overlay">
                                    +<?php echo count($hotel_images) - 5; ?> more photos
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="gallery-item main" style="background-image: url('/placeholder.svg?height=600&width=800')"></div>
                    <div class="gallery-item" style="background-image: url('/placeholder.svg?height=300&width=400')"></div>
                    <div class="gallery-item" style="background-image: url('/placeholder.svg?height=300&width=400')"></div>
                    <div class="gallery-item" style="background-image: url('/placeholder.svg?height=300&width=400')"></div>
                    <div class="gallery-item" style="background-image: url('/placeholder.svg?height=300&width=400')"></div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Hotel Details -->
    <section class="hotel-details">
        <div class="container">
            <div class="details-content">
                <h2 class="section-title">About This Hotel</h2>
                <div class="hotel-description">
                    <?php echo nl2br(htmlspecialchars($hotel['description'])); ?>
                </div>

                <h3 class="section-title">Hotel Amenities</h3>
                <div class="amenities-grid">
                    <?php foreach ($hotel_amenities as $amenity): ?>
                        <div class="amenity-item">
                            <i class="fas fa-check"></i>
                            <span><?php echo htmlspecialchars($amenity['name']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="details-sidebar">
                <h3 class="section-title">Hotel Information</h3>
                
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <strong>Phone</strong><br>
                            <?php echo htmlspecialchars($hotel['phone'] ?: '+1-800-HILTONS'); ?>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>Email</strong><br>
                            <?php echo htmlspecialchars($hotel['email'] ?: 'info@hilton.com'); ?>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Check-in / Check-out</strong><br>
                            <?php echo date('g:i A', strtotime($hotel['check_in_time'])); ?> / 
                            <?php echo date('g:i A', strtotime($hotel['check_out_time'])); ?>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-star"></i>
                        <div>
                            <strong>Hotel Rating</strong><br>
                            <?php echo $hotel['star_rating']; ?> Star Hotel
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Available Rooms -->
    <section class="rooms-section">
        <div class="container">
            <h2 class="section-title">Available Rooms</h2>
            
            <div class="rooms-grid">
                <?php if (empty($rooms)): ?>
                    <div class="no-rooms">
                        <p>No rooms available for your selected dates and guest count. Please try different dates or reduce the number of guests.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <div class="room-card">
                            <div class="room-content">
                                <div class="room-image" style="background-image: url('<?php echo $room['room_image'] ?: '/placeholder.svg?height=200&width=300'; ?>')"></div>
                                
                                <div class="room-info">
                                    <h3><?php echo htmlspecialchars($room['room_type']); ?></h3>
                                    
                                    <div class="room-features">
                                        <span><i class="fas fa-users"></i> Up to <?php echo $room['max_occupancy']; ?> guests</span>
                                        <span><i class="fas fa-bed"></i> <?php echo htmlspecialchars($room['bed_type']); ?></span>
                                        <?php if ($room['room_size']): ?>
                                            <span><i class="fas fa-expand"></i> <?php echo $room['room_size']; ?> <?php echo $room['size_unit']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="room-description">
                                        <?php echo htmlspecialchars($room['description']); ?>
                                    </div>
                                    
                                    <?php if ($room['room_amenities']): ?>
                                        <div class="room-amenities">
                                            <?php 
                                            $amenities = explode(', ', $room['room_amenities']);
                                            foreach (array_slice($amenities, 0, 6) as $amenity): 
                                                if (!empty(trim($amenity))):
                                            ?>
                                                <span class="room-amenity"><?php echo htmlspecialchars(trim($amenity)); ?></span>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="room-pricing">
                                    <div class="price-info">
                                        <?php if ($room['weekend_price'] && $room['weekend_price'] != $room['base_price']): ?>
                                            <div class="original-price">$<?php echo number_format($room['weekend_price'], 0); ?></div>
                                        <?php endif; ?>
                                        <div class="current-price">$<?php echo number_format($room['base_price'], 0); ?></div>
                                        <div class="price-label">per night</div>
                                        <div class="total-price">
                                            Total: $<?php echo number_format($room['base_price'] * $nights, 0); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="availability-status <?php echo $room['available_rooms'] > 5 ? 'available' : 'limited'; ?>">
                                        <?php if ($room['available_rooms'] > 5): ?>
                                            <i class="fas fa-check-circle"></i> Available
                                        <?php else: ?>
                                            <i class="fas fa-exclamation-triangle"></i> Only <?php echo $room['available_rooms']; ?> left
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button class="book-room-btn" onclick="bookRoom(<?php echo $room['room_id']; ?>, <?php echo $room['base_price']; ?>)">
                                        Book This Room
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <script>
        // Book room function
        function bookRoom(roomId, price) {
            const checkin = '<?php echo $checkin; ?>';
            const checkout = '<?php echo $checkout; ?>';
            const guests = '<?php echo $guests; ?>';
            const hotelId = '<?php echo $hotel_id; ?>';
            
            const params = new URLSearchParams({
                hotel_id: hotelId,
                room_id: roomId,
                checkin: checkin,
                checkout: checkout,
                guests: guests,
                price: price
            });
            
            window.location.href = 'booking.php?' + params.toString();
        }

        // Open image modal (basic implementation)
        function openImageModal(imageUrl) {
            // This could be enhanced with a proper modal/lightbox
            window.open(imageUrl, '_blank');
        }

        // Smooth scrolling for room cards
        document.querySelectorAll('.room-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Gallery hover effects
        document.querySelectorAll('.gallery-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.03)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
