<?php
require_once 'db.php';

// Get featured hotels
$featured_hotels_query = "
    SELECT h.*, l.city, l.country, hi.image_url,
           MIN(r.base_price) as starting_price
    FROM hotels h 
    LEFT JOIN locations l ON h.location_id = l.location_id
    LEFT JOIN hotel_images hi ON h.hotel_id = hi.hotel_id AND hi.is_primary = 1
    LEFT JOIN rooms r ON h.hotel_id = r.hotel_id AND r.is_active = 1
    WHERE h.is_featured = 1 AND h.is_active = 1
    GROUP BY h.hotel_id
    ORDER BY h.average_rating DESC
    LIMIT 6
";

$featured_hotels = $database->getMultiple($featured_hotels_query);

// Get top destinations
$destinations_query = "
    SELECT l.city, l.country, COUNT(h.hotel_id) as hotel_count
    FROM locations l
    LEFT JOIN hotels h ON l.location_id = h.location_id AND h.is_active = 1
    GROUP BY l.location_id
    HAVING hotel_count > 0
    ORDER BY hotel_count DESC
    LIMIT 8
";

$destinations = $database->getMultiple($destinations_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hilton Hotels - Luxury Accommodations Worldwide</title>
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
            --shadow: 0 4px 20px rgba(0, 51, 102, 0.1);
            --shadow-hover: 0 8px 30px rgba(0, 51, 102, 0.15);
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background-color: var(--white);
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

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(0, 51, 102, 0.7), rgba(0, 102, 204, 0.7)), 
                        url('/placeholder.svg?height=600&width=1200') center/cover;
            height: 70vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--white);
            position: relative;
        }

        .hero-content h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero-content p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            font-weight: 300;
        }

        /* Search Form */
        .search-container {
            background: var(--white);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: var(--shadow-hover);
            margin-top: 2rem;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-group input, .form-group select {
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--secondary-blue);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .search-btn {
            background: linear-gradient(135deg, var(--accent-gold) 0%, #b8941f 100%);
            color: var(--white);
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        /* Featured Hotels Section */
        .featured-section {
            padding: 5rem 0;
            background: var(--background-light);
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .section-header p {
            font-size: 1.1rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
        }

        .hotels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .hotel-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .hotel-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .hotel-image {
            height: 250px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .hotel-rating {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.7);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .hotel-info {
            padding: 1.5rem;
        }

        .hotel-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
        }

        .hotel-location {
            color: var(--text-light);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hotel-price {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-gold);
        }

        .price-label {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .view-btn {
            background: var(--primary-blue);
            color: var(--white);
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .view-btn:hover {
            background: var(--secondary-blue);
        }

        /* Destinations Section */
        .destinations-section {
            padding: 5rem 0;
            background: var(--white);
        }

        .destinations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 3rem;
        }

        .destination-card {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            color: var(--white);
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .destination-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        .destination-card h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .destination-card p {
            opacity: 0.9;
        }

        /* Footer */
        .footer {
            background: var(--primary-blue);
            color: var(--white);
            padding: 3rem 0 1rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            font-family: 'Playfair Display', serif;
            margin-bottom: 1rem;
            color: var(--accent-gold);
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-section ul li a {
            color: var(--white);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: var(--accent-gold);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 1rem;
            text-align: center;
            opacity: 0.8;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-content p {
                font-size: 1.1rem;
            }

            .search-form {
                grid-template-columns: 1fr;
            }

            .hotels-grid {
                grid-template-columns: 1fr;
            }

            .destinations-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 15px;
            }

            .search-container {
                padding: 1.5rem;
                margin: 1rem;
            }

            .hero-content h1 {
                font-size: 2rem;
            }
        }

        /* Loading Animation */
        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--secondary-blue);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Experience Luxury Redefined</h1>
            <p>Discover world-class accommodations and exceptional service at Hilton Hotels worldwide</p>
            
            <!-- Search Form -->
            <div class="search-container">
                <form class="search-form" id="searchForm">
                    <div class="form-group">
                        <label for="destination">Destination</label>
                        <input type="text" id="destination" name="destination" placeholder="Where are you going?" required>
                    </div>
                    <div class="form-group">
                        <label for="checkin">Check-in Date</label>
                        <input type="date" id="checkin" name="checkin" required>
                    </div>
                    <div class="form-group">
                        <label for="checkout">Check-out Date</label>
                        <input type="date" id="checkout" name="checkout" required>
                    </div>
                    <div class="form-group">
                        <label for="guests">Guests</label>
                        <select id="guests" name="guests">
                            <option value="1">1 Guest</option>
                            <option value="2" selected>2 Guests</option>
                            <option value="3">3 Guests</option>
                            <option value="4">4 Guests</option>
                            <option value="5">5+ Guests</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i> Search Hotels
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Featured Hotels Section -->
    <section class="featured-section">
        <div class="container">
            <div class="section-header">
                <h2>Featured Properties</h2>
                <p>Discover our most popular hotels offering exceptional experiences and unmatched luxury</p>
            </div>

            <div class="hotels-grid">
                <?php if ($featured_hotels): ?>
                    <?php foreach ($featured_hotels as $hotel): ?>
                        <div class="hotel-card" onclick="viewHotel(<?php echo $hotel['hotel_id']; ?>)">
                            <div class="hotel-image" style="background-image: url('<?php echo $hotel['image_url'] ?: '/placeholder.svg?height=250&width=400'; ?>')">
                                <div class="hotel-rating">
                                    <i class="fas fa-star"></i>
                                    <?php echo number_format($hotel['average_rating'], 1); ?>
                                </div>
                            </div>
                            <div class="hotel-info">
                                <h3 class="hotel-name"><?php echo htmlspecialchars($hotel['name']); ?></h3>
                                <div class="hotel-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($hotel['city'] . ', ' . $hotel['country']); ?>
                                </div>
                                <div class="hotel-price">
                                    <div>
                                        <div class="price">$<?php echo number_format($hotel['starting_price'], 0); ?></div>
                                        <div class="price-label">per night</div>
                                    </div>
                                    <button class="view-btn" onclick="event.stopPropagation(); viewHotel(<?php echo $hotel['hotel_id']; ?>)">
                                        View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="loading">
                        <div class="spinner"></div>
                        <p>Loading featured hotels...</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Destinations Section -->
    <section class="destinations-section" id="destinations">
        <div class="container">
            <div class="section-header">
                <h2>Popular Destinations</h2>
                <p>Explore our hotels in the world's most sought-after destinations</p>
            </div>

            <div class="destinations-grid">
                <?php if ($destinations): ?>
                    <?php foreach ($destinations as $destination): ?>
                        <div class="destination-card" onclick="searchDestination('<?php echo htmlspecialchars($destination['city']); ?>')">
                            <h3><?php echo htmlspecialchars($destination['city']); ?></h3>
                            <p><?php echo htmlspecialchars($destination['country']); ?></p>
                            <p><?php echo $destination['hotel_count']; ?> Hotels Available</p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Hilton Hotels</h3>
                    <p>Experience luxury and comfort at our world-class hotels and resorts.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="hotels.php">Find Hotels</a></li>
                        <li><a href="booking.php">My Bookings</a></li>
                        <li><a href="#contact">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Support</h3>
                    <ul>
                        <li><a href="#help">Help Center</a></li>
                        <li><a href="#cancellation">Cancellation Policy</a></li>
                        <li><a href="#terms">Terms & Conditions</a></li>
                        <li><a href="#privacy">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <ul>
                        <li><i class="fas fa-phone"></i> +1-800-HILTONS</li>
                        <li><i class="fas fa-envelope"></i> info@hilton.com</li>
                        <li><i class="fas fa-map-marker-alt"></i> Worldwide Locations</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Hilton Hotels. All rights reserved. | Luxury Accommodations Worldwide</p>
            </div>
        </div>
    </footer>

    <script>
        // Set minimum date to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('checkin').min = today;
            document.getElementById('checkout').min = today;
            
            // Set default dates
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const dayAfter = new Date();
            dayAfter.setDate(dayAfter.getDate() + 2);
            
            document.getElementById('checkin').value = tomorrow.toISOString().split('T')[0];
            document.getElementById('checkout').value = dayAfter.toISOString().split('T')[0];
        });

        // Update checkout minimum date when checkin changes
        document.getElementById('checkin').addEventListener('change', function() {
            const checkinDate = new Date(this.value);
            checkinDate.setDate(checkinDate.getDate() + 1);
            document.getElementById('checkout').min = checkinDate.toISOString().split('T')[0];
            
            // Update checkout if it's before new minimum
            const checkoutDate = new Date(document.getElementById('checkout').value);
            if (checkoutDate <= new Date(this.value)) {
                document.getElementById('checkout').value = checkinDate.toISOString().split('T')[0];
            }
        });

        // Search form submission
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const params = new URLSearchParams();
            
            for (let [key, value] of formData.entries()) {
                params.append(key, value);
            }
            
            // Redirect to hotels page with search parameters
            window.location.href = 'hotels.php?' + params.toString();
        });

        // View hotel details
        function viewHotel(hotelId) {
            window.location.href = 'hotel-details.php?id=' + hotelId;
        }

        // Search by destination
        function searchDestination(city) {
            const today = new Date();
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const dayAfter = new Date();
            dayAfter.setDate(dayAfter.getDate() + 2);
            
            const params = new URLSearchParams({
                destination: city,
                checkin: tomorrow.toISOString().split('T')[0],
                checkout: dayAfter.toISOString().split('T')[0],
                guests: '2'
            });
            
            window.location.href = 'hotels.php?' + params.toString();
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add loading animation for hotel cards
        function showLoading() {
            document.querySelector('.loading').style.display = 'block';
        }

        // Add hover effects and animations
        document.querySelectorAll('.hotel-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Add search suggestions (basic implementation)
        const destinations = <?php echo json_encode(array_column($destinations, 'city')); ?>;
        const destinationInput = document.getElementById('destination');
        
        destinationInput.addEventListener('input', function() {
            // This could be enhanced with a proper autocomplete dropdown
            const value = this.value.toLowerCase();
            const suggestions = destinations.filter(dest => 
                dest.toLowerCase().includes(value)
            );
            
            // Basic validation feedback
            if (value.length > 2 && suggestions.length === 0) {
                this.style.borderColor = '#ff6b6b';
            } else {
                this.style.borderColor = '#e1e5e9';
            }
        });
    </script>
</body>
</html>
