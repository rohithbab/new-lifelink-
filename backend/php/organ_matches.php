<?php
require_once 'connection.php';
require_once 'queries.php';

// Create organ_matches table if it doesn't exist
function createOrganMatchesTable($conn) {
    try {
        $sql = file_get_contents(__DIR__ . '/../sql/create_organ_matches.sql');
        $conn->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("Error creating organ_matches table: " . $e->getMessage());
        return false;
    }
}

// Add new organ match
function addOrganMatch($conn, $data) {
    try {
        // Start transaction
        $conn->beginTransaction();

        // Check donor approval and get donor hospital
        $donor_check = "SELECT hospital_id as donor_hospital_id 
                       FROM hospital_donor_approvals 
                       WHERE donor_id = :donor_id 
                       AND status = 'Approved' 
                       AND organ_type = :organ_type";
        
        $donor_stmt = $conn->prepare($donor_check);
        $donor_stmt->execute([
            'donor_id' => $data['donor_id'],
            'organ_type' => $data['organ_type']
        ]);
        
        if ($donor_stmt->rowCount() === 0) {
            throw new Exception("Donor not found or not approved for this organ type");
        }
        $donor_result = $donor_stmt->fetch(PDO::FETCH_ASSOC);
        $donor_hospital_id = $donor_result['donor_hospital_id'];

        // Check recipient approval and get recipient hospital
        $recipient_check = "SELECT hospital_id as recipient_hospital_id 
                          FROM hospital_recipient_approvals 
                          WHERE recipient_id = :recipient_id 
                          AND status = 'Approved' 
                          AND organ_required = :organ_type";
        
        $recipient_stmt = $conn->prepare($recipient_check);
        $recipient_stmt->execute([
            'recipient_id' => $data['recipient_id'],
            'organ_type' => $data['organ_type']
        ]);
        
        if ($recipient_stmt->rowCount() === 0) {
            throw new Exception("Recipient not found or not approved for this organ type");
        }
        $recipient_result = $recipient_stmt->fetch(PDO::FETCH_ASSOC);
        $recipient_hospital_id = $recipient_result['recipient_hospital_id'];

        // Insert into donor_and_recipient_requests
        $request_sql = "INSERT INTO donor_and_recipient_requests (
            requesting_hospital_id,
            requested_hospital_id,
            donor_id,
            recipient_id,
            blood_type,
            organ_type,
            request_date,
            status,
            notification_status
        ) VALUES (
            :requesting_hospital_id,
            :requested_hospital_id,
            :donor_id,
            :recipient_id,
            (SELECT blood_group FROM donor WHERE donor_id = :donor_id),
            :organ_type,
            NOW(),
            'Pending',
            'Unread'
        )";

        $request_stmt = $conn->prepare($request_sql);
        $request_stmt->execute([
            'requesting_hospital_id' => $data['match_made_by'],
            'requested_hospital_id' => $donor_hospital_id,
            'donor_id' => $data['donor_id'],
            'recipient_id' => $data['recipient_id'],
            'organ_type' => $data['organ_type']
        ]);

        // Insert into made_matches_by_hospitals
        $match_sql = "INSERT INTO made_matches_by_hospitals (
            match_made_by,
            donor_id,
            donor_name,
            donor_blood_group,
            donor_hospital_id,
            donor_hospital_name,
            recipient_id,
            recipient_name,
            recipient_blood_group,
            recipient_hospital_id,
            recipient_hospital_name,
            organ_type,
            match_date
        ) VALUES (
            :match_made_by,
            :donor_id,
            (SELECT name FROM donor WHERE donor_id = :donor_id),
            (SELECT blood_group FROM donor WHERE donor_id = :donor_id),
            :donor_hospital_id,
            (SELECT name FROM hospitals WHERE hospital_id = :donor_hospital_id),
            :recipient_id,
            (SELECT full_name FROM recipient_registration WHERE id = :recipient_id),
            (SELECT blood_type FROM recipient_registration WHERE id = :recipient_id),
            :recipient_hospital_id,
            (SELECT name FROM hospitals WHERE hospital_id = :recipient_hospital_id),
            :organ_type,
            NOW()
        )";

        $match_stmt = $conn->prepare($match_sql);
        $match_stmt->execute([
            'match_made_by' => $data['match_made_by'],
            'donor_id' => $data['donor_id'],
            'donor_hospital_id' => $donor_hospital_id,
            'recipient_id' => $data['recipient_id'],
            'recipient_hospital_id' => $recipient_hospital_id,
            'organ_type' => $data['organ_type']
        ]);

        $match_id = $conn->lastInsertId();

        // Commit transaction
        $conn->commit();
        return $match_id;

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollBack();
        error_log("Error adding organ match: " . $e->getMessage());
        return false;
    }
}

