<?php

namespace App\Entity;

use App\Repository\PagamentoOfflineRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PagamentoOfflineRepository::class)
 * @ORM\Table(name="pagamento_offline")
 */
class PagamentoOffline
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    
        
    /**
     * @ORM\Column(name="data", type="date")
     */
    private $data;

    
    
    
    /**
     * @ORM\Column(name="num_file", type="string", length=255)
     */
    private $numFile;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $cliente;

    
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $comercial;

    
    
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $servico;

    
    /**
     * @ORM\Column(name="preco_venda", type="decimal", precision=10, scale=2)
     */
    private $precoVenda;

    
        /**
     * @ORM\Column(name="preco_custo", type="decimal", precision=10, scale=2)
     */
    private $precoCusto;

    
    /**
     * @ORM\Column(name="comissao", type="decimal", precision=10, scale=0)
     */
    private $comissao;

    
    
    
    /**
     * @ORM\Column(name="valor_pago", type="decimal", precision=10, scale=2)
     */
    private $valorPago;

//    /**
//     * @ORM\Column(name="valor_pendente", type="decimal", precision=10, scale=2)
//     */
//    private $valorPendente;

    /**
     * @ORM\Column(name="metodo_pagamento", type="string", length=255)
     */
    private $metodoPagamento;

    
    
    
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumFile(): ?string
    {
        return $this->numFile;
    }

    public function setNumFile(?string $numFile): self
    {
        $this->numFile = $numFile;

        return $this;
    }

    public function getCliente(): ?string
    {
        return $this->cliente;
    }

    public function setCliente(string $cliente): self
    {
        $this->cliente = $cliente;

        return $this;
    }

    public function getDestino(): ?string
    {
        return $this->destino;
    }

    public function setDestino(string $destino): self
    {
        $this->destino = $destino;

        return $this;
    }

    public function getComercial(): ?string
    {
        return $this->comercial;
    }

    public function setComercial(string $comercial): self
    {
        $this->comercial = $comercial;

        return $this;
    }

    public function getPrecoVenda(): ?string
    {
        return $this->precoVenda;
    }

    public function setPrecoVenda(string $precoVenda): self
    {
        $this->precoVenda = $precoVenda;

        return $this;
    }

    public function getValorPago(): ?string
    {
        return $this->valorPago;
    }

    public function setValorPago(string $valorPago): self
    {
        $this->valorPago = $valorPago;

        return $this;
    }

//    public function getValorPendente(): ?string
//    {
//        return $this->valorPendente;
//    }
//
//    public function setValorPendente(string $valorPendente): self
//    {
//        $this->valorPendente = $valorPendente;
//
//        return $this;
//    }

    public function getMetodoPagamento(): ?string
    {
        return $this->metodoPagamento;
    }

    public function setMetodoPagamento(string $metodoPagamento): self
    {
        $this->metodoPagamento = $metodoPagamento;

        return $this;
    }
    
    
    public function getData() {
        return $this->data;
    }

    public function getServico() {
        return $this->servico;
    }

    public function getPrecoCusto() {
        return $this->precoCusto;
    }

    public function getComissao() {
        return $this->comissao;
    }

    public function setData($data): void {
        $this->data = $data;
    }

    public function setServico($servico): void {
        $this->servico = $servico;
    }

    public function setPrecoCusto($precoCusto): void {
        $this->precoCusto = $precoCusto;
    }

    public function setComissao($comissao): void {
        $this->comissao = $comissao;
    }


    
    
    
    
}
