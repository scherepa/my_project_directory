<?php

namespace App\Form;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AssignAgentType extends AbstractType
{

    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['data'];
        $builder
            ->add(
                'manager',
                EntityType::class,
                [
                    'class' => User::class,
                    'choice_label' => function (User $user) {
                        return sprintf('(%d) %s', $user->getId(), $user->getUserIdentifier());
                    },
                    'placeholder' => 'Choose an agent',
                    'required' => false,
                    'query_builder' => function () use ($user) {
                        return $this->userRepository->queryOnlyAgents($user);
                    },
                    'label' => false,
                    'empty_data' => null,
                    'invalid_message' => 'Not valid Agent',
                ]
            )
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($user) {
                $data = $event->getData();
                $form = $event->getForm();
                $manager = $data->getManager();
                /*dd(
                    $event,
                    $data,
                    $manager,
                    $data->getManager() !== null,
                    $manager->getRole(),
                    $manager->getId(),
                    $user->getChildren(),
                    $data->getChildren(),
                    $data->getChildren()->contains($data->getManager())
                );*/
                if ($data->getManager() !== null && $data->getManager()->getRole() !== User::ROLE_REP) {
                    $form->get('manager')->addError(new FormError("Invalid manager role."));
                }
                if ($data->getManager() !== null && $data->getManager()->getId() === $data->getId()) {
                    $form->get('manager')->addError(new FormError("Invalid manager."));
                }
                if ($data->getManager() !== null &&  $data->getManager()->getHierarchialManegers()->contains($data)) {
                    $form->get('manager')->addError(new FormError("Invalid manager. Subordinate cannot be assigned as a manager for the hierarchial managers."));
                }
                if ($data->getManager() !== null && $data->getChildren()->contains($data->getManager())) {
                    $form->get('manager')->addError(new FormError("Invalid manager. Subordinate cannot be assigned as a manager."));
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
