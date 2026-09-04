<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Models;

class UserCourse
{
    public function __construct(
        private int $id,
        private int $userId,
        private int $courseId,
        private ?string $hotmartTransactionId,
        private string $status,
        private ?string $purchasedAt,
        private ?string $accessExpiresAt,
        private string $createdAt,
        private string $updatedAt
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCourseId(): int
    {
        return $this->courseId;
    }

    public function getHotmartTransactionId(): ?string
    {
        return $this->hotmartTransactionId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPurchasedAt(): ?string
    {
        return $this->purchasedAt;
    }

    public function getAccessExpiresAt(): ?string
    {
        return $this->accessExpiresAt;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function hasAccess(): bool
    {
        return $this->status === 'ACTIVE';
    }
}
