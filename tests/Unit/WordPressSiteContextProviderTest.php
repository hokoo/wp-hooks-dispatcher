<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Tests\Unit;

use iTRON\wpHooksDispatcher\WordPressSiteContextProvider;
use LogicException;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use stdClass;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class WordPressSiteContextProviderTest extends TestCase
{
    public function testCurrentFailsWhenWordPressFunctionIsUnavailable(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'WordPress get_current_blog_id() is not available.'
        );

        (new WordPressSiteContextProvider())->current();
    }

    public function testCurrentFailsWhenWordPressDatabaseIsUnavailable(): void
    {
        $this->loadWordPressFunctionFixture();
        unset($GLOBALS['wpdb']);

        $this->expectDatabasePrefixException();

        (new WordPressSiteContextProvider())->current();
    }

    public function testCurrentFailsWhenWordPressDatabaseHasNoPrefix(): void
    {
        $this->loadWordPressFunctionFixture();
        $GLOBALS['wpdb'] = new stdClass();

        $this->expectDatabasePrefixException();

        (new WordPressSiteContextProvider())->current();
    }

    public function testCurrentFailsWhenWordPressDatabasePrefixIsNotAString(): void
    {
        $this->loadWordPressFunctionFixture();
        $database = new stdClass();
        $database->prefix = 123;
        $GLOBALS['wpdb'] = $database;

        $this->expectDatabasePrefixException();

        (new WordPressSiteContextProvider())->current();
    }

    public function testCurrentReturnsTheExactWordPressContext(): void
    {
        $this->loadWordPressFunctionFixture();
        $GLOBALS['wp_hooks_dispatcher_unit_blog_id'] = 7;
        $database = new stdClass();
        $database->prefix = 'tenant_07_';
        $GLOBALS['wpdb'] = $database;

        $context = (new WordPressSiteContextProvider())->current();

        self::assertSame(7, $context->blogId());
        self::assertSame('tenant_07_', $context->databasePrefix());
    }

    private function loadWordPressFunctionFixture(): void
    {
        require_once __DIR__ . '/Fixtures/get-current-blog-id.php';
    }

    private function expectDatabasePrefixException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'WordPress database prefix is not available.'
        );
    }
}
