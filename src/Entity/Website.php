<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PrePersist;

#[ORM\Entity(repositoryClass: 'App\Repository\WebsiteRepository')]
#[ORM\HasLifecycleCallbacks]
class Website
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $domain;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $status;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $responseTime;

    #[ORM\Column(type: 'simple_array', nullable: true)]
    private array $mailingList = [];

    #[ORM\Column(type: 'integer')]
    private int $consecutiveFailAmount = 0;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastAlertSent;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastOkStatus;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $redirectTo = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $redirectionOk;

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    #[Prepersist]
    public function onPrePersist()
    {
        $this->createdAt = new \DateTime("now");
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $date): self
    {
        $this->updatedAt = $date;

        return $this;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate()
    {
        $this->updatedAt = new \DateTime("now");
        if($this->redirectionOk && $this->status == 200) {
            $this->consecutiveFailAmount = 0;
            $this->setLastOkStatus(new \DateTime());
        } else {
            $this->consecutiveFailAmount++;
        }
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getResponseTime(): ?int
    {
        return $this->responseTime;
    }

    public function setResponseTime(?float $responseTime): self
    {
        // Set the response time in milliseconds
        $this->responseTime = intval($responseTime * 1000);

        return $this;
    }

    public function getMailingList(): ?array
    {
        return $this->mailingList;
    }

    public function setMailingList(?array $mailingList): self
    {
        $this->mailingList = $mailingList;

        return $this;
    }

    public function getConsecutiveFailAmount(): ?int
    {
        return $this->consecutiveFailAmount;
    }

    public function setConsecutiveFailAmount(int $consecutiveFailAmount): self
    {
        $this->consecutiveFailAmount = $consecutiveFailAmount;

        return $this;
    }

    public function getLastAlertSent(): ?\DateTimeInterface
    {
        return $this->lastAlertSent;
    }

    public function setLastAlertSent(?\DateTimeInterface $lastAlertSent): self
    {
        $this->lastAlertSent = $lastAlertSent;

        return $this;
    }

    public function getLastOkStatus(): ?\DateTimeInterface
    {
        return $this->lastOkStatus;
    }

    public function setLastOkStatus(?\DateTimeInterface $lastOkStatus): self
    {
        $this->lastOkStatus = $lastOkStatus;

        return $this;
    }

    public function getRedirectTo(): ?string
    {
        return $this->redirectTo;
    }

    public function setRedirectTo(?string $redirectTo): self
    {
        $this->redirectTo = $redirectTo;

        return $this;
    }

    public function getRedirectionOk(): ?bool
    {
        return $this->redirectionOk;
    }

    public function setRedirectionOk(?bool $redirectionOk): self
    {
        $this->redirectionOk = $redirectionOk;

        return $this;
    }
}
