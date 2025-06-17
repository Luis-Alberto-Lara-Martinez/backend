<?php

namespace App\Entity;

use App\Repository\VideojuegosRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VideojuegosRepository::class)]
class Videojuegos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column]
    private ?float $precio = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $fecha_lanzamiento = null;

    #[ORM\Column]
    private ?bool $deleted = null;

    #[ORM\Column]
    private ?\DateTime $created_at = null;

    #[ORM\ManyToOne(inversedBy: 'videojuegos_created_by')]
    private ?Usuarios $created_by = null;

    #[ORM\Column]
    private ?\DateTime $modified_at = null;

    #[ORM\ManyToOne(inversedBy: 'videojuegos_modified_by')]
    private ?Usuarios $modified_by = null;

    /**
     * @var Collection<int, Imagenes>
     */
    #[ORM\OneToMany(targetEntity: Imagenes::class, mappedBy: 'videojuego')]
    private Collection $imagenes;

    /**
     * @var Collection<int, Categorias>
     */
    #[ORM\ManyToMany(targetEntity: Categorias::class, inversedBy: 'videojuegos')]
    private Collection $categoria;

    #[ORM\ManyToOne(inversedBy: 'videojuegos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Plataformas $plataforma = null;

    /**
     * @var Collection<int, DetallesCompra>
     */
    #[ORM\OneToMany(targetEntity: DetallesCompra::class, mappedBy: 'videojuego')]
    private Collection $detallesCompras;

    /**
     * @var Collection<int, Reviews>
     */
    #[ORM\OneToMany(targetEntity: Reviews::class, mappedBy: 'videojuego')]
    private Collection $reviews;

    #[ORM\Column]
    private ?int $stock = null;

    #[ORM\Column(nullable: true)]
    private ?float $nota_media = null;

    public function __construct()
    {
        $this->imagenes = new ArrayCollection();
        $this->categoria = new ArrayCollection();
        $this->detallesCompras = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getPrecio(): ?float
    {
        return $this->precio;
    }

    public function setPrecio(float $precio): static
    {
        $this->precio = $precio;

        return $this;
    }

    public function getFechaLanzamiento(): ?\DateTime
    {
        return $this->fecha_lanzamiento;
    }

    public function setFechaLanzamiento(\DateTime $fecha_lanzamiento): static
    {
        $this->fecha_lanzamiento = $fecha_lanzamiento;

        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): static
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getCreatedBy(): ?Usuarios
    {
        return $this->created_by;
    }

    public function setCreatedBy(?Usuarios $created_by): static
    {
        $this->created_by = $created_by;

        return $this;
    }

    public function getModifiedAt(): ?\DateTime
    {
        return $this->modified_at;
    }

    public function setModifiedAt(\DateTime $modified_at): static
    {
        $this->modified_at = $modified_at;

        return $this;
    }

    public function getModifiedBy(): ?Usuarios
    {
        return $this->modified_by;
    }

    public function setModifiedBy(?Usuarios $modified_by): static
    {
        $this->modified_by = $modified_by;

        return $this;
    }

    /**
     * @return Collection<int, Imagenes>
     */
    public function getImagenes(): Collection
    {
        return $this->imagenes;
    }

    public function addImagene(Imagenes $imagene): static
    {
        if (!$this->imagenes->contains($imagene)) {
            $this->imagenes->add($imagene);
            $imagene->setVideojuego($this);
        }

        return $this;
    }

    public function removeImagene(Imagenes $imagene): static
    {
        if ($this->imagenes->removeElement($imagene)) {
            // set the owning side to null (unless already changed)
            if ($imagene->getVideojuego() === $this) {
                $imagene->setVideojuego(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Categorias>
     */
    public function getCategoria(): Collection
    {
        return $this->categoria;
    }

    public function addCategoria(Categorias $categorium): static
    {
        if (!$this->categoria->contains($categorium)) {
            $this->categoria->add($categorium);
        }

        return $this;
    }

    public function removeCategoria(Categorias $categorium): static
    {
        $this->categoria->removeElement($categorium);

        return $this;
    }

    public function getPlataforma(): ?Plataformas
    {
        return $this->plataforma;
    }

    public function setPlataforma(?Plataformas $plataforma): static
    {
        $this->plataforma = $plataforma;

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
            $detallesCompra->setVideojuego($this);
        }

        return $this;
    }

    public function removeDetallesCompra(DetallesCompra $detallesCompra): static
    {
        if ($this->detallesCompras->removeElement($detallesCompra)) {
            // set the owning side to null (unless already changed)
            if ($detallesCompra->getVideojuego() === $this) {
                $detallesCompra->setVideojuego(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Reviews>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Reviews $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setVideojuego($this);
        }

        return $this;
    }

    public function removeReview(Reviews $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getVideojuego() === $this) {
                $review->setVideojuego(null);
            }
        }

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getNotaMedia(): ?float
    {
        return $this->nota_media;
    }

    public function setNotaMedia(?float $nota_media): static
    {
        $this->nota_media = $nota_media;

        return $this;
    }
}
