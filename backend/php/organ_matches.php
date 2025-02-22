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
        $sql = "INSERT INTO made_matches_by_hospitals (
            donor_id, recipient_id, match_made_by,
            donor_hospital_id, recipient_hospital_id,
            organ_type, match_date, status
        ) VALUES (
            :donor_id, :recipient_id, :match_made_by,
            :donor_hospital_id, :recipient_hospital_id,
            :organ_type, NOW(), 'Pending'
        )";

        $stmt = $conn->prepare($sql);
        $stmt->execute($data);
        $match_id = $conn->lastInsertId();

        if ($match_id) {
            // Create notification for the new match
            createMatchNotification($conn, $match_id);
        }

        return $match_id;
    } catch (PDOException $e) {
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
        $query = "SELECT 
            match_id,
            match_made_by,
            donor_id,
            donor_name,
            donor_hospital_id,
            donor_hospital_name,
            recipient_id,
            recipient_name,
            recipient_hospital_id,
            recipient_hospital_name,
            organ_type,
            blood_group,
            match_date
        FROM made_matches_by_hospitals
        ORDER BY match_date DESC";

        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        // Debug: Log the number of rows returned
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Number of matches found: " . count($results));
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
?>
