<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cost_events')]
class CostEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dateTime;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $budget;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $cost;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateTime(): \DateTimeInterface
    {
        return $this->dateTime;
    }

    public function setDateTime(\DateTimeInterface $dateTime): self
    {
        $this->dateTime = $dateTime;
        return $this;
    }

    public function getBudget(): string
    {
        return $this->budget;
    }

    public function setBudget(string $budget): self
    {
        $this->budget = $budget;
        return $this;
    }

    public function getCost(): string
    {
        return $this->cost;
    }

    public function getCostAsFloat(): float
    {
        return (float) $this->cost;
    }

    public function setCost(string $cost): self
    {
        $this->cost = $cost;
        return $this;
    }
}