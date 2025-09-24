<?php
require_once 'db.php';

// Get booking parameters
$hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;
$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
$checkin = isset($_GET['checkin']) ? $_GET['checkin'] : '';
$checkout = isset($_GET['checkout']) ? $_GET['checkout'] : '';
$guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 2;
$price = isset($_GET['price']) ? (float)$_GET['price'] : 0;

// Check if this is a booking confirmation
$booking_confirmed = isset($_GET['confirmed']) && $_GET['confirmed'] == '1';
$booking_reference = isset($_GET['reference']) ? $_GET['reference'] : '';

if (!$booking_confirmed && (!$hotel_id || !$room_id || !$checkin || !$checkout)) {
    header('Location: hotels.php');
    exit;
}

// Get hotel and room details for booking form
if (!$booking_confirmed) {
    $hotel_query = "SELECT h.*, l.city, l.country FROM hotels h LEFT JOIN locations l ON h.location_id = l.location_id WHERE h.hotel_id = ?";
    $hotel = $database->getSingle($hotel_query, [$hotel_id]);
    
    $room_query = "SELECT * FROM rooms WHERE room_id = ? AND hotel_id = ?";
    $room = $database->getSingle($room_query, [$room_id, $hotel_id]);
    
    if (!$hotel || !$room) {
        header('Location: hotels.php');
        exit;
    }
    
    // Calculate nights and total
    $checkin_date = new DateTime($checkin);
    $checkout_date = new DateTime($checkout);
    $nights = $checkin_date->diff($checkout_date)->days;
    $subtotal = $price * $nights;
    $taxes = $subtotal * 0.12; // 12% tax
    $fees = 25; // Service fee
    $total = $subtotal + $taxes + $fees;
} else {
    // Get booking details for confirmation
    $booking_query = "
        SELECT b.*, h.name as hotel_name, h.address, l.city, l.country, r.room_type
        FROM bookings b
        JOIN hotels h ON b.hotel_id = h.hotel_id
        LEFT JOIN locations l ON h.location_id = l.location_id
        JOIN rooms r ON b.room_id = r.room_id
        WHERE b.booking_reference = ?
    ";
    $booking = $database->getSingle($booking_query, [$booking_reference]);
    
    if (!$booking) {
        header('Location: hotels.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $booking_confirmed ? 'Booking Confirmed' : 'Complete Your Booking'; ?> - Hilton Hotels</title>
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

        .nav-links a:hover {
            color: var(--accent-gold);
            background: rgba(255, 255, 255, 0.1);
        }

        /* Main Content */
        .main-content {
            padding: 3rem 0;
        }

        .booking-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 3rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .booking-form-section {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: var(--shadow);
        }

        .booking-summary-section {
            background: var(--white);
            padding: 2.5rem;
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
            margin-bottom: 2rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--accent-gold);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--border-light);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary-blue);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .required {
            color: var(--error-red);
        }

        /* Booking Summary */
        .hotel-summary {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-light);
        }

        .hotel-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
        }

        .hotel-location {
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .booking-details {
            background: var(--background-light);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            color: var(--text-light);
        }

        .detail-value {
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Price Breakdown */
        .price-breakdown {
            border-top: 1px solid var(--border-light);
            padding-top: 1.5rem;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
        }

        .price-row.total {
            border-top: 1px solid var(--border-light);
            padding-top: 1rem;
            margin-top: 1rem;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary-blue);
        }

        /* Buttons */
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-gold) 0%, #b8941f 100%);
            color: var(--white);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .btn-secondary {
            background: var(--border-light);
            color: var(--text-dark);
            margin-right: 1rem;
        }

        .btn-secondary:hover {
            background: var(--text-light);
            color: var(--white);
        }

        .btn-full {
            width: 100%;
            margin-top: 1rem;
        }

        /* Confirmation Styles */
        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--white);
            padding: 3rem;
            border-radius: 15px;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .confirmation-icon {
            font-size: 4rem;
            color: var(--success-green);
            margin-bottom: 2rem;
        }

        .confirmation-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .booking-reference {
            background: var(--background-light);
            padding: 1rem;
            border-radius: 8px;
            margin: 2rem 0;
            border-left: 4px solid var(--success-green);
        }

        .reference-label {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }

        .reference-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-blue);
            font-family: 'Courier New', monospace;
        }

        /* Error Messages */
        .error-message {
            background: rgba(220, 53, 69, 0.1);
            color: var(--error-red);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid var(--error-red);
        }

        /* Loading */
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

        /* Responsive Design */
        @media (max-width: 1024px) {
            .booking-container {
                grid-template-columns: 1fr;
            }
            
            .booking-summary-section {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .booking-form-section,
            .booking-summary-section,
            .confirmation-container {
                padding: 2rem;
            }

            .confirmation-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 15px;
            }

            .booking-form-section,
            .booking-summary-section,
            .confirmation-container {
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

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <?php if ($booking_confirmed): ?>
                <!-- Booking Confirmation -->
                <div class="confirmation-container">
                    <div class="confirmation-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1 class="confirmation-title">Booking Confirmed!</h1>
                    <p>Thank you for choosing Hilton Hotels. Your reservation has been successfully confirmed.</p>
                    
                    <div class="booking-reference">
                        <div class="reference-label">Booking Reference Number</div>
                        <div class="reference-number"><?php echo htmlspecialchars($booking['booking_reference']); ?></div>
                    </div>
                    
                    <div class="booking-details">
                        <div class="detail-row">
                            <span class="detail-label">Hotel:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($booking['hotel_name']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Location:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($booking['city'] . ', ' . $booking['country']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Room Type:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($booking['room_type']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Guest Name:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($booking['guest_first_name'] . ' ' . $booking['guest_last_name']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Check-in:</span>
                            <span class="detail-value"><?php echo formatDate($booking['check_in_date']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Check-out:</span>
                            <span class="detail-value"><?php echo formatDate($booking['check_out_date']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Total Amount:</span>
                            <span class="detail-value">$<?php echo number_format($booking['total_amount'], 2); ?></span>
                        </div>
                    </div>
                    
                    <p style="margin: 2rem 0; color: var(--text-light);">
                        A confirmation email has been sent to <?php echo htmlspecialchars($booking['guest_email']); ?>
                    </p>
                    
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="index.php" class="btn btn-secondary">Back to Home</a>
                        <a href="hotels.php" class="btn btn-primary">Book Another Stay</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Booking Form -->
                <div class="booking-container">
                    <div class="booking-form-section">
                        <h1 class="section-title">Complete Your Booking</h1>
                        
                        <form id="bookingForm" action="process-booking.php" method="POST">
                            <input type="hidden" name="hotel_id" value="<?php echo $hotel_id; ?>">
                            <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                            <input type="hidden" name="checkin" value="<?php echo $checkin; ?>">
                            <input type="hidden" name="checkout" value="<?php echo $checkout; ?>">
                            <input type="hidden" name="guests" value="<?php echo $guests; ?>">
                            <input type="hidden" name="nights" value="<?php echo $nights; ?>">
                            <input type="hidden" name="room_rate" value="<?php echo $price; ?>">
                            <input type="hidden" name="total_amount" value="<?php echo $total; ?>">
                            
                            <h3 style="margin-bottom: 1.5rem; color: var(--primary-blue);">Guest Information</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">First Name <span class="required">*</span></label>
                                    <input type="text" id="first_name" name="first_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="last_name">Last Name <span class="required">*</span></label>
                                    <input type="text" id="last_name" name="last_name" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email Address <span class="required">*</span></label>
                                    <input type="email" id="email" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone Number <span class="required">*</span></label>
                                    <input type="tel" id="phone" name="phone" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="arrival_time">Expected Arrival Time</label>
                                <select id="arrival_time" name="arrival_time">
                                    <option value="">Select arrival time</option>
                                    <option value="Before 12:00 PM">Before 12:00 PM</option>
                                    <option value="12:00 PM - 3:00 PM">12:00 PM - 3:00 PM</option>
                                    <option value="3:00 PM - 6:00 PM" selected>3:00 PM - 6:00 PM</option>
                                    <option value="6:00 PM - 9:00 PM">6:00 PM - 9:00 PM</option>
                                    <option value="After 9:00 PM">After 9:00 PM</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="special_requests">Special Requests</label>
                                <textarea id="special_requests" name="special_requests" placeholder="Any special requests or preferences..."></textarea>
                            </div>
                            
                            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                                <a href="hotel-details.php?id=<?php echo $hotel_id; ?>&checkin=<?php echo $checkin; ?>&checkout=<?php echo $checkout; ?>&guests=<?php echo $guests; ?>" class="btn btn-secondary">
                                    Back to Hotel
                                </a>
                                <button type="submit" class="btn btn-primary" style="flex: 1;">
                                    Confirm Booking
                                </button>
                            </div>
                        </form>
                        
                        <div class="loading" id="loadingDiv">
                            <div class="spinner"></div>
                            <p>Processing your booking...</p>
                        </div>
                    </div>
                    
                    <div class="booking-summary-section">
                        <h2 class="section-title">Booking Summary</h2>
                        
                        <div class="hotel-summary">
                            <div class="hotel-name"><?php echo htmlspecialchars($hotel['name']); ?></div>
                            <div class="hotel-location"><?php echo htmlspecialchars($hotel['city'] . ', ' . $hotel['country']); ?></div>
                        </div>
                        
                        <div class="booking-details">
                            <div class="detail-row">
                                <span class="detail-label">Room Type:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($room['room_type']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Check-in:</span>
                                <span class="detail-value"><?php echo formatDate($checkin); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Check-out:</span>
                                <span class="detail-value"><?php echo formatDate($checkout); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Guests:</span>
                                <span class="detail-value"><?php echo $guests; ?> Guest<?php echo $guests > 1 ? 's' : ''; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Nights:</span>
                                <span class="detail-value"><?php echo $nights; ?> Night<?php echo $nights > 1 ? 's' : ''; ?></span>
                            </div>
                        </div>
                        
                        <div class="price-breakdown">
                            <div class="price-row">
                                <span>Room Rate (<?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?>):</span>
                                <span>$<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="price-row">
                                <span>Taxes & Fees:</span>
                                <span>$<?php echo number_format($taxes, 2); ?></span>
                            </div>
                            <div class="price-row">
                                <span>Service Fee:</span>
                                <span>$<?php echo number_format($fees, 2); ?></span>
                            </div>
                            <div class="price-row total">
                                <span>Total Amount:</span>
                                <span>$<?php echo number_format($total, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Form submission handling
        document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading
            document.getElementById('loadingDiv').style.display = 'block';
            document.querySelector('.booking-form-section form').style.display = 'none';
            
            // Submit form
            const formData = new FormData(this);
            
            fetch('process-booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'booking.php?confirmed=1&reference=' + data.booking_reference;
                } else {
                    // Hide loading and show error
                    document.getElementById('loadingDiv').style.display = 'none';
                    document.querySelector('.booking-form-section form').style.display = 'block';
                    
                    // Show error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + (data.message || 'An error occurred. Please try again.');
                    
                    const form = document.querySelector('.booking-form-section form');
                    form.insertBefore(errorDiv, form.firstChild);
                    
                    // Remove error after 5 seconds
                    setTimeout(() => {
                        errorDiv.remove();
                    }, 5000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('loadingDiv').style.display = 'none';
                document.querySelector('.booking-form-section form').style.display = 'block';
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Network error. Please check your connection and try again.';
                
                const form = document.querySelector('.booking-form-section form');
                form.insertBefore(errorDiv, form.firstChild);
            });
        });

        // Form validation
        document.querySelectorAll('input[required]').forEach(input => {
            input.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    this.style.borderColor = 'var(--error-red)';
                } else {
                    this.style.borderColor = 'var(--border-light)';
                }
            });
        });

        // Email validation
        document.getElementById('email')?.addEventListener('blur', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(this.value)) {
                this.style.borderColor = 'var(--error-red)';
            } else {
                this.style.borderColor = 'var(--border-light)';
            }
        });

        // Phone validation
        document.getElementById('phone')?.addEventListener('input', function() {
            // Remove non-numeric characters
            this.value = this.value.replace(/\D/g, '');
            
            // Format as phone number
            if (this.value.length >= 6) {
                this.value = this.value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
            }
        });
    </script>
</body>
</html>
