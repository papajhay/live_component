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
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsLiveComponent('Todo')]
class Todo
{
    use DefaultActionTrait;

    private const PER_PAGE = 5;

    public function __construct(
        private readonly TodosRepository $todosRepository,
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack
    ) {}

    private int $limit = 5;

    // ==================== Propriétés Live ====================
    #[LiveProp(writable: true)]
    public string $newTodo = '';

    #[LiveProp(writable: true)]
    public string $search = '';

    #[LiveProp(writable: true)]
    public ?int $editingId = null;

    #[LiveProp(writable: true)]
    public string $editingTitle = '';

    #[LiveProp(writable: true)]
    public int $page = 1;

    // Données
    public array $todos = [];
    public int $totalTodos = 0;
    public int $remaining = 0;
    public int $completedCount = 0;
    public int $totalPages = 1;

    // ==================== Initialisation & Chargement ====================
    public function mount(): void
    {
        $this->loadTodos();
    }

    #[PostHydrate]
    public function loadTodos(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $this->page = $request ? max(1, (int) $request->query->get('page', 1)) : 1;
        $this->search = $request ? (string) $request->query->get('search', '') : '';

        $qb = $this->todosRepository->createQueryBuilder('t');

        if ($this->search !== '') {
            $qb->andWhere('t.title LIKE :search')
               ->setParameter('search', '%' . $this->search . '%');
        }

        // Count total
        $countQb = clone $qb;
        $this->totalTodos = (int) $countQb->select('COUNT(t.id)')->getQuery()->getSingleScalarResult();

        // Compter remaining et completed sur TOUTE la table, pas sur la page
        $remainingQb = clone $qb;
        $this->remaining = (int) $remainingQb
            ->select('COUNT(t.id)')
            ->andWhere('t.done = :done')
            ->setParameter('done', false)
            ->getQuery()
            ->getSingleScalarResult();

        $this->completedCount = $this->totalTodos - $this->remaining;

        $this->totalPages = (int) ceil($this->totalTodos / self::PER_PAGE) ?: 1;

        // Sécurité page
        if ($this->page < 1) $this->page = 1;
        if ($this->page > $this->totalPages) $this->page = $this->totalPages;

        // Requête paginée
        $this->todos = $qb
            ->orderBy('t.createdAt', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setFirstResult(self::PER_PAGE * ($this->page - 1))
            ->setMaxResults(self::PER_PAGE)
            ->getQuery()
            ->getResult();
    }

    // ==================== SEARCH EVENT ====================
    #[LiveListener('searchUpdated')]
    public function onSearchUpdated(string $search): void
    {
        $this->search = $search;
        $this->page = 1;
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

        $this->page = 1;
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
        $this->page = 1;
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