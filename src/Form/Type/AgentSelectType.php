<?php

// src/Form/Type/AgentSelectType.php

namespace App\Form\Type;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AgentSelectType extends AbstractType
{
    /** @var UserRepository */
    private $userRepository;

    /** @var Security */
    private $security;

    public function __construct(UserRepository $userRepository, Security $security)
    {
        $this->userRepository = $userRepository;
        $this->security = $security;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => User::class,
            'required' => false,
            'placeholder' => 'Choose an agent',
            'label' => false,
            'invalid_message' => 'Invalid agent selection.',
            'context_entity' => null, // the row (user/trade etc)
            'mode' => 'default',      // one of: admin_user, admin_trade, rep_user, rep_trade, etc.
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $mode = isset($options['mode']) ? $options['mode'] : 'default';
        $contextEntity = isset($options['context_entity']) ? $options['context_entity'] : null;

        /** @var User|null $currentUser */
        $currentUser = $this->security->getUser();

        $queryBuilder = null;

        // Classic switch instead of match (for PHP 7.3 compatibility)
        switch ($mode) {
            case 'admin_user':
            case 'admin_trade':
                $queryBuilder = $this->userRepository->agents();
                break;

            case 'admin_rep_table':
                $queryBuilder = $this->userRepository
                    ->queryOnlyAgents($contextEntity)
                    ->andWhere('user.id != :target')
                    ->setParameter('target', $contextEntity ? $contextEntity->getId() : 0);
                break;

            case 'rep_user':
                $assignable = $this->userRepository->getRepAssignableManagers($currentUser, $contextEntity);
                $ids = array_map(function (User $u) {
                    return $u->getId();
                }, $assignable);

                $queryBuilder = $this->userRepository->createQueryBuilder('user')
                    ->where('user.id IN (:ids)')
                    ->setParameter('ids', $ids);
                break;

            case 'rep_trade':
                $queryBuilder = $this->userRepository
                    ->queryAvailableManagersForRep($currentUser)
                    ->orWhere('user.id = :self')
                    ->setParameter('self', $currentUser->getId());
                break;

            case 'user_trade':
                // No agents available for regular user trade creation
                $queryBuilder = $this->userRepository
                    ->createQueryBuilder('user')
                    ->where('1 = 0');
                break;

            default:
                $queryBuilder = $this->userRepository->agents();
        }

        // Inject the query builder into the form field options
        $builder->add('manager', EntityType::class, [
            'class' => User::class,
            'query_builder' => $queryBuilder,
            'choice_label' => function (User $user) {
                return sprintf('(%d) %s', $user->getId(), $user->getUserIdentifier());
            },
            'placeholder' => 'Choose an agent',
            'required' => in_array($mode, ['admin_trade', 'rep_trade'], true),
            'label' => false,
            'empty_data' => null,
            'invalid_message' => 'Invalid agent selected.',
        ]);
    }

    public function getParent(): ?string
    {
        return EntityType::class;
    }
}
