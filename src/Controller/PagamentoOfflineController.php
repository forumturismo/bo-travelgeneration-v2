<?php

namespace App\Controller;

use App\Entity\PagamentoOffline;
use App\Form\PagamentoOfflineType;
use App\Repository\PagamentoOfflineRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/pagamento/offline")
 */
class PagamentoOfflineController extends AbstractController
{
    /**
     * @Route("/", name="app_pagamento_offline_index", methods={"GET"})
     */
    public function index(PagamentoOfflineRepository $pagamentoOfflineRepository): Response
    {
        return $this->render('pagamento_offline/index.html.twig', [
            'pagamento_offlines' => $pagamentoOfflineRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="app_pagamento_offline_new", methods={"GET", "POST"})
     */
    public function new(Request $request, PagamentoOfflineRepository $pagamentoOfflineRepository): Response
    {
        $pagamentoOffline = new PagamentoOffline();
        $form = $this->createForm(PagamentoOfflineType::class, $pagamentoOffline);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pagamentoOfflineRepository->add($pagamentoOffline, true);

            return $this->redirectToRoute('app_pagamento_offline_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('pagamento_offline/new.html.twig', [
            'pagamento_offline' => $pagamentoOffline,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_pagamento_offline_show", methods={"GET"})
     */
    public function show(PagamentoOffline $pagamentoOffline): Response
    {
        return $this->render('pagamento_offline/show.html.twig', [
            'pagamento_offline' => $pagamentoOffline,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_pagamento_offline_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, PagamentoOffline $pagamentoOffline, PagamentoOfflineRepository $pagamentoOfflineRepository): Response
    {
        $form = $this->createForm(PagamentoOfflineType::class, $pagamentoOffline);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pagamentoOfflineRepository->add($pagamentoOffline, true);

            return $this->redirectToRoute('app_pagamento_offline_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('pagamento_offline/edit.html.twig', [
            'pagamento_offline' => $pagamentoOffline,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_pagamento_offline_delete", methods={"POST"})
     */
    public function delete(Request $request, PagamentoOffline $pagamentoOffline, PagamentoOfflineRepository $pagamentoOfflineRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pagamentoOffline->getId(), $request->request->get('_token'))) {
            $pagamentoOfflineRepository->remove($pagamentoOffline, true);
        }

        return $this->redirectToRoute('app_pagamento_offline_index', [], Response::HTTP_SEE_OTHER);
    }
}
