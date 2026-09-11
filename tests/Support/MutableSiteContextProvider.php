<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Tests\Support;

use iTRON\wpHooksDispatcher\Contracts\SiteContextProvider;
use iTRON\wpHooksDispatcher\SiteContext;

final class MutableSiteContextProvider implements SiteContextProvider
{
    public function __construct(private SiteContext $context)
    {
    }

    public function current(): SiteContext
    {
        return $this->context;
    }

    public function switchTo(SiteContext $context): void
    {
        $this->context = $context;
    }
}