// Update organ match status
function updateOrganMatchStatus($conn, $match_id, $status, $admin_notes = null) {
    try {
        $sql = "UPDATE organ_matches SET status = :status";
        if ($admin_notes !== null) {
            $sql .= ", admin_notes = :admin_notes";
        }
        $sql .= " WHERE match_id = :match_id";

        $stmt = $conn->prepare($sql);
        $params = ['status' => $status, 'match_id' => $match_id];
        if ($admin_notes !== null) {
            $params['admin_notes'] = $admin_notes;
        }
        
        $success = $stmt->execute($params);

        // Create notification for status update
        if ($success) {
            // Get match details for the notification message
            $match = getMatchDetails($conn, $match_id);
            if ($match) {
                $message = "Organ match status updated to {$status} for {$match['donor_name']} (Donor) and {$match['recipient_name']} (Recipient)";
                createNotification(
                    $conn,
                    'organ_match',
                    $status,
                    $match_id,
                    $message
                );
            }
        }

        return $success;
    } catch (PDOException $e) {
        error_log("Error updating organ match status: " . $e->getMessage());
        return false;
    }
}

// Get all organ matches with optional filters
function getOrganMatches($conn, $filters = []) {
    try {
        $sql = "SELECT * FROM organ_matches WHERE 1=1";
        $params = [];

        if (isset($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }

        if (isset($filters['urgency_level'])) {
            $sql .= " AND urgency_level = :urgency_level";
            $params['urgency_level'] = $filters['urgency_level'];
        }

        if (isset($filters['hospital_email'])) {
            $sql .= " AND hospital_email = :hospital_email";
            $params['hospital_email'] = $filters['hospital_email'];
        }

        $sql .= " ORDER BY urgency_level DESC, match_date DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting organ matches: " . $e->getMessage());
        return [];
    }
}

