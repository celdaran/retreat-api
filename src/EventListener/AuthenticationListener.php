<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationListener implements EventSubscriberInterface
{
    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        // Check if the request has the authentication header
        if ($request->headers->has('Authorization')) {
            $auth = $request->headers->get('Authorization');
            if ($auth !== $_ENV['AUTH_TOKEN']) {
                $response = new Response('Unauthorized', 401);
                $event->setResponse($response);
            }
        } else {
            $response = new Response('Unauthorized', 401);
            $event->setResponse($response);
        }
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
