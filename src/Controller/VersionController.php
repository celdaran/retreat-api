<?php namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class Version extends JsonResponse
{
    #[Route('/version', methods: ['GET'])]
    public function version(): JsonResponse {
        return new JsonResponse(['version' => '1.0.0']);
    }

}
