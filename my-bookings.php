<?php
require_once 'db.php';

// For demo purposes, we'll show all bookings
// In a real app, this would be filtered by user session
$bookings_query = "
    SELECT b.*, h.name as hotel_name, h.address, l.city, l.country, r.room_type,
           hi.image_url as hotel_image
    FROM bookings b
    JOIN hotels h ON b.hotel_id = h.hotel_id
    LEFT JOIN locations l ON h.location_id = l.location_id
    JOIN rooms r ON b.room_id = r.room_id
    LEFT JOIN hotel_images hi ON h.hotel_id = hi.hotel_id AND hi.is_primary = 1
    ORDER BY b.created_at DESC
    LIMIT 20
";

$bookings = $database->getMultiple($bookings_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Hilton Hotels</title>
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
            --error-red: #dc3545;
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

        .nav-links a:hover, .nav-links a.active {
            color: var(--accent-gold);
            background: rgba(255, 255, 255, 0.1);
        }

        /* Mobile Menu */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .mobile-nav {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--primary-blue);
            box-shadow: var(--shadow);
        }

        .mobile-nav.active {
            display: block;
        }

        .mobile-nav ul {
            list-style: none;
            padding: 1rem 0;
        }

        .mobile-nav ul li {
            padding: 0.5rem 2rem;
        }

        .mobile-nav ul li a {
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
        }

        /* Page Header */
        .page-header {
            background: var(--white);
            padding: 3rem 0;
            text-align: center;
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .page-subtitle {
            color: var(--text-light);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Bookings Section */
        .bookings-section {
            padding: 3rem 0;
        }

        .bookings-grid {
            display: grid;
            gap: 2rem;
        }

        .booking-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .booking-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }

        .booking-content {
            display: grid;
            grid-template-columns: 250px 1fr auto;
            gap: 2rem;
            padding: 2rem;
        }

        .booking-image {
            height: 150px;
            background-size: cover;
            background-position: center;
            border-radius: 10px;
            position: relative;
        }

        .booking-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-confirmed {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-green);
        }

        .status-cancelled {
            background: rgba(220, 53, 69, 0.1);
            color: var(--error-red);
        }

        .status-completed {
            background: rgba(0, 51, 102, 0.1);
            color: var(--primary-blue);
        }

        .booking-info {
            flex: 1;
        }

        .hotel-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
        }

        .hotel-location {
            color: var(--text-light);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .booking-detail {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 0.2rem;
        }

        .detail-value {
            font-weight: 600;
            color: var(--text-dark);
        }

        .booking-reference {
            background: var(--background-light);
            padding: 0.8rem;
            border-radius: 8px;
            border-left: 4px solid var(--accent-gold);
            margin-top: 1rem;
        }

        .reference-label {
            color: var(--text-light);
            font-size: 0.8rem;
            margin-bottom: 0.2rem;
        }

        .reference-number {
            font-weight: 700;
            color: var(--primary-blue);
            font-family: 'Courier New', monospace;
        }

        .booking-actions {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-end;
            min-width: 200px;
        }

        .booking-price {
            text-align: right;
            margin-bottom: 1rem;
        }

        .price-label {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }

        .price-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-gold);
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            width: 100%;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--secondary-blue) 0%, var(--primary-blue) 100%);
            color: var(--white);
        }

        .btn-secondary {
            background: var(--border-light);
            color: var(--text-dark);
        }

        .btn-danger {
            background: rgba(220, 53, 69, 0.1);
            color: var(--error-red);
            border: 1px solid var(--error-red);
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--white);
            border-radius: 15px;
            box-shadow: var(--shadow);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--border-light);
            margin-bottom: 2rem;
        }

        .empty-state h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--text-light);
            margin-bottom: 2rem;
        }

        .empty-state .btn {
            background: linear-gradient(135deg, var(--accent-gold) 0%, #b8941f 100%);
            color: var(--white);
            padding: 1rem 2rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .page-title {
                font-size: 2rem;
            }

            .booking-content {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .booking-actions {
                align-items: flex-start;
                min-width: auto;
            }

            .booking-details {
                grid-template-columns: repeat(2, 1fr);
            }

            .action-buttons {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .btn {
                flex: 1;
                min-width: 120px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 15px;
            }

            .booking-content {
                padding: 1.5rem;
            }

            .booking-details {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }
        }

        /* Animation for loading */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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
                <li><a href="my-bookings.php" class="active">My Bookings</a></li>
            </ul>
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="mobile-nav" id="mobileNav">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="hotels.php">Hotels</a></li>
                    <li><a href="#destinations">Destinations</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li><a href="my-bookings.php">My Bookings</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">My Bookings</h1>
            <p class="page-subtitle">Manage your hotel reservations and view booking history</p>
        </div>
    </section>

    <!-- Bookings Section -->
    <section class="bookings-section">
        <div class="container">
            <?php if (empty($bookings)): ?>
                <div class="empty-state fade-in">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Bookings Found</h3>
                    <p>You haven't made any hotel reservations yet. Start planning your next getaway!</p>
                    <a href="hotels.php" class="btn">
                        <i class="fas fa-search"></i> Find Hotels
                    </a>
                </div>
            <?php else: ?>
                <div class="bookings-grid">
                    <?php foreach ($bookings as $index => $booking): ?>
                        <div class="booking-card fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                            <div class="booking-content">
                                <div class="booking-image" style="background-image: url('<?php echo $booking['hotel_image'] ?: '/placeholder.svg?height=150&width=250'; ?>')">
                                    <div class="booking-status status-<?php echo strtolower($booking['booking_status']); ?>">
                                        <?php echo ucfirst($booking['booking_status']); ?>
                                    </div>
                                </div>

                                <div class="booking-info">
                                    <h3 class="hotel-name"><?php echo htmlspecialchars($booking['hotel_name']); ?></h3>
                                    <div class="hotel-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($booking['city'] . ', ' . $booking['country']); ?>
                                    </div>

                                    <div class="booking-details">
                                        <div class="booking-detail">
                                            <span class="detail-label">Room Type</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($booking['room_type']); ?></span>
                                        </div>
                                        <div class="booking-detail">
                                            <span class="detail-label">Check-in</span>
                                            <span class="detail-value"><?php echo formatDate($booking['check_in_date']); ?></span>
                                        </div>
                                        <div class="booking-detail">
                                            <span class="detail-label">Check-out</span>
                                            <span class="detail-value"><?php echo formatDate($booking['check_out_date']); ?></span>
                                        </div>
                                        <div class="booking-detail">
                                            <span class="detail-label">Guests</span>
                                            <span class="detail-value"><?php echo $booking['adults']; ?> Adult<?php echo $booking['adults'] > 1 ? 's' : ''; ?></span>
                                        </div>
                                    </div>

                                    <div class="booking-reference">
                                        <div class="reference-label">Booking Reference</div>
                                        <div class="reference-number"><?php echo htmlspecialchars($booking['booking_reference']); ?></div>
                                    </div>
                                </div>

                                <div class="booking-actions">
                                    <div class="booking-price">
                                        <div class="price-label">Total Paid</div>
                                        <div class="price-amount">$<?php echo number_format($booking['total_amount'], 0); ?></div>
                                    </div>

                                    <div class="action-buttons">
                                        <a href="hotel-details.php?id=<?php echo $booking['hotel_id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-eye"></i> View Hotel
                                        </a>
                                        <?php if ($booking['booking_status'] == 'Confirmed'): ?>
                                            <button class="btn btn-secondary" onclick="modifyBooking('<?php echo $booking['booking_reference']; ?>')">
                                                <i class="fas fa-edit"></i> Modify
                                            </button>
                                            <button class="btn btn-danger" onclick="cancelBooking('<?php echo $booking['booking_reference']; ?>')">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const mobileNav = document.getElementById('mobileNav');
            const menuBtn = document.querySelector('.mobile-menu-btn i');
            
            mobileNav.classList.toggle('active');
            
            if (mobileNav.classList.contains('active')) {
                menuBtn.className = 'fas fa-times';
            } else {
                menuBtn.className = 'fas fa-bars';
            }
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const mobileNav = document.getElementById('mobileNav');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (!menuBtn.contains(e.target) && !mobileNav.contains(e.target)) {
                mobileNav.classList.remove('active');
                document.querySelector('.mobile-menu-btn i').className = 'fas fa-bars';
            }
        });

        // Modify booking
        function modifyBooking(bookingReference) {
            alert('Modify booking functionality would be implemented here for booking: ' + bookingReference);
            // In a real app, this would redirect to a modification form
        }

        // Cancel booking
        function cancelBooking(bookingReference) {
            if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
                // In a real app, this would make an AJAX call to cancel the booking
                alert('Booking cancellation would be processed here for: ' + bookingReference);
            }
        }

        // Add smooth hover effects
        document.querySelectorAll('.booking-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Add loading animation for buttons
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (this.getAttribute('href') || this.getAttribute('onclick')) {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                }
            });
        });
    </script>
</body>
</html>
