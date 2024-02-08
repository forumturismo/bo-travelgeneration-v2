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
 * @Route("/coupon")
 */
class CouponController extends AbstractController
{

    private $information;
    private $conn;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->information = new Information($doctrine);
        $this->conn = $this->information->getConn();
    }

    /**
     * @Route("/", name="page_coupon", methods={"GET","POST"})
     */
    public function pageCoupon(Request $request, ManagerRegistry $doctrine): Response
    {

        
//        $sql = 'SELECT coupon_id as id,
//                post_title as name, 
//                post_status as status,
//                post_type as type,
//                order_id,
//                discount_amount
//                FROM travelgeneration.wp_posts 
//                join wp_wc_order_coupon_lookup on wp_posts.id = wp_wc_order_coupon_lookup.coupon_id';

        
        $sql = 'select distinct (xpto.coupon_id) as id_cupao, wp_posts.post_title as nome_cupao, xpto.soma as quantidade_utilizada, wp_wc_order_coupon_lookup.discount_amount as valor_cupao from (select coupon_id, count(coupon_id) as soma from wp_wc_order_coupon_lookup group by coupon_id) as xpto 
join travelgeneration.wp_posts on wp_posts.id = xpto.coupon_id
inner join wp_wc_order_coupon_lookup on wp_wc_order_coupon_lookup.coupon_id = xpto.coupon_id';
        
        
        
        $stmt = $this->conn->prepare($sql);
        $resultSet = $stmt->executeQuery();
        
        $coupons = $resultSet->fetchAllAssociative();
        

        return $this->render('private/coupon/index.html.twig', [
            'coupons'   => $coupons
        ]);
    }

}
