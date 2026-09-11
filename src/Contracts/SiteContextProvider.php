<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Contracts;

use iTRON\wpHooksDispatcher\SiteContext;

interface SiteContextProvider
{
    public function current(): SiteContext;
}
