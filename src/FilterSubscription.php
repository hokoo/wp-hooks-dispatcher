<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher;

interface FilterSubscription
{
    public function unsubscribe(): void;
}
