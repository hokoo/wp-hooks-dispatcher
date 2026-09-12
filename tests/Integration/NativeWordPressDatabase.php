<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Tests\Integration;

final class NativeWordPressDatabase
{
    // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

    public string $prefix;

    private int $blogId;

    public function __construct(
        private string $basePrefix = 'wp_',
        int $blogId = 1
    ) {
        $this->blogId = $blogId;
        $this->prefix = $this->get_blog_prefix();
    }

    public function set_blog_id(int $blogId): int
    {
        $previousBlogId = $this->blogId;
        $this->blogId = $blogId;
        $this->prefix = $this->get_blog_prefix();

        return $previousBlogId;
    }

    public function get_blog_prefix(?int $blogId = null): string
    {
        $blogId ??= $this->blogId;

        if ($blogId === 1) {
            return $this->basePrefix;
        }

        return $this->basePrefix . $blogId . '_';
    }
}
