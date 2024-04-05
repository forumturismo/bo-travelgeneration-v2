<?php

namespace App\Form;

use App\Entity\PagamentoOffline;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PagamentoOfflineType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
                ->add('data')
                ->add('numFile')
                ->add('cliente')
                ->add('comercial')
                ->add('servico')
                ->add('precoVenda')
                ->add('precoCusto')
                ->add('comissao')
                ->add('valorPago')
//                ->add('valorPendente')
                ->add('metodoPagamento')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => PagamentoOffline::class,
            'attr' => ['class' => 'form-control']
        ]);
    }
}
