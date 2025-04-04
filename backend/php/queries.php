<?php
require_once 'connection.php';

// Get dashboard statistics
function getDashboardStats($conn, $tables = ['hospitals', 'donor', 'recipient_registration', 'organ_matches']) {
    $stats = [
        'total_hospitals' => 0,
        'total_donors' => 0,
        'total_recipients' => 0,
        'pending_hospitals' => 0,
        'successful_matches' => 0,
        'pending_matches' => 0,
        'urgent_recipients' => 0,
        'approved_hospitals' => 0
    ];
    
    try {
        // Check if tables exist before querying
        $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('hospitals', $tables)) {
            // Get total hospitals (approved only)
            $stmt = $conn->query("SELECT COUNT(*) FROM hospitals WHERE LOWER(status) = 'approved'");
            $stats['total_hospitals'] = $stmt->fetchColumn();
            
            // Get pending hospitals count
            $stmt = $conn->query("SELECT COUNT(*) FROM hospitals WHERE LOWER(status) = 'pending'");
            $stats['pending_hospitals'] = $stmt->fetchColumn();
            
            // Get approved hospitals
            $stmt = $conn->query("SELECT COUNT(*) FROM hospitals WHERE LOWER(status) = 'approved'");
            $stats['approved_hospitals'] = $stmt->fetchColumn();
        }
        
        if (in_array('donor', $tables)) {
            // Get total donors (approved only)
            $stmt = $conn->query("SELECT COUNT(*) FROM donor WHERE LOWER(status) = 'approved'");
            $stats['total_donors'] = $stmt->fetchColumn();
        }
        
        if (in_array('recipient_registration', $tables)) {
            // Get total recipients (accepted only)
            $stmt = $conn->query("SELECT COUNT(*) FROM recipient_registration WHERE LOWER(request_status) = 'accepted'");
            $stats['total_recipients'] = $stmt->fetchColumn();
            
            // Get urgent recipients count (only from accepted recipients)
            $stmt = $conn->query("SELECT COUNT(*) FROM recipient_registration WHERE urgency_level = 'High' AND LOWER(request_status) = 'accepted'");
            $stats['urgent_recipients'] = $stmt->fetchColumn();
        }
        
        if (in_array('organ_matches', $tables)) {
            // Get successful matches
            $stmt = $conn->query("SELECT COUNT(*) FROM organ_matches WHERE LOWER(status) = 'confirmed'");
            $stats['successful_matches'] = $stmt->fetchColumn();
            
            // Get pending matches
            $stmt = $conn->query("SELECT COUNT(*) FROM organ_matches WHERE LOWER(status) = 'pending'");
            $stats['pending_matches'] = $stmt->fetchColumn();
        }
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Error getting dashboard stats: " . $e->getMessage());
        return $stats; // Return default values if there's an error
    }
}

// Get pending hospitals
function getPendingHospitals($conn) {
    try {
        $stmt = $conn->prepare("SELECT hospital_id, name as hospital_name, email, phone, odml_id FROM hospitals WHERE LOWER(status) = 'pending'");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting pending hospitals: " . $e->getMessage());
        return [];
    }
}

// Get pending donors
function getPendingDonors($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                donor_id,
                name as full_name,
                email,
                phone,
                blood_group as blood_type,
                organs_to_donate,
                DATE_FORMAT(created_at, '%Y-%m-%d') as registration_date,
                status
            FROM 
                donor 
            WHERE 
                LOWER(status) = 'pending'
            ORDER BY 
                rejection_date DESC
        ");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Pending Donors Query Result: " . json_encode($result));
        return $result;
    } catch (PDOException $e) {
        error_log("Error getting pending donors: " . $e->getMessage());
        return [];
    }
}