// Get organ match statistics
function getOrganMatchStats($conn) {
    try {
        $stats = [];
        
        // Total matches
        $stmt = $conn->query("SELECT COUNT(*) FROM organ_matches");
        $stats['total_matches'] = $stmt->fetchColumn();

        // Matches by status
        $stmt = $conn->query("SELECT status, COUNT(*) as count FROM organ_matches GROUP BY status");
        $stats['status_counts'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Matches by organ type
        $stmt = $conn->query("SELECT organ_type, COUNT(*) as count FROM organ_matches GROUP BY organ_type");
        $stats['organ_type_counts'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Urgent matches (High priority)
        $stmt = $conn->query("SELECT COUNT(*) FROM organ_matches WHERE urgency_level = 'High'");
        $stats['urgent_matches'] = $stmt->fetchColumn();

        // Successful matches (Confirmed status)
        $stmt = $conn->query("SELECT COUNT(*) FROM organ_matches WHERE status = 'Confirmed'");
        $stats['successful_matches'] = $stmt->fetchColumn();

        return $stats;
    } catch (PDOException $e) {
        error_log("Error getting organ match statistics: " . $e->getMessage());
        return [];
    }
}

// Get specific organ match details
function getOrganMatch($conn, $match_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM organ_matches WHERE match_id = :match_id");
        $stmt->execute(['match_id' => $match_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting organ match details: " . $e->getMessage());
        return null;
    }
}

// Get recent organ matches from made_matches_by_hospitals table (last 7 days)
function getRecentOrganMatches($conn, $limit = 5) {
    try {
        $sql = "SELECT 
            m.*,
            h.name as match_made_by_hospital_name
        FROM made_matches_by_hospitals m
        LEFT JOIN hospitals h ON m.match_made_by = h.hospital_id
        WHERE m.match_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY m.match_date DESC
        LIMIT :limit";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting recent organ matches: " . $e->getMessage());
        return [];
    }
}

// Get all organ matches with pagination and search
function getAllOrganMatches($conn) {
    try {
        $query = "SELECT m.*, 
                  d.blood_group as donor_blood_group,
                  r.blood_type as recipient_blood_group,
                  h1.name as donor_hospital_name,
                  h2.name as recipient_hospital_name,
                  d.name as donor_name,
                  r.full_name as recipient_name
                  FROM made_matches_by_hospitals m
                  LEFT JOIN donor d ON m.donor_id = d.donor_id
                  LEFT JOIN recipient_registration r ON m.recipient_id = r.id
                  LEFT JOIN hospitals h1 ON m.donor_hospital_id = h1.hospital_id
                  LEFT JOIN hospitals h2 ON m.recipient_hospital_id = h2.hospital_id
                  ORDER BY m.match_date DESC";

        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        // Debug: Log the number of rows returned
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Number of matches found: " . count($results));
        
        // Format blood group display
        foreach ($results as &$match) {
            if ($match['donor_blood_group'] === $match['recipient_blood_group']) {
                $match['blood_group_display'] = $match['donor_blood_group'];
            } else {
                $match['blood_group_display'] = 'Not same type';
            }
        }
        
        return $results;
    } catch (PDOException $e) {
        error_log("Error in getAllOrganMatches: " . $e->getMessage());
        return [];
    }
}

// Get specific match details
function getMatchDetails($conn, $match_id) {
    try {
        $sql = "SELECT 
            m.*,
            h.name as match_made_by_hospital_name
        FROM made_matches_by_hospitals m
        LEFT JOIN hospitals h ON m.match_made_by = h.hospital_id
        WHERE m.match_id = :match_id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':match_id', $match_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getMatchDetails: " . $e->getMessage());
        return false;
    }
}

// Function to create notification for new match
function createMatchNotification($conn, $match_id) {
    try {
        // Get match details for notification
        $sql = "SELECT 
            m.*,
            h.name as match_made_by_hospital_name,
            d.name as donor_name,
            r.full_name as recipient_name,
            dh.name as donor_hospital_name,
            rh.name as recipient_hospital_name
        FROM made_matches_by_hospitals m
        LEFT JOIN hospitals h ON m.match_made_by = h.hospital_id
        LEFT JOIN donor d ON m.donor_id = d.donor_id
        LEFT JOIN recipient_registration r ON m.recipient_id = r.id
        LEFT JOIN hospitals dh ON m.donor_hospital_id = dh.hospital_id
        LEFT JOIN hospitals rh ON m.recipient_hospital_id = rh.hospital_id
        WHERE m.match_id = :match_id";

        $stmt = $conn->prepare($sql);
        $stmt->execute(['match_id' => $match_id]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($match) {
            // Create message with all required details
            $message = sprintf(
                "New organ match made by %s: Donor %s (%s) with Recipient %s (%s) for %s",
                $match['match_made_by_hospital_name'],
                $match['donor_name'],
                $match['donor_hospital_name'],
                $match['recipient_name'],
                $match['recipient_hospital_name'],
                $match['organ_type']
            );

            // Insert into notifications table
            $sql = "INSERT INTO notifications (
                type, action, entity_id, message, is_read, created_at, link_url
            ) VALUES (
                'organ_match', 'created', :match_id, :message, 0, NOW(), :link_url
            )";

            $link_url = "organ_match_info_for_admin.php?match_id=" . $match_id;

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'match_id' => $match_id,
                'message' => $message,
                'link_url' => $link_url
            ]);

            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Error creating match notification: " . $e->getMessage());
        return false;
    }
}

// Update match status and create notification
function updateMatchStatus($conn, $match_id, $new_status) {
    try {
        $sql = "UPDATE made_matches_by_hospitals SET status = :status WHERE match_id = :match_id";
        $stmt = $conn->prepare($sql);
        $success = $stmt->execute([
            'status' => $new_status,
            'match_id' => $match_id
        ]);

        if ($success) {
            // Get match details and create notification
            $sql = "SELECT 
                m.*,
                d.name as donor_name,
                r.name as recipient_name
            FROM made_matches_by_hospitals m
            LEFT JOIN donor d ON m.donor_id = d.donor_id
            LEFT JOIN recipient_registration r ON m.recipient_id = r.id
            WHERE m.match_id = :match_id";

            $stmt = $conn->prepare($sql);
            $stmt->execute(['match_id' => $match_id]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($match) {
                $message = sprintf(
                    "Match status updated to %s for Donor %s and Recipient %s",
                    $new_status,
                    $match['donor_name'],
                    $match['recipient_name']
                );

                createNotification(
                    $conn,
                    'organ_match',
                    $new_status,
                    $match_id,
                    $message
                );
            }
        }

        return $success;
    } catch (PDOException $e) {
        error_log("Error updating match status: " . $e->getMessage());
        return false;
    }
}

// Handle POST request for creating new match
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Add match
    $match_id = addOrganMatch($conn, $data);
    
    // Return response
    header('Content-Type: application/json');
    if ($match_id) {
        echo json_encode(['match_id' => $match_id]);
    } else {
        echo json_encode(['error' => 'Failed to create match']);
    }
    exit();
}
?>
