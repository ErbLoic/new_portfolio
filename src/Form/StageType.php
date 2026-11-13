<?php

namespace App\Form;

use App\Entity\Stage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class StageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, ['empty_data' => 'stage', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => "Type d'expérience", 'choices' => \App\Entity\Stage::TYPES])
            ->add('company', EntityType::class, ['constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => 'Entreprise', 'class' => \App\Entity\Company::class, 'placeholder' => '— Choisir —'])
            ->add('role', TextType::class, ['empty_data' => '', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => 'Poste / rôle'])
            ->add('startDate', DateType::class, ['constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => 'Début', 'widget' => 'single_text'])
            ->add('endDate', DateType::class, ['required' => false, 'label' => 'Fin (vide si en cours)', 'widget' => 'single_text'])
            ->add('isCurrent', CheckboxType::class, ['label' => 'Expérience en cours', 'required' => false])
            ->add('duration', TextType::class, ['empty_data' => '', 'required' => false, 'label' => 'Durée affichée (optionnel)', 'attr' => ['placeholder' => 'ex. 6 semaines']])
            ->add('description', TextareaType::class, ['empty_data' => '', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => 'Description', 'attr' => ['rows' => 4, 'class' => 'markdown-editor', 'data-md' => 'block']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Stage::class]);
    }
}
