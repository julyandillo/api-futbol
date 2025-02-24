<?php

namespace App\Entity;

use App\Config\Posicion;
use App\Repository\JugadorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: JugadorRepository::class)]
class Jugador
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('lista')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups('lista')]
    private ?string $apodo = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fechaNacimiento = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paisNacimiento = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nacionalidad = null;

    #[ORM\Column(length: 3, enumType: Posicion::class)]
    #[Groups('lista')]
    private Posicion $posicion;

    #[ORM\Column(nullable: true)]
    private ?int $altura = null;

    #[ORM\Column(nullable: true)]
    private ?float $peso = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApodo(): ?string
    {
        return $this->apodo;
    }

    public function setApodo(string $apodo): self
    {
        $this->apodo = $apodo;

        return $this;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getFechaNacimiento(): ?\DateTimeInterface
    {
        return $this->fechaNacimiento;
    }

    public function setFechaNacimiento(?\DateTimeInterface $fechaNacimiento): self
    {
        $this->fechaNacimiento = $fechaNacimiento;

        return $this;
    }

    public function getEdad(): int
    {
        return $this->fechaNacimiento->diff(new \DateTime())->y;
    }

    public function getPaisNacimiento(): ?string
    {
        return $this->paisNacimiento;
    }

    public function setPaisNacimiento(?string $paisNacimiento): self
    {
        $this->paisNacimiento = $paisNacimiento;

        return $this;
    }

    public function getNacionalidad(): ?string
    {
        return $this->nacionalidad;
    }

    public function setNacionalidad(?string $nacionalidad): self
    {
        $this->nacionalidad = $nacionalidad;

        return $this;
    }

    public function getPosicion(): Posicion
    {
        return $this->posicion;
    }

    public function setPosicion(Posicion $posicion): self
    {
        $this->posicion = $posicion;

        return $this;
    }

    public static function getArrayConCamposObligatorios(): array
    {
        return ['apodo', 'nombre', 'posicion'];
    }

    public function getAltura(): ?int
    {
        return $this->altura;
    }

    public function setAltura(?int $altura): static
    {
        $this->altura = $altura;

        return $this;
    }

    public function getPeso(): ?float
    {
        return $this->peso;
    }

    public function setPeso(?float $peso): static
    {
        $this->peso = $peso;

        return $this;
    }
}
