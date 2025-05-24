<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MainController extends AbstractController
{
    /**
     * @Route("", name="home")
     */
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
        /*return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/MainController.php',
        ]);*/
    }

    /**
     * @Route("/user", name="user-home")
     */
    public function indexuser(Request $request, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        return $this->json([
            'is_granted' => $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY'),
            'is_granted1' => $authorizationChecker->isGranted('IS_AUTHENTICATED'),
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/MainController.php',
        ]);
    }


    /**
     * @Route("/custom/{name?}", name="custom")
     */
    public function custom(Request $request): JsonResponse
    {
        $name = $request->get('name') ?? 'no name';
        return $this->json([
            'message' => "Welcome to your new controller, $name!",
            'path' => 'src/Controller/MainController.php',
        ]);
    }
}
