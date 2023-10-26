<?php

namespace App\Form;

use App\Entity\PagamentoOffline;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PagamentoOfflineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numFile')
            ->add('cliente')
            ->add('destino')
            ->add('comercial')
            ->add('precoVenda')
            ->add('valorPago')
            ->add('valorPendente')
            ->add('metodoPagamento')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PagamentoOffline::class,
            'attr' => ['class' => 'form-control']
        ]);
    }
}
