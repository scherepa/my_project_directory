<?php

namespace App\Validator;

use App\DTO\AssignAgentDTO;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolation;

class AssignAgentValidator
{
    private $userRepository;
    private $security;


    public function __construct(UserRepository $userRepository, Security $security)
    {
        $this->userRepository = $userRepository;
        $this->security = $security;
    }

    public function validate(AssignAgentDTO $dto, ?string $role): ConstraintViolationList
    {
        $violations = new ConstraintViolationList();
        if (empty($role) || !in_array($role, ['ROLE_REP', 'ROLE_ADMIN'], true)) {
            $violations->add(new ConstraintViolation(
                'Not Allowed.',
                null,
                [],
                null,
                'userId',
                $dto->userId
            ));
            return $violations;
        }
        $user = $this->userRepository->find($dto->userId);
        if (!$user) {
            $violations->add(new ConstraintViolation(
                'User not found.',
                null,
                [],
                null,
                'userId',
                $dto->userId
            ));
            return $violations;
        }
        $manager = $dto->managerId ? $this->userRepository->find($dto->managerId) : null;
        $rep = $this->security->getUser();
        if ($manager) {
            if ($user->getId() === $manager->getId()) {
                $violations->add(new ConstraintViolation(
                    'User cannot assign themselves as manager.',
                    null,
                    [],
                    null,
                    'managerId',
                    $dto->managerId
                ));
            }

            if ($manager->getRole() !== 'ROLE_REP') {
                $violations->add(new ConstraintViolation(
                    'Manager must have REP role.',
                    null,
                    [],
                    null,
                    'managerId',
                    $dto->managerId
                ));
            }

            if ($manager && $manager->getHierarchialManegers()->contains($user)) {
                $violations->add(new ConstraintViolation(
                    'User cannot assign their superior as manager.',
                    null,
                    [],
                    null,
                    'managerId',
                    $dto->managerId
                ));
            }

            if ($user->getChildren()->contains($manager)) {
                $violations->add(new ConstraintViolation(
                    'Subordinate cannot be assigned as manager.',
                    null,
                    [],
                    null,
                    'managerId',
                    $dto->managerId
                ));
            }
        }

        // Role-based validation
        if ($role === 'ROLE_REP') {
            if ($manager === null) {
                $violations->add(new ConstraintViolation(
                    'Manager not found or You are not allowed to assign this manager.',
                    null,
                    [],
                    null,
                    'managerId',
                    $dto->managerId
                ));
                return $violations;
            }
            // Rep cannot assign managers outside their subtree
            $assignables = $this->userRepository->getRepAssignableManagers($rep, $user);
            if (!in_array($manager, $assignables, true)) {
                $violations->add(new ConstraintViolation(
                    'You are not allowed to assign this manager.',
                    null,
                    [],
                    null,
                    'managerId',
                    $dto->managerId
                ));
                return $violations;
            }
            $currentManager = $user->getManagerId();
            $descendants = $this->userRepository->getRepDescendants($rep);
            $repIds = array_column($descendants, 'id');
            $repIds[] = (string) $this->userRepository->getId($rep);
            if (empty($currentManager) || !in_array($currentManager, $repIds, true)) {
                $violations->add(new ConstraintViolation(
                    'You are not in charge of User. Only admin permitted to change it',
                    null,
                    [],
                    null,
                    'userId',
                    $dto->userId
                ));
            }
        }
        return $violations;
    }
}
