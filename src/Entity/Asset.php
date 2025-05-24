<?php

namespace App\Entity;

use App\Repository\AssetRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AssetRepository::class)
 */
class Asset
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=20, unique=true)
     */
    private $symbol;

    /**
     * @ORM\Column(type="decimal", precision=18, scale=8)
     */
    private $bid;

    /**
     * @ORM\Column(type="decimal", precision=18, scale=8)
     */
    private $ask;

    /**
     * @ORM\Column(type="integer")
     */
    private $lot_size;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $dateUpdated;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;
        return $this;
    }

    public function getBid()
    {
        return $this->bid;
    }

    public function setBid(string $bid): self
    {
        $this->bid = $bid;

        return $this;
    }

    public function getAsk(): ?string
    {
        return $this->ask;
    }

    public function setAsk(string $ask): self
    {
        $this->ask = $ask;

        return $this;
    }

    public function getLotSize(): ?int
    {
        return $this->lot_size;
    }

    public function setLotSize(int $lot_size = 10): self
    {
        $this->lot_size = $lot_size;

        return $this;
    }

    public function getDateUpdated(): ?\DateTimeImmutable
    {
        return $this->dateUpdated;
    }

    public function setDateUpdated(\DateTimeImmutable $dateUpdated): self
    {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }
}
