<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TodoController extends AbstractController
{
    #[Route('/todo', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('todo/index.html.twig');
    }
}
