<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\Attribute\PreDehydrate;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('Todo')]
class Todo
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public array $todos = [];

    #[LiveProp(writable: true)]
    public string $newTodo = '';

    #[LiveProp(writable: true)]
    public ?int $editingIndex = null;

    #[LiveProp(writable: true)]
    public string $editingTitle = '';

    #[LiveProp(writable: true)]
    public string $editingValue = '';

    //  BONUS UI
    public int $totalTodos = 0;
    
    #[LiveProp(writable: true)]
    public string $search = '';

    // Ajout
    #[LiveAction]
    public function addTodo(): void
    {
        $title = trim($this->newTodo);

        if (strlen($title) < 2) {
            return; 
        }

        $this->todos[] = [
            'title' => $title,
            'done'  => false,
        ];

        $this->newTodo = '';

        // TRÈS IMPORTANT
        $this->editingIndex = null;
        $this->editingTitle = '';
        
    }

    //  Toggle
    #[LiveAction]
    public function toggleTodo(#[LiveArg] int $index): void
    {
        if (isset($this->todos[$index])) {
            $this->todos[$index]['done'] = !$this->todos[$index]['done'];
        }
    }

    // Supprimer
    #[LiveAction]
    public function removeTodo(#[LiveArg] int $index): void
    {
        if (!isset($this->todos[$index])) return;

        array_splice($this->todos, $index, 1);

        // Ajustement édition
        if ($this->editingIndex === $index) {
            $this->cancelEdit();
        } elseif ($this->editingIndex > $index) {
            $this->editingIndex--;
        }
    }

    // Start edit
    #[LiveAction]
    public function startEdit(#[LiveArg] int $index): void
    {
        if (!isset($this->todos[$index])) return;

        $this->editingIndex = $index;
        $this->editingTitle = $this->todos[$index]['title'];
    }

    //  Save edit
    #[LiveAction]
    public function saveEdit(#[LiveArg] int $index): void
    {
        $title = trim($this->editingTitle);

        if (strlen($title) < 2) {
            return;
        }

        if (isset($this->todos[$index])) {
            $this->todos[$index]['title'] = $title;
        }

        $this->cancelEdit();
    }

    //  Cancel
    #[LiveAction]
    public function cancelEdit(): void
    {
        $this->editingIndex = null;
        $this->editingTitle = '';
    }
    
    // Computed pour la recherche
    public function getFilteredTodos(): array
    {
        if ($this->search === '') {
            return $this->todos;
        }

        return array_filter($this->todos, function ($todo) {
            return str_contains(
                strtolower($todo['title']),
                strtolower($this->search)
            );
        });
    }

     // Total
    public function getTotalTodos(): int
    {
        return count($this->todos);
    }

     // =========================
    //  LIFECYCLE HOOKS
    // =========================

    //  1. Après réception des données du frontend
    #[PostHydrate]
    public function sanitize()
    {
        // Nettoyage des inputs
        $this->newTodo = trim($this->newTodo);
        $this->editingValue = trim($this->editingValue);
    }

    // 2. Avant d'envoyer au frontend
    #[PreDehydrate]
    public function prepareData()
    {
        // Réindexer le tableau (important après delete)
        $this->todos = array_values($this->todos);

        // Supprimer les entrées vides
        $this->todos = array_filter($this->todos, fn($t) => $t !== '');
    }

    // 3. Juste avant le rendu Twig
    #[PreReRender]
    public function prepareView()
    {
        // Calcul pour affichage
        $this->totalTodos = count($this->todos);
    }
}