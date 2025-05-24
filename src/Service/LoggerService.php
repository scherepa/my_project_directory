<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class LoggerService
{
    private $defaultLogger;
    private $accessLogger;
    private $webSocketLogger;
    private $requestId;
    private $username;

    public function __construct(
        LoggerInterface $defaultLogger,
        LoggerInterface $accessLogger,
        LoggerInterface $webSocketLogger,
        Security $security,
        RequestStack $requestStack
    ) {
        $this->defaultLogger = $defaultLogger;
        $this->accessLogger = $accessLogger;
        $this->webSocketLogger = $webSocketLogger;

        $this->requestId = uniqid('req_', true);

        $user = $security->getUser();
        $this->username = $user ? $user->getUserIdentifier() : 'guest';

        $request = $requestStack->getCurrentRequest();
        if ($request) {
            $this->requestId = $request->headers->get('X-Request-ID', $this->requestId);
        }
    }

    public function logTo(string $channel, string $message, array $context = [], string $level = 'info'): void
    {
        $context = array_merge([
            'timestamp' => date('c'),
            'request_id' => $this->requestId,
            'user' => $this->username,
        ], $context);

        switch ($channel) {
            case 'access_log':
                $this->accessLogger->{$level}($message, $context);
                break;
            case 'websocket':
                $this->webSocketLogger->{$level}($message, $context);
                break;
            default:
                $this->defaultLogger->{$level}($message, $context);
        }
    }

    public function logToAccess(string $message, array $context = [], string $level = 'info'): void
    {
        $this->logTo('access_log', $message, $context, $level);
    }

    public function logToWebSocket(string $message, array $context = [], string $level = 'info'): void
    {
        $this->logTo('websocket', $message, $context, $level);
    }

    public function logToDefault(string $message, array $context = [], string $level = 'debug'): void
    {
        $this->logTo('default', $message, $context, $level);
    }
}
