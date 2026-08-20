<?php

namespace Tests\Unit\Services\Cursor;

use App\Services\Cursor\CursorCliClient;
use Tests\TestCase;

class CursorCliClientTest extends TestCase
{
    public function test_build_invocation_includes_print_and_force(): void
    {
        config()->set('cursor.cli.binary', 'agent');
        config()->set('cursor.cli.force', true);
        config()->set('cursor.cli.output_format', 'text');
        config()->set('cursor.cli.api_key', 'test-key');
        config()->set('cursor.cli.model', '');

        $invocation = (new CursorCliClient)->buildInvocation('fix the bug', '/tmp/repo');

        $this->assertSame('/tmp/repo', $invocation['cwd']);
        $this->assertContains('-p', $invocation['argv']);
        $this->assertContains('--force', $invocation['argv']);
        $this->assertContains('--output-format', $invocation['argv']);
        $this->assertContains('text', $invocation['argv']);
        $this->assertSame('fix the bug', $invocation['argv'][array_key_last($invocation['argv'])]);
        $this->assertSame('test-key', $invocation['env']['CURSOR_API_KEY']);
    }

    public function test_build_invocation_inserts_agent_subcommand_when_binary_is_cursor(): void
    {
        config()->set('cursor.cli.binary', 'cursor');
        config()->set('cursor.cli.force', true);
        config()->set('cursor.cli.output_format', 'text');
        config()->set('cursor.cli.api_key', '');
        config()->set('cursor.cli.model', '');

        $invocation = (new CursorCliClient)->buildInvocation('fix the bug', '/tmp/repo');

        $this->assertContains('agent', $invocation['argv']);
        $this->assertContains('-p', $invocation['argv']);
        $this->assertContains('--force', $invocation['argv']);
        $this->assertContains('--output-format', $invocation['argv']);
        $this->assertContains('text', $invocation['argv']);
    }
}
