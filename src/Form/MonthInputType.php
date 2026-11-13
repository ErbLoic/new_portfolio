<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/** Sélecteur de mois natif (<input type="month">), valeur « AAAA-MM ». */
final class MonthInputType extends AbstractType
{
    public function getParent(): string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'Mois et année',
            'help' => "Sert à classer du plus récent au plus ancien et s'affiche « Juin 2025 ».",
            'attr' => ['placeholder' => 'AAAA-MM', 'pattern' => '\d{4}-(0[1-9]|1[0-2])'],
            'constraints' => [new Assert\Regex(pattern: '/^\d{4}-(0[1-9]|1[0-2])$/', message: 'Choisis un mois valide (AAAA-MM).')],
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['type'] = 'month';
    }
}
