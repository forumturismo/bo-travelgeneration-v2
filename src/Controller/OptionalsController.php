<?php

namespace App\Controller;

use App\Lib\OrdersSearch;
use App\Lib\Woocommerce;
use App\Utils\Information;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/optionals")
 */
class OptionalsController extends AbstractController
{

    private $information;
    private $conn;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->information = new Information($doctrine);
        $this->conn = $this->information->getConn();
    }

    /**
     * @Route("/", name="page_optionals", methods={"GET","POST"})
     */
    public function getOptionals(Request $request): Response
    {
        $slug           = "";
        $school         = "";
        $search_value   = "";
        $product        = 3253;
        //$product        = 4919;

        $formStatusFilter = new OrdersSearch();
        $form = $this->createFormBuilder($formStatusFilter)
            ->add('product', ChoiceType::class, [
                'label' => "Filtrar por produto opcional",
//                'placeholder' => 'Todos os produtos',
                'choices' => $this->information->getProductsOptionals(),
                'required' => true,
                'choice_value' => 'value',
                'choice_label' => 'trip'
            ])
            ->add('school', ChoiceType::class, [
                'label' => "Filtrar por escola",
                'placeholder' => 'Todas as escolas',
                'choices' => $this->information->getSchools(),
                'required' => false,
                'choice_value' => 'value',
                'choice_label' => 'name'
            ])
            ->add('status', ChoiceType::class, [
                'label' => "Filtrar por estado",
                'placeholder' => 'Todos os estados',
                'choices' => $this->information->getStatus(),
                'required' => false,
                'choice_value' => 'slug',
                'choice_label' => 'name'
            ])
            ->add('search', TextType::class, [
                'attr' => array(
                    'placeholder' => 'Pesquisar por id, nome ou email'
                ),
                'required' => false
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product      = $form->get("product")->getData() ? $form->get("product")->getData()->value : '';
            $slug         = $form->get("status")->getData() ? $form->get("status")->getData()->slug : '';
            $school       = $form->get("school")->getData() ? $form->get("school")->getData()->value : '';
            $search_value = $form->get("search")->getData() ? $form->get("search")->getData() : '';
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

        $optionals = $this->getOptionalsData($search, $product, '');
        $columns   = $this->getOptionalsColumns($optionals);

        $totals = [];
        foreach ($columns as $column) {
            $totals[] = [ "key" => $column, "value" => 0 ];
        }

        foreach ($optionals as $optional) {
            $optional->item_data = $this->sortArrayByColumns($optional->item_data, $columns);
            foreach ($optional->item_data as $i => $column_value) {
                if ($column_value === "Sim") {
                    $totals[$i]["value"]++;
                }
            }
        }

        return $this->render('private/optionals/optionals.html.twig', [
            'product'           => $product,
            'form'              => $form->createView(),
            'optionals'         => $optionals,
            'columns'           => $columns,
            'totals'            => $totals
        ]);
    }

    /**
     * @Route("/export/{product}", name="export_optionals", methods={"GET","POST"})
     */
    public function exportOptionals(Request $request, int $product): StreamedResponse
    {
        $columns = [
            'Id',
            'Estado',
            'Data de criação',
            "Nome",
            "Endereço email",
            "Escola",
            "Contacto",
        ];

        $optionals = $this->getOptionalsData("", $product, '');

        //Append optionals columns
        $optionalsColumns = $this->getOptionalsColumns($optionals);
        $columns = array_merge($columns, $optionalsColumns);

        $optionalsToExport = [];
        foreach ($optionals as $optional) {
            $optional->item_data = $this->sortArrayByColumns($optional->item_data, $optionalsColumns);
            $optionalData = [
                "id"                => $optional->order_id,
                "status"            => $optional->statusdesc["label"],
                "date_created"      => $optional->date_created,
                "nome"              => $optional->customer_name,
                "email"             => $optional->email ?? '',
                "school"            => $optional->school ?? '',
                "phone"             => $optional->phone ?? '',
            ];
            $optionalsToExport[] = array_merge($optionalData, $optional->item_data);
        }

        return $this->createExcelSpreadsheet("Opcionais.xlsx", $columns, $optionalsToExport);
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

    protected function getOptionalsData($search, $product, $condition) {

        $sql = 'SELECT wc_order.order_id, wc_order.date_created, wc_order.status, wc_order.num_items_sold, wc_order.total_sales,
       
                (SELECT COUNT(wc_order_item.order_item_id) AS total
                    FROM travelgeneration.wp_woocommerce_order_items AS wc_order_item
                    WHERE wc_order.order_id = wc_order_item.order_id
                ) AS num_items,
       
                (select json_arrayagg(json_object( "key", wc_usermeta.meta_key, "value", wc_usermeta.meta_value ))
                    FROM travelgeneration.wp_usermeta AS wc_usermeta
                    WHERE wc_usermeta.user_id = wc_customer.user_id AND (wc_usermeta.meta_key = "first_name" OR wc_usermeta.meta_key = "last_name" OR wc_usermeta.meta_key = "billing_email" OR wc_usermeta.meta_key = "billing_school" OR wc_usermeta.meta_key = "billing_phone")
                ) AS user_data,
                
                (select json_arrayagg(json_object( "key", wc_items.meta_key, "value", wc_items.meta_value ))
                    FROM travelgeneration.wp_woocommerce_order_itemmeta AS wc_items
                    WHERE wc_items.order_item_id = wc_item.order_item_id AND (wc_items.meta_key LIKE "pa_%")
                ) AS item_data
                
                FROM travelgeneration.wp_wc_order_stats AS wc_order
                
                INNER JOIN travelgeneration.wp_wc_customer_lookup AS wc_customer ON wc_order.customer_id = wc_customer.customer_id
                INNER JOIN travelgeneration.wp_woocommerce_order_items AS wc_item ON wc_order.order_id = wc_item.order_id
                INNER JOIN travelgeneration.wp_woocommerce_order_itemmeta AS wc_item_data ON wc_item.order_item_id = wc_item_data.order_item_id
                
                WHERE wc_order.status <> "wc-trash" AND wc_item_data.meta_value = '. $product . $condition . '
                AND EXISTS (
                    SELECT wc_itemmeta.meta_value
                    FROM travelgeneration.wp_woocommerce_order_itemmeta AS wc_itemmeta
                    WHERE wc_itemmeta.order_item_id = wc_item.order_item_id AND wc_itemmeta.meta_key LIKE "pa_%" AND wc_itemmeta.meta_value LIKE "sim%"
                )
                ' . $search . '
                ORDER BY order_id DESC';

        $stmt = $this->conn->prepare($sql);
        $resultSet = $stmt->executeQuery();
        $optionals = json_decode(json_encode($resultSet->fetchAllAssociative()));

        foreach ($optionals as $optional) {
            $optional->statusdesc = $this->information->getStatusType()[$optional->status];
            $optional->item_data = json_decode($optional->item_data);
            
            //dump($optional->item_data);
            
            $firstname = ""; $lastname = "";
            foreach (json_decode($optional->user_data) as $user_data) {
                switch ($user_data->key) {
                    case 'first_name':
                        $firstname = $user_data->value;
                        break;
                    case 'last_name':
                        $lastname = $user_data->value;
                        break;
                    case 'billing_email':
                        $optional->email = $user_data->value;
                        break;
                    case 'billing_school':
                        $optional->school = $user_data->value;
                        break;
                    case 'billing_phone':
                        $optional->phone = $user_data->value;
                        break;
                }
            }
            $optional->customer_name = $firstname . " " . $lastname;
        }

        return $optionals;

    }

    protected function getOptionalsColumns(array $optionals): array {
        $columns = [];
        foreach ($optionals as $optional) {
            foreach ($optional->item_data as $item_data) {
                if (!in_array($item_data->key, $columns)) {
                    $columns[] = $item_data->key;
                }
            }
        }
        return $columns;
    }

    protected function sortArrayByColumns(array $optionals, array $columns): array
    {
        $ordered = array();
        foreach ($columns as $column) {
            $index = array_search($column, array_column($optionals, 'key'));
            $ordered[] = (is_numeric($index)) ? (($optionals[$index]->value === "nao") ? "Não" : "Sim" ) : "";
        }
        return $ordered;
    }

}
