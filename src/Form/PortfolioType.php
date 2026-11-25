<?php

namespace App\Form;

use App\Entity\Portfolio;
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

final class PortfolioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, ['empty_data' => '', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => 'Prénom'])
            ->add('lastName', TextType::class, ['empty_data' => '', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => 'Nom'])
            ->add('jobTitle', TextType::class, ['label' => 'Titre affiché sous ton nom', 'required' => false, 'attr' => ['placeholder' => 'Chargé de missions SI en alternance'], 'help' => "Visible dans le hero, l'onglet du navigateur et les aperçus LinkedIn / Google."])
            ->add('bio', TextareaType::class, ['label' => 'Présentation', 'required' => false, 'attr' => ['rows' => 5, 'class' => 'markdown-editor', 'data-md' => 'block']])
            ->add('email', EmailType::class, ['label' => 'Email (reçoit aussi les messages du formulaire de contact)', 'required' => false])
            ->add('phone', TextType::class, ['label' => 'Téléphone', 'required' => false])
            ->add('location', TextType::class, ['label' => 'Localisation', 'required' => false])
            ->add('linkedinUrl', UrlType::class, ['label' => 'LinkedIn', 'required' => false, 'default_protocol' => 'https'])
            ->add('githubUrl', UrlType::class, ['label' => 'GitHub', 'required' => false, 'default_protocol' => 'https'])
            ->add('showCv', CheckboxType::class, ['label' => 'Afficher mon CV sur le site', 'required' => false, 'help' => 'Décoché : le bouton et la section « Télécharger mon CV » sont masqués (le fichier reste enregistré).'])
            ->add('timelineMode', ChoiceType::class, ['label' => 'Affichage du parcours', 'expanded' => true, 'required' => true, 'placeholder' => false, 'empty_data' => 'split', 'choices' => [
                'Séparé : formations dans « Profil », expériences dans « Expériences »' => 'split',
                'Fusionné : formations et expériences sur une seule frise' => 'merged',
            ]])
            ->add('timelineStart', TextareaType::class, ['label' => 'Point de départ', 'required' => false, 'attr' => ['rows' => 3, 'class' => 'markdown-editor', 'data-md' => 'block', 'placeholder' => "ex. Tout a commencé au lycée avec la spécialité NSI : mon premier site, codé pour un club."], 'help' => 'Affiché tout en bas de la frise : ce qui a lancé ton parcours.'])
            ->add('timelineNext', TextareaType::class, ['label' => 'Et ensuite ?', 'required' => false, 'attr' => ['rows' => 3, 'class' => 'markdown-editor', 'data-md' => 'block', 'placeholder' => "ex. Valider mon diplôme d'ingénieur et me spécialiser en architecture logicielle."], 'help' => 'Affiché tout en haut de la frise : ton objectif, la prochaine étape.'])
            ->add('contactMessage', TextareaType::class, ['label' => 'Message de la section contact', 'required' => false, 'attr' => ['rows' => 3, 'class' => 'markdown-editor', 'data-md' => 'block']])
            ->add('terminalMotivation', TextType::class, ['label' => 'Texte de « cat motivation.txt »', 'required' => false, 'attr' => ['placeholder' => '██████████ 100% (chargée à bloc)']])
            ->add('terminalCowsay', TextType::class, ['label' => 'Phrase par défaut de « cowsay »', 'required' => false, 'attr' => ['placeholder' => 'Meuh. Bienvenue sur mon portfolio !']])
            ->add('terminalFortunes', TextareaType::class, ['label' => 'Citations de « fortune »', 'required' => false, 'attr' => ['rows' => 5], 'help' => 'Une citation par ligne. Vide : citations par défaut.'])
            ->add('terminalCommands', TextareaType::class, ['label' => 'Commandes personnalisées', 'required' => false, 'attr' => ['rows' => 6, 'placeholder' => "hobbies | Escalade, jeux vidéo et café ☕
objectif | Valider mon diplôme en alternance"], 'help' => 'Une par ligne : « commande | réponse ». Texte brut, sans HTML. Peut remplacer une commande existante (sauf help, clear, exit).'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Portfolio::class]);
    }
}
