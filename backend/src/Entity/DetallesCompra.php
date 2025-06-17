<?php

namespace App\Entity;

use App\Repository\DetallesCompraRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DetallesCompraRepository::class)]
class DetallesCompra
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'detallesCompras')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Compras $compra = null;

    #[ORM\ManyToOne(inversedBy: 'detallesCompras')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Videojuegos $videojuego = null;

    #[ORM\Column]
    private ?int $cantidad = null;

    #[ORM\Column]
    private ?float $precio_unitario = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompra(): ?Compras
    {
        return $this->compra;
    }

    public function setCompra(?Compras $compra): static
    {
        $this->compra = $compra;

        return $this;
    }

    public function getVideojuego(): ?Videojuegos
    {
        return $this->videojuego;
    }

    public function setVideojuego(?Videojuegos $videojuego): static
    {
        $this->videojuego = $videojuego;

        return $this;
    }

    public function getCantidad(): ?int
    {
        return $this->cantidad;
    }

    public function setCantidad(int $cantidad): static
    {
        $this->cantidad = $cantidad;

        return $this;
    }

    public function getPrecioUnitario(): ?float
    {
        return $this->precio_unitario;
    }

    public function setPrecioUnitario(float $precio_unitario): static
    {
        $this->precio_unitario = $precio_unitario;

        return $this;
    }
}
