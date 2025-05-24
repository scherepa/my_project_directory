<?php

namespace App\Controller;

use App\DTO\AssignAgentDTO;
use App\DTO\UserTableDTO;
use App\Entity\User;
use App\Form\AssignAgentType;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Validator\AssignAgentValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/admin", name="admin_")
 * 
 * 
 */
class AdminController extends AbstractController
{
    /**
     * @Route("", name="home")
     */
    public function index(Request $request, UserRepository $userRep, SessionInterface $session): Response
    {
        //$sessionData = $session->all();
        //$users = $userRep->onlyAgents();
        //dump($users, $userRep->queryOnlyAgents($this->getUser())->getQuery());
        // For debugging, you can dump the session data
        //dump($sessionData);
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'User tried to access a page without having ROLE_ADMIN');
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }


    /**
     * @Route("/users", name="users")
     */
    public function listUsers(Request $request, UserRepository $userRep): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'User tried to get data without having permission');
        $rep = $this->getUser();
        $start = (int) $request->query->get('start', 0);
        $length = (int) $request->query->get('length', 10);
        $draw = (int) $request->query->get('draw', 1);
        $page = (int) floor($start / $length) + 1;
        $qb = $userRep->getAdminUsersForDToWithManagerQuery()
            ->select([
                'u.id as id',
                'u.username as username',
                'u.manager_id as manager_id',
                'm.username as display_name',
                'u.login_time as login_time'
            ]);

        $paginated = $userRep->queryPaginated($qb, $length, $page);
        $rows = $userRep->getResulted($paginated);
        $total = $userRep->querySelectCount($userRep->getAdminUsersForDToQuery(), 'u.id');

        // map rows to DTOs
        $dtos = array_map(function ($row) {
            return (new UserTableDTO($row))->toArray();
        }, $rows);
        //dump($dtos, $total);

        return $this->json([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $dtos
        ]);
    }


    /**
     * @Route("/users-try", name="users-try")
     */
    public function listtry(Request $request, UserRepository $userRep): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'User tried to get data without having permission');
        $users = $userRep->onlyUsers();
        $arrayCollection = [];
        foreach ($users as $item) {
            $dt = $item->getLoginTime();
            $id = $item->getId();
            $form = $this->createForm(
                AssignAgentType::class,
                $item,
                ['action' => $this->generateUrl('admin_assign_agent', ['id' => $id])]
            );
            $form->handleRequest($request);
            $errors = [];
            $html = $this->render('admin/assign_agent.html.twig', [
                'errors' => $errors,
                'assignAgentForm' => $form->createView(),
            ])->getContent();
            $arrayCollection[] = [
                "DT_RowId" => "row_" . $id,
                "DT_RowData" => [
                    "pkey" => $id
                ],
                'id' => $item->getId(),
                'username' => ucfirst($item->getUserIdentifier()),
                'manager' => $item->getManagerId(),
                'login_time' => $dt !== null ? $dt->format('Y-m-d H:i:s') : null,
                'html' => $html
            ];
        }
        $total = count($arrayCollection);
        return $this->json(
            [
                "draw" => 1,
                "recordsTotal" => $total,
                "recordsFiltered" => $total,
                'data' => $arrayCollection
            ]
        );
    }

    /**
     * @Route("/agents", name="agents")
     */
    public function listAgents(Request $request, UserRepository $userRep): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'User tried to get data without having permission');
        $users = $userRep->onlyAgents();

        $arrayCollection = [];
        foreach ($users as $item) {
            $dt = $item->getLoginTime();
            $id = $item->getId();
            $arrayCollection[] = [
                "DT_RowId" => "row_" . $id,
                "DT_RowData" => [
                    "pkey" => $id
                ],
                'id' => $id,
                'username' => ucfirst($item->getUserIdentifier()),
                'manager_Id' => $item->getManagerId(),
                'login_time' => $dt !== null ? $dt->format('Y-m-d H:i:s') : null,
                'role' => 'REP',
            ];
        }
        $result = [];


        foreach ($arrayCollection as $key => $item) {
            $managers = array_filter($users, function (User $manager) use ($item) {
                return $manager->getId() !== $item['id']; // Exclude the current user
            });
            $form = $this->createForm(AssignAgentType::class, $users[$key], ['action' => $this->generateUrl('admin_assign_agent', ['id' => ($users[$key])->getId()])]);
            $form->handleRequest($request);
            $errors = [];
            $html = $this->render('admin/assign_agent.html.twig', [
                'errors' => $errors,
                'assignAgentForm' => $form->createView(),
            ])->getContent();
            $item['html'] = $html;
            $result[] = $item;
        }
        $total = count($result);
        return $this->json(
            [
                "draw" => 1,
                "recordsTotal" => $total,
                "recordsFiltered" => $total,
                'data' => $result
            ]
        );
    }

    /**
     * @Route("/users/{id}/available-managers", name="users_available_managers", methods={"GET"})
     * 
     */
    public function availableManagers(int $id, UserRepository $userRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $rep = $this->getUser();
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }
        $managers = $userRepository->getRepAssignableManagers($rep, $user);
        //dump($managers);
        $options = array_values(array_map(function (User $mgr) {
            return [
                'id' => $mgr->getId(),
                'label' => sprintf('(%d) %s', $mgr->getId(), $mgr->getUserIdentifier()),
            ];
        }, $managers));

        return $this->json($options);
    }


    /**
     * @Route("/users/{id}/assign-manager", name="assign_manager", methods={"POST"})
     */
    public function assignManager(
        int $id,
        Request $request,
        ValidatorInterface $validator,
        AssignAgentValidator $assignAgentValidator,
        EntityManagerInterface $em,
        UserRepository $userRep
    ) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['errors' => 'No access for you!'], 403);
            //throw $this->createAccessDeniedException('No access for you!');
        }
        $data = json_decode($request->getContent(), true);

        $dto = new AssignAgentDTO();
        $dto->userId = (int) $id;
        $dto->managerId = isset($data['managerId']) ? (int) $data['managerId'] : null;

        $violations = $validator->validate($dto);
        $customViolations = $assignAgentValidator->validate($dto, 'ROLE_ADMIN');

        $allViolations = array_merge(iterator_to_array($violations), iterator_to_array($customViolations));

        if (count($allViolations) > 0) {
            $errors = [];
            foreach ($allViolations as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }
            return new JsonResponse(['errors' => $errors], 400);
        }

        $user = $userRep->find($dto->userId);
        $manager = $dto->managerId ? $userRep->find($dto->managerId) : null;
        $user->setManager($manager);
        $em->persist($user);
        $em->flush();

        return new JsonResponse(['status' => 'ok']);
    }

    /**
     * @Route("/asign_agent/{id}", name="assign_agent")
     */
    public function assignAgent(Request $request, UserRepository $userRep, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'User tried to modify data without having permission');
        $id = $request->get('id');
        $userA = $userRep->find($request->get('id'));
        $form = $this->createForm(
            AssignAgentType::class,
            $userA,
            [
                'action' => $this->generateUrl('admin_assign_agent', ['id' => $id])
            ]
        );
        $form->handleRequest($request);
        $errors = [];
        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $em->persist($form->getData());
                $em->flush();
                $this->addFlash('success', 'New Agent Assigned to User.');
            } else {
                foreach ($form as $fieldName => $formField) {
                    foreach ($formField->getErrors(true) as $error) {
                        $errors[$fieldName] = $error->getMessage();
                    }
                }
                $this->addFlash('warning', 'New Agent Assign failed!');
                return $this->render('admin/index.html.twig', [
                    'controller_name' => 'AdminController',
                    'errors' => $errors
                ]);
            }
        }
        return $this->redirectToRoute('admin_home');
    }
}
