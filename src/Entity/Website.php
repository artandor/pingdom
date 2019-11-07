<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WebsiteRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Website
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $domain;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $responseTime;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $mailingList = [];

    /**
     * @ORM\Column(type="integer")
     */
    private $consecutiveFailAmount = 0;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastAlertSent;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastOkStatus;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $redirectTo;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $redirectionOk;
    
    public function __toString(): ?string
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
        return $this->created_at;
    }

    /**
     * Gets triggered only on insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->created_at = new \DateTime("now");
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    /**
     * Gets triggered every time on update
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->updated_at = new \DateTime("now");
        if($this->redirectionOk) {
            $this->consecutiveFailAmount = 0;
        } else if ($this->status == 200 && !$this->redirectTo){
            
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

    public function getResponseTime(): ?float
    {
        return $this->responseTime;
    }

    public function setResponseTime(?float $responseTime): self
    {
        $this->responseTime = $responseTime;

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
