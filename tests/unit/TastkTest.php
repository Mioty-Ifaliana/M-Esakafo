<?php

declare(strict_types=1);

use App\Entity\Project;
use App\Entity\Task;
use PHPUnit\Framework\TestCase;

final class TaskTest extends TestCase
{
    public function testSetTitle(): void
    {
        $task = new Task();
        $task->setTitle('Title of the task');

        $this->assertSame('Title of the task', $task->getTitle());
    }

    public function testSetProject(): void
    {
        $task = new Task();
        $project = new Project();
        $task->setProject($project);

        $this->assertSame($project, $task->getProject());
    }
}
