<?php
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get form data
    $hotel_id = (int)$_POST['hotel_id'];
    $room_id = (int)$_POST['room_id'];
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];
    $guests = (int)$_POST['guests'];
    $nights = (int)$_POST['nights'];
    $room_rate = (float)$_POST['room_rate'];
    $total_amount = (float)$_POST['total_amount'];
    
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $arrival_time = sanitizeInput($_POST['arrival_time']);
    $special_requests = sanitizeInput($_POST['special_requests']);
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit;
    }
    
    // Validate dates
    $checkin_date = new DateTime($checkin);
    $checkout_date = new DateTime($checkout);
    $today = new DateTime();
    
    if ($checkin_date < $today) {
        echo json_encode(['success' => false, 'message' => 'Check-in date cannot be in the past']);
        exit;
    }
    
    if ($checkout_date <= $checkin_date) {
        echo json_encode(['success' => false, 'message' => 'Check-out date must be after check-in date']);
        exit;
    }
    
    // Check room availability
    $availability_query = "
        SELECT available_rooms FROM rooms 
        WHERE room_id = ? AND hotel_id = ? AND is_active = 1
    ";
    $room = $database->getSingle($availability_query, [$room_id, $hotel_id]);
    
    if (!$room || $room['available_rooms'] < 1) {
        echo json_encode(['success' => false, 'message' => 'Sorry, this room is no longer available']);
        exit;
    }
    
    // Generate booking reference
    $booking_reference = generateBookingReference();
    
    // Calculate taxes and fees
    $subtotal = $room_rate * $nights;
    $taxes = $subtotal * 0.12;
    $fees = 25;
    $calculated_total = $subtotal + $taxes + $fees;
    
    // Verify total amount
    if (abs($calculated_total - $total_amount) > 0.01) {
        echo json_encode(['success' => false, 'message' => 'Price calculation error. Please try again']);
        exit;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Insert booking
        $booking_query = "
            INSERT INTO bookings (
                booking_reference, hotel_id, room_id, guest_first_name, guest_last_name,
                guest_email, guest_phone, check_in_date, check_out_date, nights,
                adults, children, room_rate, taxes, fees, total_amount,
                special_requests, arrival_time, booking_status, payment_status,
                created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, 'Confirmed', 'Pending', NOW()
            )
        ";
        
        $booking_params = [
            $booking_reference, $hotel_id, $room_id, $first_name, $last_name,
            $email, $phone, $checkin, $checkout, $nights,
            $guests, $room_rate, $taxes, $fees, $total_amount,
            $special_requests, $arrival_time
        ];
        
        $booking_id = $database->insert($booking_query, $booking_params);
        
        if (!$booking_id) {
            throw new Exception('Failed to create booking');
        }
        
        // Update room availability
        $update_room_query = "
            UPDATE rooms 
            SET available_rooms = available_rooms - 1 
            WHERE room_id = ? AND available_rooms > 0
        ";
        
        $updated_rows = $database->execute($update_room_query, [$room_id]);
        
        if ($updated_rows === 0) {
            throw new Exception('Room is no longer available');
        }
        
        // Commit transaction
        $db->commit();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'booking_reference' => $booking_reference,
            'booking_id' => $booking_id,
            'message' => 'Booking confirmed successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Booking error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your booking. Please try again.'
    ]);
}
?>
