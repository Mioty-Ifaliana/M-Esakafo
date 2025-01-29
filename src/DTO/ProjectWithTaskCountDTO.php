<?php

namespace App\DTO;

class ProjectWithTaskCountDTO
{
  public function __construct(
    public readonly int $id,
    public readonly string $title,
    public readonly int $taskCount,
  )
  {
    
  }
}