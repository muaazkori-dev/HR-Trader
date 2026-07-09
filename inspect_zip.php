<?php
header('Content-Type: text/plain; charset=utf-8');

$zip_files = ['website.zip', 'website.zip.zip', 'project.zip.zip'];

foreach ($zip_files as $zip_file) {
    $path = __DIR__ . '/' . $zip_file;
    if (file_exists($path)) {
        echo "Found backup file: $zip_file (" . filesize($path) . " bytes)\n";
        
        $zip = new ZipArchive();
        if ($zip->open($path) === TRUE) {
            echo "Successfully opened $zip_file. Total files: " . $zip->numFiles . "\n";
            $count = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (strpos($stat['name'], 'prod_') !== false) {
                    echo "-> " . $stat['name'] . " (" . $stat['size'] . " bytes)\n";
                    $count++;
                    if ($count > 10) {
                        echo "... and more product files\n";
                        break;
                    }
                }
            }
            if ($count == 0) {
                echo "No files containing 'prod_' found inside this zip.\n";
            }
            $zip->close();
        } else {
            echo "Failed to open $zip_file.\n";
        }
    } else {
        echo "Zip file not found: $zip_file\n";
    }
    echo "\n----------------------------------------\n";
}
