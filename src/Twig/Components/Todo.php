<?php declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Todos as TodoEntity;
use App\Repository\TodosRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('Todo')]
class Todo
{
    use DefaultActionTrait;

    public function __construct(
        private readonly TodosRepository $todosRepository,
        private readonly EntityManagerInterface $em
    ) {}

    // ==================== Propriétés Live ====================
    #[LiveProp(writable: true)]
    public string $newTodo = '';

    #[LiveProp(writable: true)]
    public string $search = '';

    #[LiveProp(writable: true)]
    public ?int $editingId = null;

    #[LiveProp(writable: true)]
    public string $editingTitle = '';

    // Données
    public array $todos = [];
    public int $totalTodos = 0;
    public int $remaining = 0;
    public int $completedCount = 0;

    // ==================== Initialisation & Chargement ====================
    public function mount(): void
    {
        $this->loadTodos();
    }

    #[PostHydrate]
    public function loadTodos(): void
    {
        $query = $this->todosRepository->createQueryBuilder('t');

        if ($this->search !== '') {
            $query->andWhere('t.title LIKE :search')
                  ->setParameter('search', '%' . $this->search . '%');
        }

        $this->todos = $query->orderBy('t.createdAt', 'DESC')
                             ->getQuery()
                             ->getResult();

        $this->totalTodos = count($this->todos);
        $this->remaining = count(array_filter($this->todos, fn($t) => !$t->isDone()));
        $this->completedCount = $this->totalTodos - $this->remaining;
    }

    // ==================== Actions ====================
    #[LiveAction]
    public function addTodo(): void
    {
        $title = trim($this->newTodo);

        if (strlen($title) < 5) {
            return; 
        }

        $todo = new TodoEntity();
        $todo->setTitle($title)
             ->setDone(false)
             ->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($todo);
        $this->em->flush();

        // Réinitialisation du champ
        $this->newTodo = '';

        // IMPORTANT : on recharge les données pour que LiveComponent mette à jour l'interface
        $this->loadTodos();
    }

    #[LiveAction]
    public function refresh(): void
    {
        // Cette méthode ne fait rien d’autre que recharger les données
        $this->loadTodos();
    }

    #[LiveAction]
    public function clearSearch(): void
    {
        $this->search = '';
        $this->loadTodos();   
    }

    #[LiveAction]
    public function toggleTodo(#[LiveArg] int $id): void
    {
        $todo = $this->todosRepository->find($id);
        if ($todo) {
            $todo->setDone(!$todo->isDone())
                 ->setUpdatedAt(new \DateTimeImmutable());
            $this->em->flush();
        }
        $this->loadTodos();       
    }

    #[LiveAction]
    public function removeTodo(#[LiveArg] int $id): void
    {
        $todo = $this->todosRepository->find($id);
        if ($todo) {
            $this->em->remove($todo);
            $this->em->flush();
        }
        $this->loadTodos();
    }

    #[LiveAction]
    public function startEdit(#[LiveArg] int $id): void
    {
        $todo = $this->todosRepository->find($id);
        if ($todo) {
            $this->editingId = $todo->getId();
            $this->editingTitle = $todo->getTitle();
        }
    }

    #[LiveAction]
    public function saveEdit(): void
    {
        if (!$this->editingId) return;

        $todo = $this->todosRepository->find($this->editingId);
        if ($todo) {
            $title = trim($this->editingTitle);
            if (strlen($title) >= 2) {
                $todo->setTitle($title)
                     ->setUpdatedAt(new \DateTimeImmutable());
                $this->em->flush();
            }
        }
        $this->cancelEdit();
        $this->loadTodos();
    }

    #[LiveAction]
    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editingTitle = '';
    }
}