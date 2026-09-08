<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Models;

class Tool
{
    public function __construct(
        private int $id,
        private string $name,
        private ?string $description,
        private string $slug,
        private string $url,
        private bool $isActive,
        private ?string $deletedAt,
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getUrl(): string
    {
        return $this->url;
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

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function isAvailable(): bool
    {
        return $this->isActive && !$this->isDeleted();
    }
}
