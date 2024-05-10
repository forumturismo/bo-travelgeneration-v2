<?php

namespace App\Controller;

use App\Entity\PagamentoOffline;
use App\Form\PagamentoOfflineType;
use App\Repository\PagamentoOfflineRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @Route("/pagamento/offline")
 */
class PagamentoOfflineController extends AbstractController {

    /**
     * @Route("/", name="app_pagamento_offline_index", methods={"GET"})
     */
    public function index(PagamentoOfflineRepository $pagamentoOfflineRepository): Response {


        $this->exportToExcel($pagamentoOfflineRepository->findAll());

        return $this->render('pagamento_offline/index.html.twig', [
                    'pagamento_offlines' => $pagamentoOfflineRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="app_pagamento_offline_new", methods={"GET", "POST"})
     */
    public function new(Request $request, PagamentoOfflineRepository $pagamentoOfflineRepository): Response {
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
    public function show(PagamentoOffline $pagamentoOffline): Response {
        return $this->render('pagamento_offline/show.html.twig', [
                    'pagamento_offline' => $pagamentoOffline,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_pagamento_offline_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, PagamentoOffline $pagamentoOffline, PagamentoOfflineRepository $pagamentoOfflineRepository): Response {
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
    public function delete(Request $request, PagamentoOffline $pagamentoOffline, PagamentoOfflineRepository $pagamentoOfflineRepository): Response {
        if ($this->isCsrfTokenValid('delete' . $pagamentoOffline->getId(), $request->request->get('_token'))) {
            $pagamentoOfflineRepository->remove($pagamentoOffline, true);
        }

        return $this->redirectToRoute('app_pagamento_offline_index', [], Response::HTTP_SEE_OTHER);
    }

    public function exportToExcel($pagamentos) {


        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();

        $activeWorksheet->setCellValue('A1', 'Data');
        $activeWorksheet->setCellValue('B1', 'Num File');
        $activeWorksheet->setCellValue('C1', 'Cliente');
        $activeWorksheet->setCellValue('D1', 'Comercial');
        $activeWorksheet->setCellValue('E1', 'Serviço');
        $activeWorksheet->setCellValue('F1', 'Preço Venda');
        $activeWorksheet->setCellValue('G1', 'Preço Custo');
        $activeWorksheet->setCellValue('H1', 'Margem Bruta');
        $activeWorksheet->setCellValue('I1', 'Comissão');
        $activeWorksheet->setCellValue('J1', 'Comissão Valor');
        $activeWorksheet->setCellValue('K1', 'Valor Pago');
        $activeWorksheet->setCellValue('L1', 'Valor Pendente');
        $activeWorksheet->setCellValue('M1', 'Metodo Pagamento');

        $line = 2;
        foreach ($pagamentos as $key => $pagamento) {
            $activeWorksheet->setCellValue('A' . $line, $pagamento->getData());
            $activeWorksheet->setCellValue('B' . $line, $pagamento->getNumFile());
            $activeWorksheet->setCellValue('C' . $line, $pagamento->getCliente());
            $activeWorksheet->setCellValue('D' . $line, $pagamento->getComercial());
            $activeWorksheet->setCellValue('E' . $line, $pagamento->getServico());
            $activeWorksheet->setCellValue('F' . $line, $pagamento->getPrecoVenda());
            $activeWorksheet->setCellValue('G' . $line, $pagamento->getPrecoCusto());
            $activeWorksheet->setCellValue('H' . $line, $pagamento->getMargemBruta());

            $activeWorksheet->setCellValue('I' . $line, $pagamento->getComissao());
            $activeWorksheet->setCellValue('J' . $line, $pagamento->getComissaoValor());
            $activeWorksheet->setCellValue('K' . $line, $pagamento->getValorPago());
            $activeWorksheet->setCellValue('L' . $line, $pagamento->getValorPendente());
            $activeWorksheet->setCellValue('M' . $line, $pagamento->getMetodoPagamento());

            $line = $line + 1;
        }



        $writer = new Xlsx($spreadsheet);
        $writer->save('assets/pagamentos_offline.xlsx');
    }
}
