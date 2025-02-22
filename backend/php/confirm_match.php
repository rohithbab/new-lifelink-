<?php
session_start();
require_once 'connection.php';
require_once 'queries.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['donor_id']) || !isset($data['recipient_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    // Begin transaction
    $conn->beginTransaction();
    
    // Create match record
    $stmt = $conn->prepare("
        INSERT INTO organ_matches (
            donor_id,
            recipient_id,
            match_date,
            status,
            matched_by_admin
        ) VALUES (
            :donor_id,
            :recipient_id,
            NOW(),
            'pending',
            :admin_id
        )
    ");
    
    $stmt->execute([
        ':donor_id' => $data['donor_id'],
        ':recipient_id' => $data['recipient_id'],
        ':admin_id' => $_SESSION['admin_id']
    ]);
    
    $matchId = $conn->lastInsertId();
    
    // Update donor status
    $stmt = $conn->prepare("
        UPDATE donors
        SET status = 'matched',
            matched_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([':id' => $data['donor_id']]);
    
    // Update recipient status
    $stmt = $conn->prepare("
        UPDATE recipients
        SET status = 'matched',
            matched_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([':id' => $data['recipient_id']]);
    
    // Get donor and recipient details for notifications
    $stmt = $conn->prepare("
        SELECT 
            d.name as donor_name,
            d.hospital_id as donor_hospital,
            r.name as recipient_name,
            r.hospital_id as recipient_hospital,
            dh.email as donor_hospital_email,
            rh.email as recipient_hospital_email
        FROM donors d
        JOIN hospitals dh ON d.hospital_id = dh.id
        JOIN recipients r ON r.id = :recipient_id
        JOIN hospitals rh ON r.hospital_id = rh.id
        WHERE d.id = :donor_id
    ");
    $stmt->execute([
        ':donor_id' => $data['donor_id'],
        ':recipient_id' => $data['recipient_id']
    ]);
    $matchDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Create notifications
    $donorNotification = "Donor {$matchDetails['donor_name']} has been matched with recipient {$matchDetails['recipient_name']}";
    $recipientNotification = "Recipient {$matchDetails['recipient_name']} has been matched with donor {$matchDetails['donor_name']}";
    
    addNotification($conn, 'match_created', $donorNotification, $matchDetails['donor_hospital']);
    addNotification($conn, 'match_created', $recipientNotification, $matchDetails['recipient_hospital']);
    
    // Send email notifications
    require_once "../emails/match-email.php";
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Match confirmed successfully',
        'match_id' => $matchId
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error confirming match'
    ]);
}
?>
