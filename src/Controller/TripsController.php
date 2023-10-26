<?php

namespace App\Controller;

use App\Lib\OrdersSearch;
use App\Lib\SchoolData;
use App\Lib\Woocommerce;
use App\Utils\Information;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use function PHPUnit\Framework\stringContains;

/**
 * @Route("/trips")
 */
class TripsController extends AbstractController
{

    private $information;
    private $conn;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->information = new Information($doctrine);
        $this->conn = $this->information->getConn();
    }

    /**
     * @Route("/{page}", name="page_trips", methods={"GET","POST"}, requirements={"page"="\d+"})
     */
    public function pageTrips(Request $request, int $page = 1): Response
    {
        $session = $request->getSession();

        $product        = $session->get('product') ?? 1939;
        $slug           = $session->get('slug');
        $school         = $session->get('school');
        $search_value   = $session->get('search');

        $results_per_page   = 15;
        $page_first_result  = ($page-1) * $results_per_page;

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
            ->add('school', ChoiceType::class, [
                'label' => "Filtrar por escola",
                'placeholder' => 'Todas as escolas',
                'choices' => $this->information->getSchools(),
                'required' => false,
                'choice_value' => 'value',
                'choice_label' => 'name',
                'data' => (object) [ 'value' => $school ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => "Filtrar por estado",
                'placeholder' => 'Todos os estados',
                'choices' => $this->information->getStatus(),
                'required' => false,
                'choice_value' => 'slug',
                'choice_label' => 'name',
                'data' => (object) [ 'slug' => $slug ]
            ])
            ->add('search', TextType::class, [
                'attr' => array(
                    'placeholder' => 'Pesquisar por id, nome ou email'
                ),
                'required' => false,
                'data' => $search_value
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $page               = 1;
            $page_first_result  = 0;
            $product            = $form->get("product")->getData() ? $form->get("product")->getData()->value : '';
            $slug               = $form->get("status")->getData() ? $form->get("status")->getData()->slug : '';
            $school             = $form->get("school")->getData() ? $form->get("school")->getData()->value : '';
            $search_value       = $form->get("search")->getData() ? $form->get("search")->getData() : '';
            $session->set('product', $product);
            $session->set('slug',    $slug);
            $session->set('school',  $school);
            $session->set('search',  $search_value);
        }

        $search = $school ? ' AND EXISTS (
		    SELECT wc_usermetadata.meta_value FROM travelgeneration.wp_usermeta as wc_usermetadata WHERE wc_usermetadata.user_id = wc_customer.user_id AND wc_usermetadata.meta_value = "'. $school . '"
        )' : '';

        $search .= $slug ? ' AND wc_order.status = "'. $slug .'"' : '';

        $search .= $search_value ? ' AND (wc_order.order_id LIKE "%'. $search_value .'%"
        OR EXISTS (
            SELECT wc_user_data.meta_value FROM travelgeneration.wp_usermeta as wc_user_data WHERE wc_user_data.user_id = wc_customer.user_id
            AND ((wc_user_data.meta_key = "last_name" AND wc_user_data.meta_value LIKE "%'. $search_value .'%") OR (wc_user_data.meta_key = "first_name" AND wc_user_data.meta_value LIKE "%'. $search_value .'%") OR (wc_user_data.meta_key = "billing_email" AND wc_user_data.meta_value LIKE "%'. $search_value .'%"))
        ))' : '';

        $ordersCount        = $this->getTripsCount($search, $product);
        $number_of_pages    = ceil($ordersCount / $results_per_page);

        $orders = $this->getTripsData($search, $product, $page_first_result, $results_per_page);
        
        $totals = $this->getTripsTotals($this->getTripsData($search, $product));

        return $this->render('private/trips/trips.html.twig', [
            'form'              => $form->createView(),
            'orders'            => $orders,
            'status'            => $this->information->getStatus(),
            'product'           => $product,
            'search'            => json_encode($search),
            'current_page'      => $page,
            'number_of_pages'   => $number_of_pages,
            'totals'            => $totals
        ]);
    }

    /**
     * @Route("/export/{product}", name="export_trips", methods={"GET","POST"}, requirements={"product"="\d+"})
     */
    public function exportOrders(Request $request, int $product): StreamedResponse
    {
        $columns = [
            'Id',
            'Estado',
            'Seguro',
            'Data de criação',
            'Parcelas',
            'Por pagar',
            'Pago',
            'Total',
            'Método de pagamento',
            "Nome",
            "Telefone/Telemóvel",
            "Endereço email",
            "Escola",
            "NIF",
            "Data de nascimento",
            "Nacionalidade",
            "Documento (tipo)",
            "Documento (número)",
            "Documento (validade)",
            "Contacto Emergência (Tipo)",
            "Contacto Emergência (Nome)",
            "Contacto Emergência (Email)",
            "Contacto Emergência (Telemóvel)",
            "Contacto Opcional Emergência (Tipo)",
            "Contacto Opcional Emergência (Nome)",
            "Contacto Opcional Emergência (Email)",
            "Contacto Opcional Emergência (Telemóvel)"
        ];

        $search = json_decode($request->get("json"));
        $orders = $this->getTripsData($search, $product);

        $ordersToExport = [];
        foreach ($orders as $order) {
            $ordersToExport[] = [
                "id"                            => $order->order_id,
                "status"                        => $order->statusdesc["label"],
                "insurance"                     => $order->insurance,
                "date_created"                  => $order->date_created,
                "installments"                  => (integer) $order->child_orders,
                "child_orders_unpaid_total"     => $order->child_orders_unpaid_total,
                "child_orders_paid_total"       => $order->child_orders_paid_total,
                "total"                         => $order->total,
                "payment_method"                => $order->payment_method,
                "nome"                          => $order->customer_name,
                "phone"                         => $order->phone                ?? '',
                "email"                         => $order->email                ?? '',
                "school"                        => $order->school               ?? '',
                "nif"                           => $order->nif                  ?? '',
                "birthdate"                     => $order->birthdate            ?? '',
                "nationality"                   => $order->nationality          ?? '',
                "doc_type"                      => $order->doc_type             ?? '',
                "doc_number"                    => $order->doc_number           ?? '',
                "doc_expiration_date"           => $order->doc_expiration_date  ?? '',
                "billing_emergency_type"            => $order->billing_emergency_type  ?? '',
                "billing_emergency_name"            => $order->billing_emergency_name  ?? '',
                "billing_emergency_email"           => $order->billing_emergency_email  ?? '',
                "billing_emergency_phone"           => $order->billing_emergency_phone  ?? '',
                "billing_emergency_optional_type"   => $order->billing_emergency_optional_type  ?? '',
                "billing_emergency_optional_name"   => $order->billing_emergency_optional_name  ?? '',
                "billing_emergency_optional_email"  => $order->billing_emergency_optional_email  ?? '',
                "billing_emergency_optional_phone"  => $order->billing_emergency_optional_phone  ?? ''
            ];
        }

        return $this->createExcelSpreadsheet("Viagens.xlsx", $columns, $ordersToExport);
    }

    protected function createExcelSpreadsheet($filename, $columns, $rows): StreamedResponse
    {
        $contentType = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$filename.'"');
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-cache', true);
        $response->headers->addCacheControlDirective('must-revalidate', true);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $columnLetter = 'A';
        foreach ($columns as $column) {
            $sheet->setCellValue($columnLetter++ . '1', $column);
        }

        $line = 2;
        foreach ($rows as $row) {
            $columnLetter = 'A';
            foreach($row as $value) {
                $sheet->setCellValue($columnLetter. $line, $value);
                $columnLetter++;
            }
            $line++;
        }

        $writer = new Xlsx($spreadsheet);
        $response->setCallback(function() use ($writer) {
            $writer->save('php://output');
        });

        return $response;

    }

    protected function getTripsCount($search, $product): int
    {

        $sql = 'SELECT count(*) as total
                FROM travelgeneration.wp_wc_order_stats AS wc_order
                
                INNER JOIN travelgeneration.wp_wc_customer_lookup AS wc_customer ON wc_order.customer_id = wc_customer.customer_id
                INNER JOIN travelgeneration.wp_woocommerce_order_items AS wc_item ON wc_order.order_id = wc_item.order_id
                INNER JOIN travelgeneration.wp_woocommerce_order_itemmeta AS wc_item_data ON wc_item.order_item_id = wc_item_data.order_item_id

                WHERE wc_order.parent_id = 0 AND wc_order.status <> "wc-trash" AND wc_item_data.meta_value = '. $product .'
                ' . $search . '
                ORDER BY wc_order.order_id DESC';

        $stmt = $this->conn->prepare($sql);
        $resultSet = $stmt->executeQuery();

        return (int) $resultSet->fetchNumeric()[0];

    }

    protected function getTripsData($search, $product, $page_first_result = null, $results_per_page = null) {

        $pagination = (is_numeric($page_first_result) >= 0 and $results_per_page <> null) ? ' LIMIT ' . $page_first_result . ',' . $results_per_page : '';

        $sql = 'SELECT result.order_id, result.insurance, result.date_created, result.date_paid, result.payment_method, 
            result.status, 
            result.order_total, 
            result.child_orders, 
            result.child_orders_total, 
            result.child_orders_paid, 
            result.child_orders_paid_total,
            result.product_net_revenue,
            result.child_orders - child_orders_paid AS child_orders_unpaid, 
            result.child_orders_total - result.child_orders_paid_total AS child_orders_unpaid_total, 
            result.user_data
            
            
            FROM (
                SELECT wc_order.order_id, wc_order.date_created, wc_order.status, wc_order.net_total as order_total,
            
                        (select json_arrayagg(json_object( "key", wc_usermeta.meta_key, "value", wc_usermeta.meta_value ))
                            FROM travelgeneration.wp_usermeta AS wc_usermeta
                            WHERE wc_usermeta.user_id = wc_customer.user_id 
                                AND (
                                    wc_usermeta.meta_key = "first_name"
                                    OR wc_usermeta.meta_key = "last_name"
                                    OR wc_usermeta.meta_key = "billing_school"
                                    OR wc_usermeta.meta_key = "billing_nif"
                                    OR wc_usermeta.meta_key = "billing_email"
                                    OR wc_usermeta.meta_key = "billing_phone"
                                    OR wc_usermeta.meta_key = "billing_birthdate"
                                    OR wc_usermeta.meta_key = "billing_nationality"
                                    OR wc_usermeta.meta_key = "billing_doc_type"
                                    OR wc_usermeta.meta_key = "billing_doc_number"
                                    OR wc_usermeta.meta_key = "billing_doc_expiration_date"
                                    OR wc_usermeta.meta_key = "billing_emergency_type"
                                    OR wc_usermeta.meta_key = "billing_emergency_name"
                                    OR wc_usermeta.meta_key = "billing_emergency_email"
                                    OR wc_usermeta.meta_key = "billing_emergency_phone"
                                    OR wc_usermeta.meta_key = "billing_emergency_optional_type"
                                    OR wc_usermeta.meta_key = "billing_emergency_optional_name"
                                    OR wc_usermeta.meta_key = "billing_emergency_optional_email"
                                    OR wc_usermeta.meta_key = "billing_emergency_optional_phone"
                                )
                            ) AS user_data,
                        
                        (select count(*)
                        FROM travelgeneration.wp_wc_order_stats AS child_orders
                        WHERE child_orders.parent_id = wc_order.order_id 
                        AND wc_order.status <> "wc-trash"
                        ) AS child_orders,
                        
                        #Retorna o valor total dos parcelamentos da encomenda
                        (select IFNULL(sum(child_order.net_total), 0)
                        FROM travelgeneration.wp_wc_order_stats AS child_order
                        WHERE child_order.parent_id = wc_order.order_id
                        AND wc_order.status <> "wc-trash"
                        ) AS child_orders_total,


                        #Retorna número de parcelamentos pagos
                        (select count(*) 
                        FROM travelgeneration.wp_wc_order_stats AS child_order 
                        INNER JOIN travelgeneration.wp_postmeta AS child_order_postmeta ON child_order.order_id = child_order_postmeta.post_id AND child_order_postmeta.meta_key = "_paid_date"
                        WHERE child_order.parent_id = wc_order.order_id
                        AND wc_order.status <> "wc-trash"
                        ) AS child_orders_paid,
                        

                        #Retorna valor dos parcelamentos pagos
                        (select IFNULL(sum(child_order.net_total), 0)
                        FROM travelgeneration.wp_wc_order_stats AS child_order 
                        INNER JOIN travelgeneration.wp_postmeta AS child_order_postmeta ON child_order.order_id = child_order_postmeta.post_id AND child_order_postmeta.meta_key = "_paid_date"
                        WHERE child_order.parent_id = wc_order.order_id
                        AND wc_order.status <> "wc-trash"
                        ) AS child_orders_paid_total,


                        #@LM - Retorna o valor pago pelo produto
                        (select product_net_revenue
                        FROM travelgeneration.wp_wc_order_stats AS child_order 
                        INNER JOIN travelgeneration.wp_postmeta AS child_order_postmeta ON child_order.order_id = child_order_postmeta.post_id AND child_order_postmeta.meta_key = "_paid_date"
                        INNER JOIN travelgeneration.wp_wc_order_product_lookup on wp_wc_order_product_lookup.order_id =  child_order.order_id and wp_wc_order_product_lookup.product_id = '. $product .'
                        WHERE wp_wc_order_product_lookup.order_id = wc_order.order_id) AS product_net_revenue,

                        (SELECT wp_postmeta.meta_value
                        FROM travelgeneration.wp_postmeta AS wp_postmeta 
                        WHERE wc_order.order_id = wp_postmeta.post_id AND wp_postmeta.meta_key = "_paid_date"
                        ) AS date_paid,
                        
                        (SELECT wp_postmeta.meta_value
                        FROM travelgeneration.wp_postmeta AS wp_postmeta 
                        WHERE wc_order.order_id = wp_postmeta.post_id AND wp_postmeta.meta_key = "_payment_method_title"
                        ) AS payment_method,
                       
                       (SELECT insurance.meta_value FROM travelgeneration.wp_woocommerce_order_itemmeta AS insurance WHERE insurance.order_item_id = wc_item.order_item_id AND insurance.meta_key = "pa_seguro-covid-premium") AS insurance
            
                FROM travelgeneration.wp_wc_order_stats AS wc_order
            
                INNER JOIN travelgeneration.wp_wc_customer_lookup AS wc_customer ON wc_order.customer_id = wc_customer.customer_id
                INNER JOIN travelgeneration.wp_woocommerce_order_items AS wc_item ON wc_order.order_id = wc_item.order_id
                INNER JOIN travelgeneration.wp_woocommerce_order_itemmeta AS wc_item_data ON wc_item.order_item_id = wc_item_data.order_item_id
                
                WHERE wc_order.parent_id = 0 AND wc_order.status <> "wc-trash" AND wc_item_data.meta_value = '. $product .'
                ' . $search . '
            ) AS result 
           
ORDER BY result.order_id DESC' . $pagination;

        $stmt = $this->conn->prepare($sql);
        $resultSet = $stmt->executeQuery();
        $orders = json_decode(json_encode($resultSet->fetchAllAssociative()));

        foreach ($orders as $order) {
            $order->statusdesc = $this->information->getStatusType()[$order->status];
            $firstname = ""; $lastname = "";

            $order->insurance = (str_contains($order->insurance, "sim")) ? "Sim" : "Não";

            foreach (json_decode($order->user_data) as $user_data) {
                switch ($user_data->key) {
                    case 'first_name':
                        $firstname = $user_data->value;
                        break;
                    case 'last_name':
                        $lastname = $user_data->value;
                        break;
                    case 'billing_school':
                        $order->school = $user_data->value;
                        break;
                    case 'billing_nif':
                        $order->nif = $user_data->value;
                        break;
                    case 'billing_phone':
                        $order->phone = $user_data->value;
                        break;
                    case 'billing_email':
                        $order->email = $user_data->value;
                        break;
                    case 'billing_birthdate':
                        $order->birthdate = $user_data->value;
                        break;
                    case 'billing_nationality':
                        $order->nationality = $user_data->value;
                        break;
                    case 'billing_doc_type':
                        $order->doc_type = $user_data->value;
                        break;
                    case 'billing_doc_number':
                        $order->doc_number = $user_data->value;
                        break;
                    case 'billing_doc_expiration_date':
                        $order->doc_expiration_date = $user_data->value;
                        break;
                    case 'billing_emergency_type':
                        $order->billing_emergency_type = $user_data->value;
                        break;
                    case 'billing_emergency_name':
                        $order->billing_emergency_name = $user_data->value;
                        break;
                    case 'billing_emergency_email':
                        $order->billing_emergency_email = $user_data->value;
                        break;
                    case 'billing_emergency_phone':
                        $order->billing_emergency_phone = $user_data->value;
                        break;
                    case 'billing_emergency_optional_type':
                        $order->billing_emergency_optional_type = $user_data->value;
                        break;
                    case 'billing_emergency_optional_name':
                        $order->billing_emergency_optional_name = $user_data->value;
                        break;
                    case 'billing_emergency_optional_email':
                        $order->billing_emergency_optional_email = $user_data->value;
                        break;
                    case 'billing_emergency_optional_phone':
                        $order->billing_emergency_optional_phone = $user_data->value;
                        break;
                }
            }

            $order->customer_name = $firstname . " " . $lastname;

            $order->child_orders                = $order->child_orders == 0 ? "--" : $order->child_orders + 1;
            $order->child_orders_unpaid_total   = $order->date_paid == null ? $order->child_orders_unpaid_total + $order->order_total : $order->child_orders_unpaid_total;
            $order->child_orders_paid_total     = $order->date_paid == null ? $order->child_orders_paid_total : $order->child_orders_paid_total + $order->order_total;
            $order->total                       = $order->order_total + $order->child_orders_total;
        }

        return $orders;

    }

    protected function getTripsTotals($orders): array
    {

        $totals = [
            [ "key" => "Valor de viagem",           "value" => 0.00, "color" => "text-primary" ],
            [ "key" => "Valor pago de viagem",      "value" => 0.00, "color" => "text-success" ],
            [ "key" => "Valor em falta de viagem",  "value" => 0.00, "color" => "text-danger" ],
        ];

        foreach ($orders as $order) {
            $totals[0]["value"] += (float) $order->total;
            $totals[1]["value"] += (float) $order->child_orders_paid_total;
            $totals[2]["value"] += (float) $order->child_orders_unpaid_total;
        }

        return $totals;

    }

}
