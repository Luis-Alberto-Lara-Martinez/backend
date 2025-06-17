<?php

namespace App\Entity;

use App\Repository\ImagenesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImagenesRepository::class)]
class Imagenes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $url = null;

    #[ORM\Column]
    private ?bool $portada = null;

    #[ORM\ManyToOne(inversedBy: 'imagenes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Videojuegos $videojuego = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function isPortada(): ?bool
    {
        return $this->portada;
    }

    public function setPortada(bool $portada): static
    {
        $this->portada = $portada;

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
}
