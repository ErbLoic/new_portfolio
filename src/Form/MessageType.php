<?php

namespace App\Form;

use App\Entity\ImportantMessage;
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

final class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre', 'required' => false, 'attr' => ['placeholder' => 'ex. Recherche de stage']])
            ->add('message', TextareaType::class, ['empty_data' => '', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => 'Message', 'attr' => ['rows' => 3, 'class' => 'markdown-editor', 'data-md' => 'inline']])
            ->add('type', ChoiceType::class, ['empty_data' => '', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => 'Style', 'choices' => \App\Entity\ImportantMessage::TYPES])
            ->add('icon', TextType::class, ['label' => 'Icône Bootstrap (optionnel)', 'required' => false, 'attr' => ['placeholder' => 'ex. briefcase']])
            ->add('linkUrl', UrlType::class, ['label' => 'Lien (optionnel)', 'required' => false, 'default_protocol' => 'https'])
            ->add('linkText', TextType::class, ['label' => 'Texte du lien', 'required' => false])
            ->add('startDate', DateTimeType::class, ['label' => 'Afficher à partir du', 'help' => 'Laisser vide pour afficher tout de suite.', 'widget' => 'single_text', 'required' => false])
            ->add('endDate', DateTimeType::class, ['label' => "Afficher jusqu'au", 'help' => 'Laisser vide pour afficher sans limite de date.', 'widget' => 'single_text', 'required' => false])
            ->add('order', IntegerType::class, ['empty_data' => '0', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'label' => "Ordre d'affichage"])
            ->add('isActive', CheckboxType::class, ['label' => 'Actif', 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ImportantMessage::class]);
    }
}
