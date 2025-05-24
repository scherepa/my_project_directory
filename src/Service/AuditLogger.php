<?php

namespace App\Service;

use App\Entity\Log;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;


class AuditLogger
{
    private $em;
    private $security;
    private $requestStack;

    public function __construct(EntityManagerInterface $entityManager, Security $security, RequestStack $requestStack)
    {
        $this->em = $entityManager;
        $this->security = $security;
        $this->requestStack = $requestStack;
    }

    public function log(string $action,?int $entityId = null): void
    {
        $log = new Log();
        $log->setActionName($action);
        $log->setDateCreated(new DateTimeImmutable('now'));
        if ($entityId) {
            $log->setUserId($entityId);
        }
        $this->em->persist($log);
        $this->em->flush();
    }
}
