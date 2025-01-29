<?php

namespace App\Form;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder
        ->add('email')
        // ->add('roles', ChoiceType::class, [ 'choices' => [ 'Admin' => 'ROLE_ADMIN', 'User' => 'ROLE_USER', ], 
        // 'multiple' => false, 
        // 'expanded' => true, ])
        ->add('password')
        ->add('username')
        ->add('apiToken')
        ->add('tasks', EntityType::class, [
            'class' => Task::class,
            'choice_label' => 'title',
            'multiple' => true, // Allows multiple selection
            'expanded' => true, // Use checkboxes
        ])
        ->add('save', SubmitType::class, [
            'label' => 'Enregistrer'
        ])
    ;
}


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
