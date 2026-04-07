<?php declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('TodoStats')]
class TodoStats
{
    use DefaultActionTrait;

    public int $totalTodos = 0;
    public int $remaining = 0;
    public int $completedCount = 0;
}