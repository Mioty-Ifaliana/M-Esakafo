<?php

declare(strict_types=1);

use App\Entity\Project;
use App\Entity\Task;
use PHPUnit\Framework\TestCase;

final class ProjectTest extends TestCase
{
    public function testAddTask(): void
    {
        $project = new Project();
        $task = new Task();
        $project->addTask($task);

        $this->assertCount(1, $project->getTasks()); // count task project
        $this->assertSame($project, $task->getProject()); // check project
    }

    public function testRemoveTask(): void
    {
        $project = new Project();
        $task = new Task();
        $project->addTask($task);
        $project->removeTask($task);

        $this->assertCount(0, $project->getTasks());
        $this->assertNull($task->getProject());
    }
}
