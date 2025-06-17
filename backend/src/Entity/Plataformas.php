<?php

namespace App\Entity;

use App\Repository\PlataformasRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlataformasRepository::class)]
class Plataformas
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    /**
     * @var Collection<int, Videojuegos>
     */
    #[ORM\OneToMany(targetEntity: Videojuegos::class, mappedBy: 'plataforma')]
    private Collection $videojuegos;

    public function __construct()
    {
        $this->videojuegos = new ArrayCollection();
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

    /**
     * @return Collection<int, Videojuegos>
     */
    public function getVideojuegos(): Collection
    {
        return $this->videojuegos;
    }

    public function addVideojuego(Videojuegos $videojuego): static
    {
        if (!$this->videojuegos->contains($videojuego)) {
            $this->videojuegos->add($videojuego);
            $videojuego->setPlataforma($this);
        }

        return $this;
    }

    public function removeVideojuego(Videojuegos $videojuego): static
    {
        if ($this->videojuegos->removeElement($videojuego)) {
            // set the owning side to null (unless already changed)
            if ($videojuego->getPlataforma() === $this) {
                $videojuego->setPlataforma(null);
            }
        }

        return $this;
    }
}
