<?php

namespace App\Form;

use App\Entity\Project;
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

final class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['empty_data' => '', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => 'Titre'])
            ->add('type', TextType::class, ['empty_data' => '', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => 'Type', 'attr' => ['placeholder' => 'Application Web, Mobile, Script…']])
            ->add('year', TextType::class, ['empty_data' => '', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => 'Année'])
            ->add('company', EntityType::class, ['label' => 'Entreprise (optionnel)', 'class' => \App\Entity\Company::class, 'required' => false, 'placeholder' => '— Aucune —'])
            ->add('formation', EntityType::class, ['label' => 'École / formation (optionnel)', 'class' => \App\Entity\Formation::class, 'required' => false, 'placeholder' => '— Aucune —', 'help' => "Pour un projet réalisé en cours : l'école apparaîtra sur la carte et la page du projet."])
            ->add('description', TextareaType::class, ['empty_data' => '', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => 'Description courte', 'attr' => ['rows' => 3, 'class' => 'markdown-editor', 'data-md' => 'inline']])
            ->add('longDescription', TextareaType::class, ['label' => 'Description détaillée', 'required' => false, 'attr' => ['rows' => 8, 'class' => 'markdown-editor'], 'help' => "Titres, listes, gras, liens, code… Utilise la barre d'outils ; l'œil affiche l'aperçu."])
            ->add('tagsText', TextType::class, ['label' => 'Technologies (séparées par des virgules)', 'required' => false, 'attr' => ['placeholder' => 'PHP, Symfony, MySQL']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Project::class]);
    }
}
