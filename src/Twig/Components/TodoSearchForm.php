<?php declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('TodoSearchForm')]
class TodoSearchForm
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $search = '';
}