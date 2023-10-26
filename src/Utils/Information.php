<?php

namespace App\Utils;

use App\Lib\SchoolData;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

class Information {

    private $conn;

    private $statusType = [
        "wc-pending"           => [ "label" => "Pagamento pendente",               "color_class" => "badge-light-info" ],
        "wc-processing"        => [ "label" => "Em processamento",                 "color_class" => "badge-light-warning" ],
        "wc-on-hold"           => [ "label" => "Aguarda confirmação de pagamento", "color_class" => "badge-light-info" ],
        "wc-completed"         => [ "label" => "Concluída",                        "color_class" => "badge-success" ],
        "wc-cancelled"         => [ "label" => "Cancelada",                        "color_class" => "badge-light-danger" ],
        "wc-refunded"          => [ "label" => "Reembolsada",                      "color_class" => "badge-light" ],
        "wc-failed"            => [ "label" => "Falhada",                          "color_class" => "badge-light-danger" ],
        "wc-checkout-draft"    => [ "label" => "Rascunho",                         "color_class" => "badge-light" ],
        "wc-partial-payment"   => [ "label" => "Pago parcialmente",                "color_class" => "badge-light-success" ],
        "wc-scheduled-payment" => [ "label" => "Agendado",                         "color_class" => "badge-light-primary" ],
        "wc-pending-deposit"   => [ "label" => "Parcela pendente",                 "color_class" => "badge-light-secondary" ]
    ];

    private $status = [];

    private $schools = [];

    private $products = [];
    
    private $productsOptionals = [];

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->conn = $doctrine->getManager('woocommerce')->getConnection();
        $this->schools  = [
                new SchoolData('AE Bemposta', 'AE Bemposta'),
                new SchoolData('Escola Comércio Lisboa', 'Escola Comércio Lisboa'),
                new SchoolData('Escola de Hotelaria e Turismo de Coimbra', 'Escola de Hotelaria e Turismo de Coimbra'),
                new SchoolData('Escola de Hotelaria e Turismo do Douro - Lamego', 'Escola de Hotelaria e Turismo do Douro - Lamego'),
                new SchoolData('Escola de Hotelaria e Turismo do Estoril', 'Escola de Hotelaria e Turismo do Estoril'),
                new SchoolData('Escola de Hotelaria e Turismo de Faro', 'Escola de Hotelaria e Turismo de Faro'),
                new SchoolData('Escola de Hotelaria e Turismo de Lisboa', 'Escola de Hotelaria e Turismo de Lisboa'),
                new SchoolData('Escola de Hotelaria e Turismo do Oeste', 'Escola de Hotelaria e Turismo do Oeste'),
                new SchoolData('Escola de Hotelaria e Turismo de Portalegre', 'Escola de Hotelaria e Turismo de Portalegre'),
                new SchoolData('Escola de Hotelaria e Turismo do Porto', 'Escola de Hotelaria e Turismo do Porto'),
                new SchoolData('Escola de Hotelaria e Turismo de Setúbal', 'Escola de Hotelaria e Turismo de Setúbal'),
                new SchoolData('Escola de Hotelaria e Turismo de Viana do Castelo', 'Escola de Hotelaria e Turismo de Viana do Castelo'),
                new SchoolData('Escola de Hotelaria e Turismo de Vila Real de Santo António', 'Escola de Hotelaria e Turismo de Vila Real de Santo António'),
                new SchoolData('Escola Profissional Gustave Eiffel - Amadora', 'Escola Profissional Gustave Eiffel - Amadora'),
                new SchoolData('Escola Profissional Gustave Eiffel - Queluz', 'Escola Profissional Gustave Eiffel - Queluz'),
                new SchoolData('Escola Profissional Magestil', 'Escola Profissional Magestil'),
                new SchoolData('Escola Profissional de Hotelaria e Turismo do Chiado', 'Escola Profissional de Hotelaria e Turismo do Chiado'),
                new SchoolData('Escola Secundária Santa Maria da Feira', 'Escola Secundária Santa Maria da Feira'),
                new SchoolData('ESHTE - Escola Superior de Hotelaria e Turismo do Estoril', 'ESHTE - Escola Superior de Hotelaria e Turismo do Estoril'),
                new SchoolData('Instituto Educativo do Juncal', 'IEJ - Instituto Educativo do Juncal'),
                new SchoolData('Instituto Politécnico do Cávado e do Ave (IPCA)', 'Instituto Politécnico do Cávado e do Ave (IPCA)'),
                new SchoolData('Instituto Politécnico de Portalegre', 'Instituto Politécnico de Portalegre'),
                new SchoolData('Instituto Politécnico de Leiria - Peniche', 'Instituto Politécnico de Leiria - Peniche'),
                new SchoolData('Instituto Politécnico de Viana do Castelo', 'Instituto Politécnico de Viana do Castelo'),
                new SchoolData('ISEC Lisboa - Instituto Superior de Educação e Ciências', 'ISEC Lisboa - Instituto Superior de Educação e Ciências'),
                new SchoolData('ISCE - Instituto Superior de Lisboa e Vale do Tejo', 'ISCE - Instituto Superior de Lisboa e Vale do Tejo'),
                new SchoolData('ISLA - Santarém', 'ISLA - Santarém'),
                new SchoolData('Universidade do Algarve', 'Universidade do Algarve'),
                new SchoolData('Universidade de Aveiro', 'Universidade de Aveiro'),
                new SchoolData('Universidade de Coimbra', 'Universidade de Coimbra'),
                new SchoolData('Universidade Europeia', 'Universidade Europeia'),
                new SchoolData('Universidade Lusófona de Humanidades e Tecnologias', 'Universidade Lusófona de Humanidades e Tecnologias')
        ];

