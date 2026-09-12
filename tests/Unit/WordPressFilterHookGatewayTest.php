<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Tests\Unit;

use iTRON\wpHooksDispatcher\WordPressFilterHookGateway;
use LogicException;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class WordPressFilterHookGatewayTest extends TestCase
{
    public function testAddFilterFailsWhenWordPressFunctionIsUnavailable(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'WordPress add_filter() is not available.'
        );

        (new WordPressFilterHookGateway())->addFilter(
            'example',
            static fn (mixed $value): mixed => $value,
            10,
            1
        );
    }

    public function testRemoveFilterFailsWhenWordPressFunctionIsUnavailable(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'WordPress remove_filter() is not available.'
        );

        (new WordPressFilterHookGateway())->removeFilter(
            'example',
            static fn (mixed $value): mixed => $value,
            10
        );
    }
}
