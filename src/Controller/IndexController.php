<?php namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    #[Route('/', methods: ['GET'])]
    public function index(): Response {
        return new Response($this->staticIndex());
    }

    private function staticIndex() {
        return <<<'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
  <title>retreat-api</title>
</head>
<body>
  <h1>retreat-api</h1>
  <p>This is the API endpoint for Retreat</p>
</body>
EOF;
    }
}