        $this->status   = [
            (object) [ "slug" => "wc-pending",           "name" => "Pagamento pendente"],
            (object) [ "slug" => "wc-processing",        "name" => "Em processamento"],
            (object) [ "slug" => "wc-on-hold",           "name" => "Aguarda confirmação de pagamento"],
            (object) [ "slug" => "wc-completed",         "name" => "Concluída"],
            (object) [ "slug" => "wc-cancelled",         "name" => "Cancelada"],
            (object) [ "slug" => "wc-refunded",          "name" => "Reembolsada"],
            (object) [ "slug" => "wc-failed",            "name" => "Falhada"],
            (object) [ "slug" => "wc-checkout-draft",    "name" => "Rascunho"],
            (object) [ "slug" => "wc-partial-payment",   "name" => "Pago parcialmente"],
            (object) [ "slug" => "wc-scheduled-payment", "name" => "Agendado"],
            (object) [ "slug" => "wc-pending-deposit",   "name" => "Parcela pendente"]
        ];
        $this->products = $this->getWoocommerceProducts();
        
        $this->productsOptionals   = [
            (object) [ "value" => "3253", "trip" => "Viagem à FITUR 2023 - Opcionais"],
            (object) [ "value" => "4919", "trip" => "Viagem à FITUR 2024 - Opcionais"],
            
        ];
    }

    public function getData(): array
    {
        return [
            $this->statusType,
            $this->status,
            $this->schools,
            $this->products,
            $this->productsOptionals
        ];
    }

    protected function getWoocommerceProducts() {
        // GET ONLY PRODUCTS WITH ORDERS.
        $sql = 'SELECT distinct product.product_id, product.sku, post.post_title FROM travelgeneration.wp_wc_product_meta_lookup AS product
INNER JOIN travelgeneration.wp_wc_order_product_lookup AS order_items ON order_items.product_id = product.product_id
INNER JOIN travelgeneration.wp_posts AS post ON post.id = product.product_id
WHERE product.sku <> "" AND post.post_title NOT LIKE "%opcionais%";';

        $stmt       = $this->conn->prepare($sql);
        $resultSet  = $stmt->executeQuery();

        $products = json_decode(json_encode($resultSet->fetchAllAssociative()));
        $products_out = [];

        foreach ($products as $product) {
            $products_out[] = (object)['value' => $product->product_id, 'trip' => $product->post_title];
        }

        return $products_out;
    }

    /**
     * @return Connection
     */
    public function getConn(): Connection
    {
        return $this->conn;
    }

    /**
     * @param Connection $conn
     */
    public function setConn(Connection $conn): void
    {
        $this->conn = $conn;
    }

    /**
     * @return \string[][]
     */
    public function getStatusType(): array
    {
        return $this->statusType;
    }

    /**
     * @param \string[][] $statusType
     */
    public function setStatusType(array $statusType): void
    {
        $this->statusType = $statusType;
    }

    /**
     * @return object[]
     */
    public function getStatus(): array
    {
        return $this->status;
    }

    /**
     * @param object[] $status
     */
    public function setStatus(array $status): void
    {
        $this->status = $status;
    }

    /**
     * @return SchoolData[]
     */
    public function getSchools(): array
    {
        return $this->schools;
    }

    /**
     * @param SchoolData[] $schools
     */
    public function setSchools(array $schools): void
    {
        $this->schools = $schools;
    }

    /**
     * @return object[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    /**
     * @param object[] $products
     */
    public function setProducts(array $products): void
    {
        $this->products = $products;
    }

    
    
    public function getProductsOptionals() {
        return $this->productsOptionals;
    }

    public function setProductsOptionals($productsOptionals): void {
        $this->productsOptionals = $productsOptionals;
    }


    
    
    
}