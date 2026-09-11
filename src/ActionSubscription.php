<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher;

interface ActionSubscription
{
    public function unsubscribe(): void;
}
