<?php

namespace App\Form;

use App\Entity\Competence;
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

final class CompetenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, ['empty_data' => '', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => 'Compétence', 'attr' => ['placeholder' => 'ex. Symfony']])
            ->add('code', TextType::class, ['empty_data' => '', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => 'Identifiant court (unique)', 'attr' => ['placeholder' => 'ex. symfony']])
            ->add('bloc', TextType::class, ['empty_data' => '', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => 'Catégorie', 'attr' => ['placeholder' => 'Front, Back, DevOps, Outils…']])
            ->add('level', ChoiceType::class, ['label' => 'Niveau', 'choices' => ['1 — Notions' => 1, '2 — Débutant' => 2, '3 — Intermédiaire' => 3, '4 — Avancé' => 4, '5 — Expert' => 5]])
            ->add('icon', TextType::class, ['label' => 'Icône (optionnel)', 'required' => false, 'attr' => ['placeholder' => 'bi-filetype-php'], 'help' => "Nom d'une icône Bootstrap Icons (icons.getbootstrap.com)."])
            ->add('description', TextareaType::class, ['label' => 'Description', 'required' => false, 'attr' => ['rows' => 3]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Competence::class]);
    }
}
