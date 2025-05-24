<?php

namespace App\Controller;

use App\DTO\AssignAgentDTO;
use App\DTO\UserTableDTO;
use App\Entity\User;
use App\Form\AssignAgentRepType;
use App\Repository\UserRepository;
use App\Validator\AssignAgentValidator;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Node\Expr\Clone_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/rep", name="rep_")
 * 
 */
class RepController extends AbstractController
{
    /**
     * @Route("", name="home")
     */
    public function index(UserRepository $userRep, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_REP', null, 'User tried to get data without having permission');
        return $this->render('rep/index.html.twig', [
            'controller_name' => 'RepController',
        ]);
    }

    /**
     * @Route("/users", name="users")
     */
    public function listUsers(Request $request, UserRepository $userRep): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_REP', null, 'User tried to get data without having permission');
        $rep = $this->getUser();
        $start = (int) $request->query->get('start', 0);
        $length = (int) $request->query->get('length', 10);
        $draw = (int) $request->query->get('draw', 1);
        $rows = $userRep->getUsersUnderRepFlat($rep, $start, $length);
        $total = $userRep->countUsersUnderRep($rep);
        $dtos = array_map(function ($row) {
            return (new UserTableDTO($row))->toArray();
        }, $rows);

        return $this->json([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $dtos
        ]);
    }

    /**
     * @Route("/users/{id}/available-managers", name="users_available_managers", methods={"GET"})
     * 
     */
    public function availableManagers(int $id, UserRepository $userRepository): JsonResponse
    {
        if (!$this->isGranted('ROLE_REP')) {
            return new JsonResponse(['errors' => 'No access for you!'], 403);
            //throw $this->createAccessDeniedException('No access for you!');
        }
        $this->denyAccessUnlessGranted('ROLE_REP');
        $rep = $this->getUser();
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }
        $managers = $userRepository->getRepDescendantEntities($rep);
        $options = array_values(array_map(function (User $mgr) {
            return [
                'id' => $mgr->getId(),
                'label' => sprintf('(%d) %s', $mgr->getId(), $mgr->getUserIdentifier()),
            ];
        }, $managers));

        return $this->json($options);
    }

    /**
     * @Route("/agents", name="agents")
     */
    public function listRepAgents(UserRepository $userRep, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_REP', null, 'User tried to get data without having permission');
        // there is definnetly a better way to do it... but recursive queries and dbal quering with self referenced entity...
        // all of this is new for me
        // as I see it rep can manage users managers and trades, admin manage reps, users and trades, user can manage trades
        // but maybe I've got it wrong...
        // so for reps agents table will not have select manager opion...
        // $user = $this->getUser(); // Authenticated rep
        //$total = count($userRep->getRepDescendants($user));

        //$start = (int) $request->query->get('start', 0);
        //$length = (int) $request->query->get('length', 10);
        //$draw = (int) $request->query->get('draw', 1);
        //$page = (int) floor($start / $length) + 1;
        /*$qqb = $userRep->getRepDescendantEntitiesForDTO($user)->select([
            'u.id as id',
            'u.username as username',
            'u.manager_id as manager_id',
            'm.username as display_name',
            'u.login_time as login_time',
            'u.role as role'
        ]);
        $paginated = $userRep->queryPaginated($qqb, $length, $page);
        $rows = $userRep->getResulted($paginated);
        // map rows to DTOs
        $dtos = array_map(function ($row) {
            return (new UserTableDTO($row))->toArray();
        }, $rows);*/
        $rep = $this->getUser();
        $start = (int) $request->query->get('start', 0);
        $length = (int) $request->query->get('length', 10);
        $draw = (int) $request->query->get('draw', 1);
        $rows = $userRep->getAgentsUnderRepFlat($rep, $start, $length);
        $total = $userRep->countRepAgents($rep);
        $dtos = array_map(function ($row) {
            return (new UserTableDTO($row))->toArray();
        }, $rows);
        return $this->json([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $dtos
        ]);
    }

    /**
     * @Route("/users/{id}/assign-manager", name="assign_agents", methods={"POST"})
     */
    public function assignManager(
        int $id,
        Request $request,
        ValidatorInterface $validator,
        AssignAgentValidator $assignAgentValidator,
        EntityManagerInterface $em,
        UserRepository $userRep
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_REP', null, 'User tried to update data without having permission');
        if ($request->getContentType() !== 'json') {
            return new JsonResponse(['errors' => 'Expected JSON content'], 400);
        }
        $request->getSession()->set('last_activity', time());
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['errors' => 'Invalid JSON body.'], 400);
        }
        $dto = new AssignAgentDTO();
        $dto->userId = (int) $id;
        $dto->managerId = isset($data['managerId']) ? (int) $data['managerId'] : null;
        $violations = $validator->validate($dto);
        $customViolations = $assignAgentValidator->validate($dto, 'ROLE_REP');
        $allViolations = array_merge(iterator_to_array($violations), iterator_to_array($customViolations));
        if (count($allViolations) > 0) {
            $errors = [];
            foreach ($allViolations as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }
            return new JsonResponse(['errors' => $errors], 422);
        }
        $user = $userRep->find($dto->userId);
        $manager = $dto->managerId ? $userRep->find($dto->managerId) : null;
        $user->setManager($manager);
        $em->persist($user);
        $em->flush();

        return new JsonResponse(['status' => 'ok']);
    }
}
