<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);
        $errors = [];

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // encode the plain password
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
                // Ensure manager is null
                $user->setManager(null);
                $userName = $form->get('username')->getData();
                if (str_contains($userName, 'admin@')) {
                    $roles = ['ROLE_ADMIN'];
                } elseif (str_contains($userName, 'rep@')) {
                    $roles = ['ROLE_REP'];
                }else {
                    $roles = [];
                }
                $user->setRoles($roles);
                $entityManager->persist($user);
                $entityManager->flush();
                // do anything else you need here, like send an email
                $this->addFlash('success', 'Registration successful! Please log in.');
                return $this->redirectToRoute('app_login');
            } else {

                foreach ($form as $fieldName => $formField) {
                    foreach ($formField->getErrors(true) as $error) {
                        $errors[$fieldName] = $error->getMessage();
                    }
                }
                $this->addFlash('warning', 'Registration failed!');
            }
        }

        return $this->render('registration/register.html.twig', [
            'errors' => $errors,
            'registrationForm' => $form->createView(),
        ]);
    }
}
