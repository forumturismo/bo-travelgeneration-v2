<?php

namespace App\Controller;

use App\Lib\OrdersSearch;
use App\Utils\Information;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/statistics")
 */
class StatisticsController extends AbstractController
{

    private $information;
    private $conn;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->information = new Information($doctrine);
        $this->conn = $this->information->getConn();
    }

    /**
     * @Route("/", name="page_statistics", methods={"GET","POST"})
     */
    public function pageStatistics(Request $request, ManagerRegistry $doctrine): Response
    {

        $trips_resume = [];

        $session = $request->getSession();

        $product = $session->get('product') ?? 0;

        $formStatusFilter = new OrdersSearch();

        $form = $this->createFormBuilder($formStatusFilter)
            ->add('product', ChoiceType::class, [
                'label' => "Filtrar por viagem",
                'choices' => $this->information->getProducts(),
                'required' => true,
                'choice_value' => 'value',
                'choice_label' => 'trip',
                'data' => (object) [ 'value' => $product ]
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product = $form->get("product")->getData() ? $form->get("product")->getData()->value : '';
            $session->set('product', $product);
        }

        $totals_schools = $this->getOrdersTotalsSchools($doctrine, $product);
        $totals_status  = $this->getOrdersTotalsStatus($doctrine, $product);

        $trip_resume = [
            "schools_resume"     => $totals_schools,
            "total_purchases"    => array_sum(array_column($totals_schools, "total")),
            "total_status"       => $totals_status
        ];

        return $this->render('private/statistics/statistics.html.twig', [
            'form'                  => $form->createView(),
            'product'               => $product,
            'trip_school_resume'   => $trip_resume
        ]);
    }

    protected function getOrdersTotalsSchools($doctrine, $product) {

        $sql = '
            SELECT wc_usermeta.meta_value AS school, count(*) AS total
    
            FROM travelgeneration.wp_wc_order_stats AS wc_order
            
            INNER JOIN travelgeneration.wp_wc_customer_lookup AS wc_customer ON wc_order.customer_id = wc_customer.customer_id
            INNER JOIN travelgeneration.wp_woocommerce_order_items AS wc_item ON wc_order.order_id = wc_item.order_id
            INNER JOIN travelgeneration.wp_woocommerce_order_itemmeta AS wc_item_data ON wc_item.order_item_id = wc_item_data.order_item_id
            INNER JOIN travelgeneration.wp_usermeta AS wc_usermeta ON wc_usermeta.user_id = wc_customer.user_id AND wc_usermeta.meta_key = "billing_school"
            
            WHERE wc_order.parent_id = 0 AND wc_order.status <> "wc-trash" ';
            
if($product != 0):
                    $sql = $sql .' AND wc_item_data.meta_value = '. $product .' ';
                endif;


                
            #LM - BEGIN
            $sql = $sql .' AND wc_order.order_id not in(SELECT wp_posts.ID FROM wp_posts WHERE post_status like "%trash%" and post_type = "shop_order")
            #LM - END
                        

            GROUP BY school
        ';

        $stmt = $this->conn->prepare($sql);
        $resultSet = $stmt->executeQuery();

        return json_decode(json_encode($resultSet->fetchAllAssociative()));
    }

    protected function getOrdersTotalsStatus($doctrine, $product) {

        $sql = '
            SELECT wc_order.status, count(*) AS total
            
            FROM travelgeneration.wp_wc_order_stats AS wc_order
                
            INNER JOIN travelgeneration.wp_woocommerce_order_items AS wc_item ON wc_order.order_id = wc_item.order_id
            INNER JOIN travelgeneration.wp_woocommerce_order_itemmeta AS wc_item_data ON wc_item.order_item_id = wc_item_data.order_item_id
            
            WHERE wc_order.parent_id = 0 AND wc_order.status <> "wc-trash" ';
            
if($product != 0):
                    $sql = $sql .' AND wc_item_data.meta_value = '. $product .' ';
                endif;


                
            #LM - BEGIN
            $sql = $sql .' AND wc_order.order_id not in(SELECT wp_posts.ID FROM wp_posts WHERE post_status like "%trash%" and post_type = "shop_order")
            #LM - END
            GROUP BY wc_order.status;
        ';

        $stmt = $this->conn->prepare($sql);
        $resultSet = $stmt->executeQuery();

        $result = json_decode(json_encode($resultSet->fetchAllAssociative()));

        foreach ($result as $total) {
            $total->status_desc = $this->information->getStatusType()[$total->status];
        }

        return $result;
    }

}
