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
            ->add('contactMessage', TextareaType::class, ['label' => 'Message de la section contact', 'required' => false, 'attr' => ['rows' => 3, 'class' => 'markdown-editor', 'data-md' => 'block']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Portfolio::class]);
    }
}