// Get pending recipients
function getPendingRecipients($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                id as recipient_id,
                full_name,
                email,
                phone_number,
                blood_type,
                organ_required,
                urgency_level,
                DATE_FORMAT(rejected_at, '%Y-%m-%d') as registration_date,
                request_status as status
            FROM 
                recipient_registration 
            WHERE 
                LOWER(request_status) = 'pending'
            ORDER BY 
                id DESC
        ");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Pending Recipients Query Result: " . json_encode($result));
        return $result;
    } catch (PDOException $e) {
        error_log("Error getting pending recipients: " . $e->getMessage());
        return [];
    }
}

// Update hospital status
function updateHospitalStatus($conn, $hospital_id, $status) {
    try {
        // Convert status to proper case
        $status = ucfirst(strtolower($status)); // This will convert 'approved' to 'Approved', 'rejected' to 'Rejected'
        
        $stmt = $conn->prepare("UPDATE hospitals SET status = ? WHERE hospital_id = ?");
        $result = $stmt->execute([$status, $hospital_id]);
        
        if ($result) {
            // Add notification
            $message = "Hospital #$hospital_id has been " . strtolower($status);
            addSystemNotification($conn, 'hospital_status', $message);
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error updating hospital status: " . $e->getMessage());
        return false;
    }
}

// Get all donors with filters
function getDonors($conn, $filters = []) {
    $query = "SELECT * FROM donor WHERE 1=1";
    $params = [];
    
    if (!empty($filters['blood_type'])) {
        $query .= " AND blood_type = :blood_type";
        $params[':blood_type'] = $filters['blood_type'];
    }
    
    if (!empty($filters['organ_type'])) {
        $query .= " AND organ_type = :organ_type";
        $params[':organ_type'] = $filters['organ_type'];
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get urgent recipients with details
function getUrgentRecipients($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                r.*,
                COALESCE(om.match_count, 0) as potential_matches
            FROM 
                recipient_registration r
                LEFT JOIN (
                    SELECT recipient_id, COUNT(*) as match_count 
                    FROM organ_matches 
                    WHERE LOWER(status) = 'pending'
                    GROUP BY recipient_id
                ) om ON r.id = om.recipient_id
            WHERE 
                r.urgency_level = 'High'
                AND LOWER(r.request_status) = 'active'
            ORDER BY 
                r.registration_date DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting urgent recipients: " . $e->getMessage());
        return [];
    }
}

// Match donors with recipients
function findPotentialMatches($conn, $donor_id) {
    $stmt = $conn->prepare("
        SELECT r.* FROM recipient_registration r
        JOIN donor d ON d.blood_type = r.blood_type
        WHERE d.donor_id = :donor_id
        AND d.organ_type = r.organ_required
        AND LOWER(r.request_status) = 'waiting'
        ORDER BY r.urgency_level DESC, r.registration_date ASC
    ");
    $stmt->execute(['donor_id' => $donor_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Add notification
function addNotification($conn, $type, $message, $user_id = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (type, message, user_id) VALUES (?, ?, ?)");
    return $stmt->execute([$type, $message, $user_id]);
}

// Get admin notifications
function getAdminNotifications($conn, $limit = 10) {
    $stmt = $conn->prepare("
        SELECT 
            notification_id,
            type,
            action,
            entity_id,
            message,
            is_read,
            DATE_FORMAT(created_at, '%M %d, %Y at %h:%i %p') as formatted_time,
            link_url
        FROM notifications 
        ORDER BY created_at DESC 
        LIMIT :limit
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to add system notification
function addSystemNotification($conn, $type, $message) {
    // Check if notifications table exists
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'notifications'
    ");
    $stmt->execute();
    $exists = $stmt->fetchColumn();

    if (!$exists) {
        // Silently return if table doesn't exist
        return;
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO notifications (type, message, created_at, is_read) 
            VALUES (:type, :message, NOW(), 0)
        ");
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':message', $message);
        return $stmt->execute();
    } catch (PDOException $e) {
        // Log error but don't throw exception
        error_log("Error adding notification: " . $e->getMessage());
        return false;
    }
}

// Update donor status
function updateDonorStatus($conn, $donor_id, $status) {
    try {
        // First get the donor's email
        $stmt = $conn->prepare("SELECT email FROM donor WHERE donor_id = ?");
        $stmt->execute([$donor_id]);
        $donor = $stmt->fetch(PDO::FETCH_ASSOC);
        $donor_email = $donor ? $donor['email'] : 'Unknown';

        // Update the status
        $stmt = $conn->prepare("UPDATE donor SET status = ? WHERE donor_id = ?");
        $result = $stmt->execute([$status, $donor_id]);
        
        if ($result) {
            addSystemNotification($conn, 'donor_status', "Donor ($donor_email) status updated to $status");
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Failed to update status'];
    } catch (PDOException $e) {
        error_log("Error updating donor status: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error'];
    }
}

// Update recipient status
function updateRecipientStatus($conn, $recipient_id, $status) {
    try {
        // First get the recipient's email
        $stmt = $conn->prepare("SELECT email FROM recipient_registration WHERE id = ?");
        $stmt->execute([$recipient_id]);
        $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
        $recipient_email = $recipient ? $recipient['email'] : 'Unknown';

        // Update the status
        $stmt = $conn->prepare("
            UPDATE recipient_registration 
            SET request_status = :status 
            WHERE id = :recipient_id
        ");
        
        $result = $stmt->execute([
            ':status' => $status,
            ':recipient_id' => $recipient_id
        ]);

        if ($result) {
            // Add notification with email
            $message = "Recipient ($recipient_email) registration has been " . $status;
            addSystemNotification($conn, 'recipient_status', $message);
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Error updating recipient status: " . $e->getMessage());
        return false;
    }
}

// Analytics Functions
function getMonthlyStats($conn) {
    $stats = [
        'registrations' => [],
        'matches' => [],
        'completions' => []
    ];
    
    // Get monthly hospital registrations
    $stmt = $conn->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
        FROM hospitals
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month
    ");
    $stats['registrations'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Get monthly matches
    $stmt = $conn->query("
        SELECT DATE_FORMAT(match_date, '%Y-%m') as month,
        COUNT(*) as count
        FROM organ_matches
        WHERE match_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month
    ");
    $stats['matches'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    return $stats;
}

function getOrganTypeStats($conn) {
    $stmt = $conn->query("
        SELECT organ_type, COUNT(*) as count
        FROM donor
        GROUP BY organ_type
    ");
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function getBloodTypeStats($conn) {
    $stats = [];
    
    // Get donor blood type stats
    $stmt = $conn->query("
        SELECT blood_type, COUNT(*) as count
        FROM donor
        GROUP BY blood_type
    ");
    $stats['donors'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    return $stats;
}

function getSuccessfulMatches($conn) {
    $stmt = $conn->query("
        SELECT COUNT(*) as total,
        AVG(TIMESTAMPDIFF(DAY, match_date, completion_date)) as avg_days
        FROM organ_matches
        WHERE LOWER(status) = 'completed'
    ");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getRegionalStats($conn) {
    $stmt = $conn->query("
        SELECT 
            h.region,
            COUNT(DISTINCT h.hospital_id) as hospitals,
            COUNT(DISTINCT d.donor_id) as donors,
            COUNT(DISTINCT r.id) as recipients
        FROM hospitals h
        LEFT JOIN donor d ON d.hospital_id = h.hospital_id
        LEFT JOIN recipient_registration r ON r.hospital_id = h.hospital_id
        GROUP BY h.region
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to update hospital ODML ID
function updateHospitalODMLID($conn, $hospital_id, $odml_id) {
    try {
        // Update the ODML ID
        $stmt = $conn->prepare("UPDATE hospitals SET odml_id = ?, status = 'approved' WHERE hospital_id = ?");
        $result = $stmt->execute([$odml_id, $hospital_id]);
        return $result;
    } catch (PDOException $e) {
        error_log("Error updating hospital ODML ID: " . $e->getMessage());
        return false;
    }
}

// Function to update donor ODML ID
function updateDonorODMLID($conn, $donor_id, $odml_id) {
    try {
        // Update the ODML ID
        $stmt = $conn->prepare("UPDATE donor SET odml_id = ?, status = 'approved' WHERE donor_id = ?");
        $result = $stmt->execute([$odml_id, $donor_id]);
        return $result;
    } catch (PDOException $e) {
        error_log("Error updating donor ODML ID: " . $e->getMessage());
        return false;
    }
}

// Function to update recipient ODML ID
function updateRecipientODMLID($conn, $recipient_id, $odml_id) {
    try {
        error_log("Attempting to update recipient ODML ID. ID: " . $recipient_id . ", ODML ID: " . $odml_id);
        
        // First check if recipient exists
        $checkStmt = $conn->prepare("SELECT id FROM recipient_registration WHERE id = ?");
        $checkStmt->execute([$recipient_id]);
        if (!$checkStmt->fetch()) {
            error_log("Recipient not found with ID: " . $recipient_id);
            return false;
        }
        
        // Update the ODML ID and set status to accepted
        $stmt = $conn->prepare("UPDATE recipient_registration SET odml_id = ?, request_status = 'accepted' WHERE id = ?");
        $result = $stmt->execute([$odml_id, $recipient_id]);
        
        if ($result) {
            error_log("Successfully updated recipient ODML ID");
            return true;
        } else {
            error_log("Failed to update recipient ODML ID. SQL Error: " . implode(", ", $stmt->errorInfo()));
            return false;
        }
    } catch (PDOException $e) {
        error_log("Error updating recipient ODML ID: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        return false;
    }
}

// Function to create notification
function createNotification($conn, $type, $action, $entity_id, $message = '') {
    try {
        // If no custom message provided, create default message
        if (empty($message)) {
            $message = ucfirst($type);
            switch ($action) {
                case 'registered':
                    $message .= " has registered";
                    break;
                case 'accepted':
                    $message .= " has been accepted";
                    break;
                case 'rejected':
                    $message .= " has been rejected";
                    break;
            }
        }

        // Generate link URL based on type
        $link_url = '';
        switch ($type) {
            case 'hospital':
                $link_url = "view_hospital.php?id=" . $entity_id;
                break;
            case 'donor':
                $link_url = "view_donor.php?id=" . $entity_id;
                break;
            case 'recipient':
                $link_url = "view_recipient_details.php?id=" . $entity_id;
                break;
        }

        $stmt = $conn->prepare("
            INSERT INTO notifications (type, action, entity_id, message, link_url)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([$type, $action, $entity_id, $message, $link_url]);
    } catch (Exception $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

// Analytics Functions for Dashboard
function getAnalyticsDonorStats($conn) {
    $stats = [
        'approved' => 0,
        'rejected' => 0,
        'pending' => 0
    ];
    
    try {
        // Get status counts with case-insensitive comparison
        $stmt = $conn->prepare("
            SELECT 
                CASE 
                    WHEN LOWER(status) = 'approved' THEN 'approved'
                    WHEN LOWER(status) = 'rejected' THEN 'rejected'
                    WHEN LOWER(status) = 'pending' THEN 'pending'
                    ELSE LOWER(status)
                END as normalized_status,
                COUNT(*) as count 
            FROM donor 
            GROUP BY normalized_status
        ");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = $row['normalized_status'];
            if (isset($stats[$status])) {
                $stats[$status] = $row['count'];
            }
        }
    } catch (PDOException $e) {
        error_log("Error getting donor stats: " . $e->getMessage());
    }
    
    return $stats;
}

function getAnalyticsRecipientStats($conn) {
    $stats = [
        'approved' => 0,
        'rejected' => 0,
        'pending' => 0
    ];
    
    try {
        // Get status counts with case-insensitive comparison
        $stmt = $conn->prepare("
            SELECT 
                CASE 
                    WHEN LOWER(request_status) = 'accepted' THEN 'approved'
                    WHEN LOWER(request_status) = 'rejected' THEN 'rejected'
                    WHEN LOWER(request_status) = 'pending' THEN 'pending'
                    ELSE LOWER(request_status)
                END as normalized_status,
                COUNT(*) as count 
            FROM recipient_registration 
            GROUP BY normalized_status
        ");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = $row['normalized_status'];
            if (isset($stats[$status])) {
                $stats[$status] = $row['count'];
            }
        }
    } catch (PDOException $e) {
        error_log("Error getting recipient stats: " . $e->getMessage());
    }
    
    return $stats;
}

function getAnalyticsHospitalStats($conn) {
    $stats = [
        'approved' => 0,
        'rejected' => 0,
        'pending' => 0
    ];
    
    try {
        // Get status counts with case-insensitive comparison
        $stmt = $conn->prepare("
            SELECT 
                CASE 
                    WHEN LOWER(status) = 'approved' THEN 'approved'
                    WHEN LOWER(status) = 'rejected' THEN 'rejected'
                    WHEN LOWER(status) = 'pending' THEN 'pending'
                    ELSE LOWER(status)
                END as normalized_status,
                COUNT(*) as count 
            FROM hospitals 
            GROUP BY normalized_status
        ");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = $row['normalized_status'];
            if (isset($stats[$status])) {
                $stats[$status] = $row['count'];
            }
        }
    } catch (PDOException $e) {
        error_log("Error getting hospital stats: " . $e->getMessage());
    }
    
    return $stats;
}

function getAnalyticsOrganMatchStats($conn) {
    $stats = [
        'approved' => 0,
        'rejected' => 0,
        'pending' => 0
    ];
    
    try {
        // Get status counts with case-insensitive comparison
        $stmt = $conn->prepare("
            SELECT 
                CASE 
                    WHEN LOWER(status) = 'approved' THEN 'approved'
                    WHEN LOWER(status) = 'rejected' THEN 'rejected'
                    WHEN LOWER(status) = 'pending' THEN 'pending'
                    ELSE LOWER(status)
                END as normalized_status,
                COUNT(*) as count 
            FROM organ_matches 
            GROUP BY normalized_status
        ");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = $row['normalized_status'];
            if (isset($stats[$status])) {
                $stats[$status] = $row['count'];
            }
        }
    } catch (PDOException $e) {
        error_log("Error getting organ match stats: " . $e->getMessage());
    }
    
    return $stats;
}

function getAnalyticsTotalUsersStats($conn) {
    $stats = [
        'donors' => 0,
        'recipients' => 0,
        'hospitals' => 0
    ];
    
    try {
        // Get total donors
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM donor");
        $stmt->execute();
        $stats['donors'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Get total recipients
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM recipient_registration");
        $stmt->execute();
        $stats['recipients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Get total hospitals
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM hospitals");
        $stmt->execute();
        $stats['hospitals'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        error_log("Error getting total users stats: " . $e->getMessage());
    }
    
    return $stats;
}

function getAnalyticsRejectionStats($conn) {
    $stats = [
        'donor_rejections' => 0,
        'recipient_rejections' => 0,
        'hospital_rejections' => 0
    ];
    
    try {
        // Get donor rejections
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM donor WHERE LOWER(status) = 'rejected'");
        $stmt->execute();
        $stats['donor_rejections'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Get recipient rejections
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM recipient_registration WHERE LOWER(request_status) = 'rejected'");
        $stmt->execute();
        $stats['recipient_rejections'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Get hospital rejections
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM hospitals WHERE LOWER(status) = 'rejected'");
        $stmt->execute();
        $stats['hospital_rejections'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        error_log("Error getting rejection stats: " . $e->getMessage());
    }
    
    return $stats;
}

?>
