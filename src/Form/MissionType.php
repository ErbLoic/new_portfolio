<?php

namespace App\Form;

use App\Entity\Mission;
use App\Entity\Stage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class MissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $md = ['rows' => 4, 'class' => 'markdown-editor', 'data-md' => 'block'];
        $builder
            ->add('stage', EntityType::class, [
                'label' => 'Expérience',
                'class' => Stage::class,
                'placeholder' => '— Choisir —',
                'choice_label' => fn (Stage $s) => $s->role.($s->company ? ' — '.$s->company->name : '').($s->isCurrent ? ' (en cours)' : ''),
                'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')],
                'help' => "⚠ Décris la mission de façon anonymisée : pas de nom de client, d'outil interne, d'adresse IP / serveur ni de donnée confidentielle de l'entreprise.",
            ])
            ->add('title', TextType::class, ['empty_data' => '', 'label' => 'Titre de la mission', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'attr' => ['placeholder' => 'ex. Automatisation du suivi du parc informatique']])
            ->add('context', TextareaType::class, ['label' => 'Contexte / besoin', 'required' => false, 'attr' => $md])
            ->add('actions', TextareaType::class, ['label' => "Ce que j'ai fait", 'required' => false, 'attr' => $md])
            ->add('results', TextareaType::class, ['label' => 'Résultats', 'required' => false, 'attr' => $md, 'help' => "Idéalement chiffrés : temps gagné, nombre d'utilisateurs…"])
            ->add('tags', TextType::class, ['label' => 'Technos / outils', 'required' => false, 'attr' => ['placeholder' => 'PowerShell, Symfony, Power BI'], 'help' => 'Séparés par des virgules.'])
            ->add('order', IntegerType::class, ['label' => "Ordre d'affichage", 'empty_data' => '0'])
            ->add('isPublished', CheckboxType::class, ['label' => 'Visible sur le site', 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Mission::class]);
    }
}
