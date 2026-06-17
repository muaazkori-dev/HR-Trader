<?php
header('Content-Type: text/plain; charset=utf-8');

$dir = __DIR__ . '/assets/images/categories/';
if (!is_dir($dir)) {
    die("Categories directory not found: $dir");
}

if (!extension_loaded('gd')) {
    die("GD library is not enabled on this PHP installation.");
}

echo "=== Category Image Optimizer (Signature Aware) ===\n";

$files = glob($dir . '*.{png,jpg,jpeg}', GLOB_BRACE);

foreach ($files as $file) {
    $filename = basename($file);
    $orig_size = filesize($file);
    echo "Processing $filename (" . number_format($orig_size / 1024, 1) . " KB)... ";
    
    // Detect actual image type
    $img_info = @getimagesize($file);
    if (!$img_info) {
        echo "Failed to detect image type.\n";
        continue;
    }
    
    $mime = $img_info['mime'];
    $im = null;
    
    if ($mime === 'image/png') {
        $im = @imagecreatefrompng($file);
    } elseif ($mime === 'image/jpeg') {
        $im = @imagecreatefromjpeg($file);
    } elseif ($mime === 'image/webp') {
        $im = @imagecreatefromwebp($file);
    } elseif ($mime === 'image/gif') {
        $im = @imagecreatefromgif($file);
    }
    
    if (!$im) {
        echo "Failed to load image with MIME type: $mime.\n";
        continue;
    }
    
    $width = imagesx($im);
    $height = imagesy($im);
    
    // Resize to 120x120 max
    $max_dim = 120;
    if ($width > $max_dim || $height > $max_dim) {
        if ($width > $height) {
            $new_width = $max_dim;
            $new_height = floor($height * ($max_dim / $width));
        } else {
            $new_height = $max_dim;
            $new_width = floor($width * ($max_dim / $height));
        }
        
        $tmp = imagecreatetruecolor($new_width, $new_height);
        
        // Setup transparency for PNG saving
        imagealphablending($tmp, false);
        imagesavealpha($tmp, true);
        $transparent = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
        imagefill($tmp, 0, 0, $transparent);
        
        imagecopyresampled($tmp, $im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagedestroy($im);
        $im = $tmp;
    }
    
    // Save back based on file extension to keep path unchanged but convert content to actual extension type
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $success = false;
    
    if ($ext === 'png') {
        // Force PNG format
        imagealphablending($im, false);
        imagesavealpha($im, true);
        $success = @imagepng($im, $file, 9); // Max compression
    } elseif ($ext === 'webp') {
        $success = @imagewebp($im, $file, 75);
    } else {
        $success = @imagejpeg($im, $file, 75);
    }
    
    imagedestroy($im);
    
    if ($success) {
        clearstatcache();
        $new_size = filesize($file);
        $saved = $orig_size - $new_size;
        $pct = ($orig_size > 0) ? ($saved / $orig_size) * 100 : 0;
        echo "SUCCESS! New size: " . number_format($new_size / 1024, 1) . " KB (Saved " . number_format($pct, 1) . "%)\n";
    } else {
        echo "FAILED to save image.\n";
    }
}

echo "Image optimization complete!\n";
?>
