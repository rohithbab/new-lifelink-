<?php
session_start();
require_once '../config/db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Content-Type: application/json');

// Check if hospital is logged in
if (!isset($_SESSION['hospital_logged_in']) || !$_SESSION['hospital_logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$hospital_id = $_SESSION['hospital_id'];

try {
    // Check if we have the required data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['request_id']) && !isset($data['action']) && !isset($data['recipientId']) && !isset($data['recipientHospitalId'])) {
        throw new Exception('Missing required parameters');
    }

    if (isset($data['request_id']) && isset($data['action'])) {
        $request_id = $data['request_id'];
        $action = $data['action'];
        $message = isset($data['message']) ? $data['message'] : null;
        $current_date = date('Y-m-d H:i:s');

        // Start transaction
        $conn->beginTransaction();

        // First, verify the request exists and belongs to this hospital
        $stmt = $conn->prepare("
            SELECT rr.*, r.full_name, r.id as recipient_id, ha.required_organ
            FROM recipient_requests rr
            JOIN recipient_registration r ON r.id = rr.recipient_id
            JOIN hospital_recipient_approvals ha ON ha.recipient_id = r.id
            WHERE rr.request_id = ? 
            AND rr.recipient_hospital_id = ? 
            AND rr.status = 'Pending'
        ");
        $stmt->execute([$request_id, $hospital_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            throw new Exception('Request not found or you do not have permission to modify it');
        }

        // Update request status based on action
        $status = ($action === 'approve') ? 'Approved' : 'Rejected';
        
        $stmt = $conn->prepare("
            UPDATE recipient_requests 
            SET status = ?,
                response_date = ?,
                response_message = ?
            WHERE request_id = ? 
        ");
        $stmt->execute([
            $status,
            $current_date,
            $message,
            $request_id
        ]);

        // If approved, create shared recipient approval for requesting hospital
        if ($status === 'Approved') {
            // Insert into shared_recipient_approvals
            $stmt = $conn->prepare("
                INSERT INTO shared_recipient_approvals (
                    recipient_id,
                    from_hospital_id,
                    to_hospital_id,
                    request_id,
                    organ_type,
                    share_date,
                    is_matched
                ) VALUES (?, ?, ?, ?, ?, NOW(), FALSE)
            ");
            $stmt->execute([
                $request['recipient_id'],
                $hospital_id,
                $request['requesting_hospital_id'],
                $request_id,
                $request['required_organ']
            ]);

            // Log the approval
            error_log("Recipient {$request['full_name']} shared with hospital {$request['requesting_hospital_id']}");
        }

        // Commit transaction
        $conn->commit();

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => "Request successfully " . strtolower($status)
        ]);
    } else if (isset($data['recipientId']) && isset($data['recipientHospitalId']) && isset($data['action'])) {
        $recipientId = $data['recipientId'];
        $recipientHospitalId = $data['recipientHospitalId'];
        $action = $data['action'];
        $requestingHospitalId = $hospital_id;

        if (!$recipientId || !$recipientHospitalId || !$action) {
            throw new Exception('Missing required parameters');
        }

        // Start transaction
        $conn->beginTransaction();

        if ($action === 'request') {
            // Check if there's already a pending or approved request
            $checkStmt = $conn->prepare("
                SELECT status 
                FROM recipient_requests 
                WHERE recipient_id = ? 
                AND requesting_hospital_id = ? 
                AND status IN ('Pending', 'Approved')
            ");
            $checkStmt->execute([$recipientId, $requestingHospitalId]);
            $existingRequest = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingRequest) {
                throw new Exception("A request already exists for this recipient");
            }

            // Insert new request
            $stmt = $conn->prepare("
                INSERT INTO recipient_requests 
                (recipient_id, requesting_hospital_id, recipient_hospital_id, request_date, status) 
                VALUES (?, ?, ?, NOW(), 'Pending')
            ");
            $stmt->execute([$recipientId, $requestingHospitalId, $recipientHospitalId]);

            $message = "Request sent successfully";
        } 
        else if ($action === 'cancel') {
            // Check if there's a pending request to cancel
            $stmt = $conn->prepare("
                UPDATE recipient_requests 
                SET status = 'Cancelled',
                    response_date = NOW(),
                    response_message = 'Request cancelled by requesting hospital'
                WHERE recipient_id = ? 
                AND requesting_hospital_id = ? 
                AND status = 'Pending'
            ");
            $stmt->execute([$recipientId, $requestingHospitalId]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("No pending request found to cancel");
            }

            $message = "Request cancelled successfully";
        }
        else {
            throw new Exception("Invalid action");
        }

        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        throw new Exception("Invalid action");
    }

} catch (Exception $e) {
    // Rollback transaction if active
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log the error
    error_log("Error in handle_recipient_request.php: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
