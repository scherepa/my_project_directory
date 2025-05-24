<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Log;
use App\Repository\UserRepository;
use App\Service\AuditLogger;
use DateTime;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class LoginSubscriber implements EventSubscriberInterface
{
    private $doctrine;
    private $auditlogger;
    private $urlGenerator;
    private $rep;
    private $flashBag;

    //public $logRep;
    //public $registry;


    public function __construct(
        EntityManagerInterface $doctrine,
        AuditLogger $auditlogger,
        UrlGeneratorInterface $urlGenerator,
        UserRepository $rep,
        FlashBagInterface $flashBag
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->doctrine = $doctrine;
        $this->auditlogger = $auditlogger;
        $this->rep = $rep;
        $this->flashBag = $flashBag;
    }

    public static function getSubscribedEvents(): array
    {
        return [LoginSuccessEvent::class => 'onLogin'];
    }

    public function onLogin(LoginSuccessEvent $event): void
    {

        // get the current request
        $request = $event->getRequest();
        $user = $event->getUser();
        $userId = $this->rep->getId($user);
        // Ensure manager is null
        $this->rep->setAgent($user, null);
        $this->auditlogger->log('successfull-login', $userId);
        $this->rep->setLog($user, true);
        if ($request->getSession()) {
            $this->flashBag->add('success', 'Wellcome, ' . $user->getUserIdentifier());
        }
    }
}
