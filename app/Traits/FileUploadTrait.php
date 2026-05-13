<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

trait FileUploadTrait
{
    /**
     * Upload a file to AWS S3.
     * 
     * @param UploadedFile $file
     * @param string $folder
     * @return string|false
     */
    public function uploadFile(UploadedFile $file, $folder)
    {
        // Remove 'public/' or 'public' prefix from the folder path if present
        $folder = preg_replace('/^public\/?/', '', $folder);
        
        // Generate a unique filename
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        
        // Store on S3 (disk 's3' should be configured in config/filesystems.php)
        $path = $file->storeAs($folder, $filename, 's3');
        //  $path = $file->storeAs($folder, $filename, [
        //     'disk' => 's3',
        //     'visibility' => 'public'
        // ]);
        
        
        return $path;
    }

    /**
     * Delete a file from AWS S3.
     * 
     * @param string|null $path
     * @return void
     */
    public function deleteFile($path)
    {
        if ($path && Storage::disk('s3')->exists($path)) {
            Storage::disk('s3')->delete($path);
        }
    }
}
