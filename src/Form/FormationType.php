<?php

namespace App\Form;

use App\Entity\Formation;
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

final class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['empty_data' => '', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => 'Intitulé', 'attr' => ['placeholder' => 'ex. BTS SIO SLAM']])
            ->add('degreeType', TextType::class, ['label' => 'Type de diplôme', 'required' => false, 'attr' => ['placeholder' => 'BTS, Licence, BAC…']])
            ->add('school', TextType::class, ['empty_data' => '', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => 'Établissement'])
            ->add('location', TextType::class, ['label' => 'Lieu', 'required' => false])
            ->add('startDate', DateType::class, ['constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => 'Début', 'widget' => 'single_text'])
            ->add('endDate', DateType::class, ['label' => 'Fin', 'widget' => 'single_text', 'required' => false])
            ->add('isCurrent', CheckboxType::class, ['label' => 'Formation en cours', 'required' => false])
            ->add('description', TextareaType::class, ['label' => 'Description', 'required' => false, 'attr' => ['rows' => 4, 'class' => 'markdown-editor', 'data-md' => 'block']])
            ->add('order', IntegerType::class, ['empty_data' => '0', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => "Ordre d'affichage"])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Formation::class]);
    }
}
