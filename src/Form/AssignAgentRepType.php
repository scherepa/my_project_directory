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

class AssignAgentRepType extends AbstractType
{

    private $availableManagers;

    public function __construct(array $availableManagers = [])
    {
        $this->availableManagers = $availableManagers;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('manager', ChoiceType::class, [
                'choices' => $this->availableManagers,
                'choice_label' => function (User $user) {
                    return sprintf('(%d) %s', $user->getId(), $user->getUserIdentifier());
                },
                'placeholder' => 'Choose agent',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
