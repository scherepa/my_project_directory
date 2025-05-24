<?php

namespace App\Entity;

use App\Repository\TradeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TradeRepository::class)
 */
class Trade
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $userID;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $agentID;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false,name="userID",referencedColumnName="id")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=true,name="agentID",referencedColumnName="id")
     */
    private $agent;

    /**
     * @ORM\Column(type="integer")
     */
    private $tradeSize;

    /**
     * @ORM\Column(type="integer")
     */
    private $lot_count;

    /**
     * @ORM\Column(type="decimal", precision=20, scale=8)
     */
    private $pnl;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=8)
     */
    private $payout;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=8)
     */
    private $usedMargin;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=8)
     */
    private $entryRate;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=8)
     */
    private $closeRate;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $dateCreated;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $dateClosed;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $position;

    /**
     * @ORM\Column(type="integer")
     */
    private $stop_loss;

    /**
     * @ORM\Column(type="integer")
     */
    private $take_profit;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserID(): ?int
    {
        return $this->userID;
    }

    public function setUserID(int $userID): self
    {
        $this->userID = $userID;

        return $this;
    }

    public function getAgentID(): ?int
    {
        return $this->agentID;
    }

    public function setAgentID(?int $agentID): self
    {
        $this->agentID = $agentID;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getAgent(): ?User
    {
        return $this->agent;
    }

    public function setAgent(?User $agent): self
    {
        $this->agent = $agent;

        return $this;
    }

    public function getTradeSize(): ?int
    {
        return $this->tradeSize;
    }

    public function setTradeSize(int $tradeSize): self
    {
        $this->tradeSize = $tradeSize;

        return $this;
    }

    public function getLotCount(): ?int
    {
        return $this->lot_count;
    }

    public function setLotCount(int $lot_count): self
    {
        $this->lot_count = $lot_count;

        return $this;
    }

    public function getPnl(): ?string
    {
        return $this->pnl;
    }

    public function setPnl(string $pnl): self
    {
        $this->pnl = $pnl;

        return $this;
    }

    public function getPayout(): ?string
    {
        return $this->payout;
    }

    public function setPayout(string $payout): self
    {
        $this->payout = $payout;

        return $this;
    }

    public function getUsedMargin(): ?string
    {
        return $this->usedMargin;
    }

    public function setUsedMargin(string $usedMargin): self
    {
        $this->usedMargin = $usedMargin;

        return $this;
    }

    public function getEntryRate(): ?string
    {
        return $this->entryRate;
    }

    public function setEntryRate(string $entryRate): self
    {
        $this->entryRate = $entryRate;

        return $this;
    }

    public function getCloseRate(): ?string
    {
        return $this->closeRate;
    }

    public function setCloseRate(string $closeRate): self
    {
        $this->closeRate = $closeRate;

        return $this;
    }

    public function getDateCreated(): ?\DateTimeImmutable
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeImmutable $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDateClosed(): ?\DateTimeImmutable
    {
        return $this->dateClosed;
    }

    public function setDateClosed(\DateTimeImmutable $dateClosed): self
    {
        $this->dateClosed = $dateClosed;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(string $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getStopLoss(): ?int
    {
        return $this->stop_loss;
    }

    public function setStopLoss(int $stop_loss): self
    {
        $this->stop_loss = $stop_loss;

        return $this;
    }

    public function getTakeProfit(): ?int
    {
        return $this->take_profit;
    }

    public function setTakeProfit(int $take_profit): self
    {
        $this->take_profit = $take_profit;

        return $this;
    }
}
