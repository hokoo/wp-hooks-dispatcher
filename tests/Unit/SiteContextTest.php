<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Tests\Unit;

use iTRON\wpHooksDispatcher\SiteContext;
use PHPUnit\Framework\TestCase;

final class SiteContextTest extends TestCase
{
    public function testAccessorsReturnExactValues(): void
    {
        $context = new SiteContext(7, 'tenant_07_');

        self::assertSame(7, $context->blogId());
        self::assertSame('tenant_07_', $context->databasePrefix());
    }

    public function testEqualityRequiresTheSameBlogIdAndDatabasePrefix(): void
    {
        $context = new SiteContext(7, 'tenant_07_');

        self::assertTrue($context->equals(new SiteContext(7, 'tenant_07_')));
        self::assertFalse($context->equals(new SiteContext(8, 'tenant_07_')));
        self::assertFalse($context->equals(new SiteContext(7, 'tenant_08_')));
    }
}
