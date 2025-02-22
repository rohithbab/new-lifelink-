<?php
// Base upload directory
$baseUploadDir = __DIR__ . '/../../uploads';

// Directories to create
$directories = [
    $baseUploadDir,
    $baseUploadDir . '/donors',
    $baseUploadDir . '/medical_reports',
    $baseUploadDir . '/id_proofs',
    $baseUploadDir . '/guardian_proofs'
];

// Create each directory if it doesn't exist
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
        echo "Created directory: " . $dir . "<br>";
    } else {
        echo "Directory already exists: " . $dir . "<br>";
    }
}

echo "Directory setup complete!";
?>
