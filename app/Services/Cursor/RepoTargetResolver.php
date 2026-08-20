<?php

namespace App\Services\Cursor;

class RepoTargetResolver
{
    /**
     * Resolve a local repository target from an IMS issue URL.
     *
     * @return array{
     *     key: string,
     *     name: string,
     *     path: string,
     *     matched_host: string|null,
     *     match_type: string
     * }|null
     */
    public function resolve(?string $url): ?array
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $host = $this->extractHost($url);
        $normalizedUrl = mb_strtolower($url);

        foreach ((array) config('cursor.repos', []) as $repo) {
            if (! is_array($repo) || empty($repo['key'])) {
                continue;
            }

            $hosts = array_map('mb_strtolower', (array) ($repo['hosts'] ?? []));
            if ($host !== null && in_array($host, $hosts, true)) {
                return $this->normalizeRepo($repo, $host, 'host');
            }

            foreach ((array) ($repo['url_contains'] ?? []) as $needle) {
                $needle = mb_strtolower(trim((string) $needle));
                if ($needle !== '' && str_contains($normalizedUrl, $needle)) {
                    return $this->normalizeRepo($repo, $host, 'contains');
                }
            }
        }

        return null;
    }

    /**
     * @return list<array{key: string, name: string, path: string, hosts: list<string>}>
     */
    public function listRepos(): array
    {
        $items = [];

        foreach ((array) config('cursor.repos', []) as $repo) {
            if (! is_array($repo) || empty($repo['key'])) {
                continue;
            }

            $normalized = $this->normalizeRepo($repo, null, 'list');
            $items[] = [
                'key' => $normalized['key'],
                'name' => $normalized['name'],
                'path' => $normalized['path'],
                'hosts' => array_values(array_map('strval', (array) ($repo['hosts'] ?? []))),
            ];
        }

        return $items;
    }

    public function findByKey(string $key): ?array
    {
        foreach ((array) config('cursor.repos', []) as $repo) {
            if (! is_array($repo)) {
                continue;
            }

            if (($repo['key'] ?? null) === $key) {
                return $this->normalizeRepo($repo, null, 'key');
            }
        }

        return null;
    }

    private function extractHost(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            // Allow bare domains such as "gateway.co.th/path"
            if (preg_match('#^([a-z0-9.-]+\.[a-z]{2,})(?:/|$)#i', $url, $matches) === 1) {
                $host = $matches[1];
            } else {
                return null;
            }
        }

        return mb_strtolower($host);
    }

    /**
     * @param  array<string, mixed>  $repo
     * @return array{key: string, name: string, path: string, matched_host: string|null, match_type: string}
     */
    private function normalizeRepo(array $repo, ?string $matchedHost, string $matchType): array
    {
        $path = (string) ($repo['path'] ?? $repo['key']);
        if (! str_starts_with($path, DIRECTORY_SEPARATOR) && ! preg_match('#^[A-Za-z]:[\\\\/]#', $path)) {
            $root = rtrim((string) config('cursor.repos_root', ''), DIRECTORY_SEPARATOR);
            $path = $root === '' ? $path : $root.DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
        }

        return [
            'key' => (string) $repo['key'],
            'name' => (string) ($repo['name'] ?? $repo['key']),
            'path' => $path,
            'matched_host' => $matchedHost,
            'match_type' => $matchType,
        ];
    }
}
