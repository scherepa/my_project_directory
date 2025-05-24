<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;
use Psr\Log\LoggerInterface;

class AccessLogSubscriber implements EventSubscriberInterface
{
    private $logger;
    private $enabled;
    private $security;

    public function __construct(LoggerInterface $accessLogger, bool $enabled, Security $security)
    {
        $this->logger = $accessLogger;
        $this->enabled = $enabled;
        $this->security = $security;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->enabled || $event->isMainRequest() === false) {
            return;
        }

        $request = $event->getRequest();
        $session = $request->getSession();
        $last_activity = $session ? $session->get('last_activity', 'N/A') : 'anonymous';
        $user = $this->security->getUser();
        $username = $user ? $user->getUserIdentifier() : 'none';
        $this->logger->info(json_encode([
            'type' => 'REQUEST',
            'method' => $request->getMethod(),
            'uri' => $request->getUri(),
            'ip' => $request->getClientIp(),
            'last_activity' => $last_activity,
            'user' => $username,
            'session' => $session ? true : false
        ]));
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->enabled || $event->isMainRequest() === false) {
            return;
        }
        $request = $event->getRequest();
        $response = $event->getResponse();

        $this->logger->info(json_encode([
            'type' => 'RESPONSE',
            'status' => $response->getStatusCode(),
            'uri' => $request->getUri(),
            'ip' => $request->getClientIp(),
        ]));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}
