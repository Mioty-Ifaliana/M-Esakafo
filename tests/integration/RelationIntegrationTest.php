<?php

namespace App\tests\integration;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RelationIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
    }



    public function testUserProjectTaskCreation(): void
    {
        // Create a new User
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setUsername('testuser');

        // Create a new Project
        $project = new Project();
        $project->setName('Test Project');
        $project->setDescription('This is a test project.');

        // Create Tasks for the project
        $task1 = new Task();
        $task1->setTitle('Test Task 1');
        $task1->setSlug('test-task-1');
        $task1->setDescription('This is the first test task.');
        $task1->setEstimates(10);
        $task1->setCreatedAt(new DateTimeImmutable());
        $task1->setUpdatedAt(new DateTimeImmutable());



        $task2 = new Task();
        $task2->setTitle('Test Task 2');
        $task2->setSlug('test-task-2');
        $task2->setDescription('This is the second test task.');
        $task2->setEstimates(20);
        $task2->setCreatedAt(new DateTimeImmutable());
        $task2->setUpdatedAt(new DateTimeImmutable());


        // Associate tasks with the project
        $project->addTask($task1);
        $project->addTask($task2);

        // Persist everything
        $this->entityManager->persist($user);
        $this->entityManager->persist($project);
        $this->entityManager->persist($task1);
        $this->entityManager->persist($task2);

        // Flush the changes to the database
        $this->entityManager->flush();

        // Now verify the relationships
        $this->assertEquals('Test Project', $project->getName());
        $this->assertCount(2, $project->getTasks());
        $this->assertSame($project, $task1->getProject());
        $this->assertSame($project, $task2->getProject());
    }

    // public function testUniqueEmailConstraint(): void
    // {
    //     // Create a user with an email
    //     $user1 = new User();
    //     $user1->setEmail('unique@example.com');
    //     $user1->setPassword('password');
    //     $user1->setUsername('user1');

    //     // Persist the user
    //     $this->entityManager->persist($user1);
    //     $this->entityManager->flush();

    //     // Try to create another user with the same email
    //     $user2 = new User();
    //     $user2->setEmail('unique@example.com');
    //     $user2->setPassword('password2');
    //     $user2->setUsername('user2');

    //     // Expect an exception or error
    //     $this->expectException(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);

    //     // Persist the second user
    //     $this->entityManager->persist($user2);
    //     $this->entityManager->flush();
    // }


    protected function tearDown(): void
    {
        if ($this->entityManager !== null) {
            $this->entityManager->close();
        }

        parent::tearDown();
    }
}
