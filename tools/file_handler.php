<?php
require_once __DIR__ . '/../backend/config/db_connect.php';

class FileHandler {
    private $base_upload_dir;
    private $conn;

    public function __construct($conn) {
        $this->base_upload_dir = __DIR__ . '/../uploads/';
        $this->conn = $conn;
    }

    /**
     * Handle hospital license file upload
     */
    public function handleHospitalFile($hospital_id, $file) {
        $target_dir = $this->base_upload_dir . 'hospitals/license_file/';
        return $this->moveUploadedFile($file, $target_dir);
    }

    /**
     * Handle donor file uploads
     */
    public function handleDonorFiles($donor_id, $files) {
        $result = [];
        
        if (isset($files['medical_reports'])) {
            $target_dir = $this->base_upload_dir . 'donors/medical_reports_path/';
            $result['medical_reports_path'] = $this->moveUploadedFile($files['medical_reports'], $target_dir);
        }
        
        if (isset($files['id_proof'])) {
            $target_dir = $this->base_upload_dir . 'donors/id_proof_path/';
            $result['id_proof_path'] = $this->moveUploadedFile($files['id_proof'], $target_dir);
        }
        
        if (isset($files['guardian_id_proof'])) {
            $target_dir = $this->base_upload_dir . 'donors/guardian_id_proof_path/';
            $result['guardian_id_proof_path'] = $this->moveUploadedFile($files['guardian_id_proof'], $target_dir);
        }
        
        return $result;
    }

    /**
     * Handle recipient file uploads
     */
    public function handleRecipientFiles($recipient_id, $files) {
        $result = [];
        
        if (isset($files['medical_reports'])) {
            $target_dir = $this->base_upload_dir . 'recipient_registration/recipient_medical_reports/';
            $result['recipient_medical_reports'] = $this->moveUploadedFile($files['medical_reports'], $target_dir);
        }
        
        if (isset($files['id_document'])) {
            $target_dir = $this->base_upload_dir . 'recipient_registration/id_document/';
            $result['id_document'] = $this->moveUploadedFile($files['id_document'], $target_dir);
        }
        
        return $result;
    }

    /**
     * Move uploaded file to target directory
     */
    private function moveUploadedFile($file, $target_dir) {
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $target_path = $target_dir . $filename;

        // Move file and set permissions
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            chmod($target_path, 0666);
            return $filename;
        }
        
        return false;
    }
}

// Example usage in registration scripts:
/*
$fileHandler = new FileHandler($conn);

// For hospital registration
if (isset($_FILES['license_file'])) {
    $filename = $fileHandler->handleHospitalFile($hospital_id, $_FILES['license_file']);
    // Update database with $filename
}

// For donor registration
if (!empty($_FILES)) {
    $files = $fileHandler->handleDonorFiles($donor_id, $_FILES);
    // Update database with $files array values
}

// For recipient registration
if (!empty($_FILES)) {
    $files = $fileHandler->handleRecipientFiles($recipient_id, $_FILES);
    // Update database with $files array values
}
*/
?>
