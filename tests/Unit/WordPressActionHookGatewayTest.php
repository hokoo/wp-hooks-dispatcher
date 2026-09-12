<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Tests\Unit;

use iTRON\wpHooksDispatcher\WordPressActionHookGateway;
use LogicException;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class WordPressActionHookGatewayTest extends TestCase
{
    public function testAddActionFailsWhenWordPressFunctionIsUnavailable(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'WordPress add_action() is not available.'
        );

        (new WordPressActionHookGateway())->addAction(
            'example',
            static function (): void {
            },
            10,
            1
        );
    }

    public function testRemoveActionFailsWhenWordPressFunctionIsUnavailable(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'WordPress remove_action() is not available.'
        );

        (new WordPressActionHookGateway())->removeAction(
            'example',
            static function (): void {
            },
            10
        );
    }
}
