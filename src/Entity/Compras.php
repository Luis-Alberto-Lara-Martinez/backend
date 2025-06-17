<?php

namespace App\Entity;

use App\Repository\ComprasRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ComprasRepository::class)]
class Compras
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'compras')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Usuarios $usuario = null;

    #[ORM\Column]
    private ?float $precio_total = null;

    #[ORM\Column]
    private ?\DateTime $fecha = null;

    /**
     * @var Collection<int, DetallesCompra>
     */
    #[ORM\OneToMany(targetEntity: DetallesCompra::class, mappedBy: 'compra')]
    private Collection $detallesCompras;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $transaccion_id = null;

    public function __construct()
    {
        $this->detallesCompras = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsuario(): ?Usuarios
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuarios $usuario): static
    {
        $this->usuario = $usuario;

        return $this;
    }

    public function getPrecioTotal(): ?float
    {
        return $this->precio_total;
    }

    public function setPrecioTotal(float $precio_total): static
    {
        $this->precio_total = $precio_total;

        return $this;
    }

    public function getFecha(): ?\DateTime
    {
        return $this->fecha;
    }

    public function setFecha(\DateTime $fecha): static
    {
        $this->fecha = $fecha;

        return $this;
    }

    /**
     * @return Collection<int, DetallesCompra>
     */
    public function getDetallesCompras(): Collection
    {
        return $this->detallesCompras;
    }

    public function addDetallesCompra(DetallesCompra $detallesCompra): static
    {
        if (!$this->detallesCompras->contains($detallesCompra)) {
            $this->detallesCompras->add($detallesCompra);
            $detallesCompra->setCompra($this);
        }

        return $this;
    }

    public function removeDetallesCompra(DetallesCompra $detallesCompra): static
    {
        if ($this->detallesCompras->removeElement($detallesCompra)) {
            // set the owning side to null (unless already changed)
            if ($detallesCompra->getCompra() === $this) {
                $detallesCompra->setCompra(null);
            }
        }

        return $this;
    }

    public function getTransaccionId(): ?string
    {
        return $this->transaccion_id;
    }

    public function setTransaccionId(string $transaccion_id): static
    {
        $this->transaccion_id = $transaccion_id;

        return $this;
    }
}
