<?php

namespace App\Security;

use App\Service\AuditLogger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;



class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private $urlGenerator;
    private $auditlogger;

    public function __construct(UrlGeneratorInterface $urlGenerator, AuditLogger $auditlogger)
    {
        $this->urlGenerator = $urlGenerator;
        $this->auditlogger = $auditlogger;
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('username', '');

        $request->getSession()->set(Security::LAST_USERNAME, $username);

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {

            return new RedirectResponse($targetPath);
        }
        $user = $token->getUser();
        // Regenerate session
        $request->getSession()->migrate(true);
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('admin_home')
            );
        }
        if (in_array('ROLE_REP', $user->getRoles(), true)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('rep_home')
            );
        }
        return new RedirectResponse(
            $this->urlGenerator->generate('user_home')
        );
        throw new \Exception('TODO: provide a valid redirect inside ' . __FILE__);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
