<?php

namespace App\Form;

use App\Entity\Certification;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class CertificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['empty_data' => '', 'label' => 'Certification', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'attr' => ['placeholder' => 'ex. AWS Cloud Practitioner']])
            ->add('issuer', TextType::class, ['empty_data' => '', 'label' => 'Organisme', 'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')], 'attr' => ['placeholder' => 'ex. Amazon Web Services']])
            ->add('issuedAt', DateType::class, ['label' => "Date d'obtention", 'required' => false, 'widget' => 'single_text'])
            ->add('expiresAt', DateType::class, ['label' => "Date d'expiration (si applicable)", 'required' => false, 'widget' => 'single_text'])
            ->add('credentialUrl', UrlType::class, ['label' => 'Lien de vérification', 'required' => false, 'default_protocol' => 'https'])
            ->add('order', IntegerType::class, ['label' => "Ordre d'affichage", 'empty_data' => '0'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Certification::class]);
    }
}
