<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadService
{
    private string $uploadDir;

    public function __construct(string $uploadDir)
    {
        $this->uploadDir = $uploadDir;
    }

    public function upload(UploadedFile $file): string
    {
        $filename = uniqid().'.'.$file->guessExtension();
        $file->move($this->uploadDir, $filename);
        return $filename;
    }

    public function delete(string $filename): void
    {
        $filePath = $this->uploadDir.'/'.$filename;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}