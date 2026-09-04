<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Models;

class User
{
    public function __construct(
        private int $id,
        private string $name,
        private string $email,
        private string $passwordHash,
        private string $role,
        private ?string $hotmartBuyerId,
        private ?string $hotmartEmail,
        private bool $isActive,
        private ?string $deletedAt,
        private ?string $lastLoginAt,
        private string $createdAt,
        private string $updatedAt
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getHotmartBuyerId(): ?string
    {
        return $this->hotmartBuyerId;
    }

    public function getHotmartEmail(): ?string
    {
        return $this->hotmartEmail;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function getDeletedAt(): ?string
    {
        return $this->deletedAt;
    }

    public function getLastLoginAt(): ?string
    {
        return $this->lastLoginAt;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function isAluno(): bool
    {
        return $this->role === 'ALUNO';
    }

    public function canLogin(): bool
    {
        return $this->isActive && !$this->isDeleted();
    }
}