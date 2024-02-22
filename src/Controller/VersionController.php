<?php namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class VersionController extends AbstractController
{
    #[Route('/version', methods: ['GET'])]
    public function version(): JsonResponse {
        return new JsonResponse(['version' => '1.0.0']);
    }

}
