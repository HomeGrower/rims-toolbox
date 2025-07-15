<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageCompressionService
{
    protected ImageManager $manager;
    protected int $maxFileSize = 2 * 1024 * 1024; // 2MB in bytes
    protected int $minWidth = 2000;
    protected int $minHeight = 1500;
    protected int $maxWidth = 2400;
    protected int $maxHeight = 2400;
    
    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }
    
    /**
     * Process an uploaded image file - upscale if too small, compress if needed
     */
    public function processImage(UploadedFile $file, string $path): string
    {
        // Check if we need to process the image
        $originalSize = $file->getSize();
        $needsProcessing = false;
        
        // Load the image to check dimensions
        $image = $this->manager->read($file->getRealPath());
        
        // Get original dimensions
        $width = $image->width();
        $height = $image->height();
        
        // Check if image needs upscaling
        if ($width < $this->minWidth || $height < $this->minHeight) {
            $needsProcessing = true;
            
            // Calculate scale factor to meet minimum dimensions while maintaining aspect ratio
            $scaleFactorWidth = $this->minWidth / $width;
            $scaleFactorHeight = $this->minHeight / $height;
            $scaleFactor = max($scaleFactorWidth, $scaleFactorHeight);
            
            $newWidth = (int) ($width * $scaleFactor);
            $newHeight = (int) ($height * $scaleFactor);
            
            // Upscale the image
            $image->resize($newWidth, $newHeight);
            
            // Update dimensions
            $width = $newWidth;
            $height = $newHeight;
        }
        
        // Check if image needs downscaling
        if ($width > $this->maxWidth || $height > $this->maxHeight) {
            $needsProcessing = true;
            $image->scaleDown($this->maxWidth, $this->maxHeight);
        }
        
        // Check if file size needs compression
        if ($originalSize > $this->maxFileSize) {
            $needsProcessing = true;
        }
        
        // If no processing is needed and image is already optimal, just store it
        if (!$needsProcessing) {
            return $file->store($path, 'public');
        }
        
        // Generate filename
        $filename = Str::random(40) . '.jpg';
        $fullPath = $path . '/' . $filename;
        
        // Start with high quality
        $quality = 90;
        $minQuality = 60;
        $step = 5;
        
        // Progressively reduce quality until file size is under limit
        do {
            $encoded = $image->toJpeg($quality);
            $size = strlen($encoded->toString());
            
            if ($size <= $this->maxFileSize || $quality <= $minQuality) {
                break;
            }
            
            $quality -= $step;
        } while ($quality > $minQuality);
        
        // Save the processed image
        Storage::disk('public')->put($fullPath, $encoded->toString());
        
        return $fullPath;
    }
    
    /**
     * Process multiple images
     */
    public function processMultipleImages(array $files, string $path): array
    {
        $processedPaths = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $processedPaths[] = $this->processImage($file, $path);
            }
        }
        
        return $processedPaths;
    }
}