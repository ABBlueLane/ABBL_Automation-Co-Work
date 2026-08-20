<?php

namespace Tests\Unit\Services\Cursor;

use App\Services\Cursor\RepoTargetResolver;
use Tests\TestCase;

class RepoTargetResolverTest extends TestCase
{
    private RepoTargetResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cursor.repos_root', '/var/www/repos');
        config()->set('cursor.repos', [
            [
                'key' => 'AB_Gateway',
                'name' => 'AB Gateway',
                'path' => 'AB_Gateway',
                'hosts' => ['gateway.co.th', 'www.gateway.co.th'],
                'url_contains' => ['gateway.co.th'],
            ],
        ]);

        $this->resolver = new RepoTargetResolver;
    }

    public function test_resolves_gateway_host_to_ab_gateway(): void
    {
        $repo = $this->resolver->resolve('https://gateway.co.th/login');

        $this->assertNotNull($repo);
        $this->assertSame('AB_Gateway', $repo['key']);
        $this->assertSame('/var/www/repos/AB_Gateway', $repo['path']);
        $this->assertSame('gateway.co.th', $repo['matched_host']);
    }

    public function test_resolves_www_gateway_host(): void
    {
        $repo = $this->resolver->resolve('https://www.gateway.co.th/app');

        $this->assertNotNull($repo);
        $this->assertSame('AB_Gateway', $repo['key']);
        $this->assertSame('www.gateway.co.th', $repo['matched_host']);
    }

    public function test_returns_null_when_no_mapping(): void
    {
        $this->assertNull($this->resolver->resolve('https://example.com/x'));
        $this->assertNull($this->resolver->resolve(null));
        $this->assertNull($this->resolver->resolve(''));
    }

    public function test_find_by_key(): void
    {
        $repo = $this->resolver->findByKey('AB_Gateway');

        $this->assertNotNull($repo);
        $this->assertSame('AB Gateway', $repo['name']);
    }
}
