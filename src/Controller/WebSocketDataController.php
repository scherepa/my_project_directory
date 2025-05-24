<?php

// src/Controller/WebSocketDataController.php

namespace App\Controller;

use App\Repository\AssetRepository;
use App\Service\LoggerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class WebSocketDataController
{

    private $bearerToken;
    private $allowedIps;
    private $logger;
    private $assetRepository;

    public function __construct(
        string $bearerToken,
        string $allowedIps,
        LoggerService $logger,
        AssetRepository $assetRepository
    ) {
        $this->bearerToken = $bearerToken;
        $this->allowedIps = explode(',', $allowedIps);
        $this->logger = $logger;
        $this->assetRepository = $assetRepository;
    }

    /**
     * @Route("/api/data", name="api_data", methods={"POST"})
     */
    public function receive(Request $request): JsonResponse
    {
        //dump('hit /api/data'); die;
        $ip = $request->getClientIp();
        $authHeader = $request->headers->get('Authorization');

        // Check IP whitelist
        if (!in_array($ip, $this->allowedIps)) {
            return new JsonResponse(['error' => 'IP not allowed', 'allowed' => $this->allowedIps, 'ip' => $ip], 403);
        }

        // Check Bearer token
        if (!$authHeader || !preg_match('/Bearer (.+)/', $authHeader, $matches)) {
            $this->logger->logTo('websocket', 'Missing Bearer or Bad Format', [
                'header' => $authHeader
            ]);
            return new JsonResponse(['error' => 'Missing Bearer token'], 401);
        }

        if ($matches[1] !== $this->bearerToken) {
            $this->logger->logTo('websocket', 'Invalid Bearer Token', [
                'received' => $matches[1],
                'expected' => $this->bearerToken
            ]);
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        // Success: process data
        $data = json_decode($request->getContent(), true);
        $payload = $data['payload'] ?? 'N/A';

        $this->logger->logTo('websocket', 'Received payload', [
            'ip' => $ip,
            'payload' => $payload,
        ], 'info');

        $parsedPayload = json_decode($payload, true);

        if (!isset($parsedPayload['s']) || !str_ends_with($parsedPayload['s'], 'USDT')) {
            return new JsonResponse(['message' => 'Not a USDT asset, skipped'], 200);
        }

        $asset = $this->assetRepository->updateOrCreateFromPayload($parsedPayload);
        $this->logger->logToWebSocket('Saved or updated Asset is:', [
            'symbol' => $asset->getSymbol(),
            'bid' => $asset->getBid(),
            'ask' => $asset->getAsk(),
        ], 'info');


        return new JsonResponse(['status' => 'ok']);
    }
}
