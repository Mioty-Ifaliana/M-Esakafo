<?php

namespace App\tests\application;

use App\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TaskControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testIndex(): void
    {
        // Visitez la page d'index des tâches
        $this->client->request('GET', '/tasks/paginate');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('title', 'Les taches'); // Vérifiez le titre ou autre élément
    }

    // public function testCreateTask(): void
    // {
    //     $this->client->request('GET', '/tasks/create');
    //     $this->assertResponseIsSuccessful();

    //     // Soumettez le formulaire de création de tâche
    //     $this->client->submitForm('Créer', [
    //         'task[title]' => 'Nouvelle Tâche',
    //         'task[slug]' => 'nouvelle-tache',
    //         'task[description]' => 'Description de la nouvelle tâche',
    //         'task[estimates]' => 10,
    //     ]);

    //     $this->assertResponseRedirects('/tasks'); // Vérifiez la redirection après la création
    //     $this->client->followRedirect();

    //     // Vérifiez si la tâche a bien été créée
    //     $this->assertSelectorTextContains('.task-title', 'Nouvelle Tâche'); // Modifiez le sélecteur selon votre HTML
    // }

    // public function testEditTask(): void
    // {
    //     // Créez d'abord une tâche pour pouvoir la modifier
    //     $task = new Task();
    //     $task->setTitle('Tâche à Modifier');
    //     $task->setSlug('tache-a-modifier');
    //     // ... initialisez les autres propriétés

    //     $entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');
    //     $entityManager->persist($task);
    //     $entityManager->flush();

    //     $this->client->request('GET', '/tasks/'.$task->getId().'/edit');
    //     $this->assertResponseIsSuccessful();

    //     // Soumettez le formulaire d'édition
    //     $this->client->submitForm('Enregistrer', [
    //         'task[title]' => 'Tâche Modifiée',
    //         // ... autres champs
    //     ]);

    //     $this->assertResponseRedirects('/tasks');
    //     $this->client->followRedirect();

    //     // Vérifiez que la tâche a été modifiée
    //     $this->assertSelectorTextContains('.task-title', 'Tâche Modifiée'); // Modifiez selon votre HTML
    // }

    // public function testShowTask(): void
    // {
    //     // Créez une tâche pour pouvoir la visualiser
    //     $task = new Task();
    //     $task->setTitle('Tâche à Voir');
    //     $task->setSlug('tache-a-voir');
    //     // ... initialisez les autres propriétés

    //     $entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');
    //     $entityManager->persist($task);
    //     $entityManager->flush();

    //     $this->client->request('GET', '/tasks/'.$task->getSlug().'-'.$task->getId());
    //     $this->assertResponseIsSuccessful();
    //     $this->assertSelectorTextContains('h1', 'Tâche à Voir'); // Vérifiez le titre ou autre élément
    // }

    // public function testDeleteTask(): void
    // {
    //     // Créez une tâche pour pouvoir la supprimer
    //     $task = new Task();
    //     $task->setTitle('Tâche à Supprimer');
    //     $task->setSlug('tache-a-supprimer');
    //     // ... initialisez les autres propriétés

    //     $entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');
    //     $entityManager->persist($task);
    //     $entityManager->flush();

    //     // Soumettez une requête de suppression
    //     $this->client->request('POST', '/task/'.$task->getId().'/delete');
    //     $this->assertResponseRedirects('/tasks');
    //     $this->client->followRedirect();

    //     // Vérifiez que la tâche n'existe plus
    //     $this->assertSelectorNotExists('.task-title:contains("Tâche à Supprimer")'); // Modifiez le sélecteur selon votre HTML
    // }
}
