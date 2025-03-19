<?php
function getAnalyticsDonorStats($conn) {
    $stats = [
        'approved' => 0,
        'rejected' => 0,
        'pending' => 0
    ];
    
    try {
        $stmt = $conn->prepare("SELECT request_status, COUNT(*) as count FROM donor GROUP BY request_status");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = strtolower($row['request_status']);
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
        $stmt = $conn->prepare("SELECT request_status, COUNT(*) as count FROM recipient_registration GROUP BY request_status");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = strtolower($row['request_status']);
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
        $stmt = $conn->prepare("SELECT request_status, COUNT(*) as count FROM hospitals GROUP BY request_status");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = strtolower($row['request_status']);
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
        $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM organ_matches GROUP BY status");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = strtolower($row['status']);
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
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM donor WHERE request_status = 'rejected'");
        $stmt->execute();
        $stats['donor_rejections'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Get recipient rejections
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM recipient_registration WHERE request_status = 'rejected'");
        $stmt->execute();
        $stats['recipient_rejections'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Get hospital rejections
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM hospitals WHERE request_status = 'rejected'");
        $stmt->execute();
        $stats['hospital_rejections'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        error_log("Error getting rejection stats: " . $e->getMessage());
    }
    
    return $stats;
}
?>
