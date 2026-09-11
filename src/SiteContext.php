<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher;

final class SiteContext
{
    public function __construct(
        private int $blogId,
        private string $databasePrefix
    ) {
    }

    public function blogId(): int
    {
        return $this->blogId;
    }

    public function databasePrefix(): string
    {
        return $this->databasePrefix;
    }

    public function equals(self $other): bool
    {
        return $this->blogId === $other->blogId
            && $this->databasePrefix === $other->databasePrefix;
    }
}
