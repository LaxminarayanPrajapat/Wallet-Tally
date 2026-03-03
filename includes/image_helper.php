<?php
/**
 * Image Helper Functions
 * Provides image processing utilities with GD extension support
 */

class ImageHelper {
    
    /**
     * Check if GD extension is available
     * @return bool
     */
    public static function isGDAvailable() {
        return extension_loaded('gd');
    }
    
    /**
     * Get GD extension info
     * @return array|null
     */
    public static function getGDInfo() {
        if (!self::isGDAvailable()) {
            return null;
        }
        
        return gd_info();
    }
    
    /**
     * Validate image file
     * @param string $file_path Path to image file
     * @return array|false Image info array or false on failure
     */
    public static function validateImage($file_path) {
        // Basic file existence check
        if (!file_exists($file_path)) {
            return false;
        }
        
        // If GD is available, use getimagesize for proper validation
        if (self::isGDAvailable()) {
            return getimagesize($file_path);
        }
        
        // Fallback: basic MIME type check
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($mime_type, $allowed_types)) {
            // Return basic info without GD
            return [
                'mime' => $mime_type,
                'gd_available' => false
            ];
        }
        
        return false;
    }
    
    /**
     * Get supported image formats
     * @return array
     */
    public static function getSupportedFormats() {
        $formats = ['jpeg', 'png', 'gif'];
        
        if (self::isGDAvailable()) {
            $gd_info = gd_info();
            $supported = [];
            
            if ($gd_info['JPEG Support']) $supported[] = 'jpeg';
            if ($gd_info['PNG Support']) $supported[] = 'png';
            if ($gd_info['GIF Read Support'] && $gd_info['GIF Create Support']) $supported[] = 'gif';
            
            return $supported;
        }
        
        return $formats; // Return all as potentially supported
    }
    
    /**
     * Resize image (requires GD extension)
     * @param string $source_file Source image path
     * @param int $max_width Maximum width
     * @param int $max_height Maximum height
     * @param string $output_file Output file path (optional)
     * @return string|false Output file path or false on failure
     */
    public static function resizeImage($source_file, $max_width, $max_height, $output_file = null) {
        if (!self::isGDAvailable()) {
            return false;
        }
        
        $image_info = getimagesize($source_file);
        if ($image_info === false) {
            return false;
        }
        
        $width = $image_info[0];
        $height = $image_info[1];
        $type = $image_info[2];
        
        // Calculate new dimensions
        $ratio = min($max_width / $width, $max_height / $height);
        if ($ratio >= 1) {
            return $source_file; // Image is already smaller than target size
        }
        
        $new_width = round($width * $ratio);
        $new_height = round($height * $ratio);
        
        // Create source image
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source_image = imagecreatefromjpeg($source_file);
                break;
            case IMAGETYPE_PNG:
                $source_image = imagecreatefrompng($source_file);
                break;
            case IMAGETYPE_GIF:
                $source_image = imagecreatefromgif($source_file);
                break;
            default:
                return false;
        }
        
        if (!$source_image) {
            return false;
        }
        
        // Create new image
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve transparency for PNG and GIF
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
        }
        
        // Resize image
        imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        
        // Determine output file
        if ($output_file === null) {
            $output_file = $source_file . '_resized';
        }
        
        // Save resized image
        $success = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($new_image, $output_file, 85);
                break;
            case IMAGETYPE_PNG:
                $success = imagepng($new_image, $output_file, 8);
                break;
            case IMAGETYPE_GIF:
                $success = imagegif($new_image, $output_file);
                break;
        }
        
        // Clean up memory
        imagedestroy($source_image);
        imagedestroy($new_image);
        
        return $success ? $output_file : false;
    }
    
    /**
     * Create thumbnail (requires GD extension)
     * @param string $source_file Source image path
     * @param int $size Thumbnail size (square)
     * @param string $output_file Output file path (optional)
     * @return string|false Output file path or false on failure
     */
    public static function createThumbnail($source_file, $size = 150, $output_file = null) {
        return self::resizeImage($source_file, $size, $size, $output_file);
    }
    
    /**
     * Get system status for images
     * @return array
     */
    public static function getSystemStatus() {
        $status = [
            'gd_available' => self::isGDAvailable(),
            'fileinfo_available' => extension_loaded('fileinfo'),
            'supported_formats' => self::getSupportedFormats()
        ];
        
        if ($status['gd_available']) {
            $status['gd_info'] = self::getGDInfo();
        }
        
        return $status;
    }
}
?>