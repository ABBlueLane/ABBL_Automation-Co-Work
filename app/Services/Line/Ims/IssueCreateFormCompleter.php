<?php

namespace App\Services\Line\Ims;

class IssueCreateFormCompleter
{
    /**
     * @param  array<string, mixed>  $state
     * @return list<string>
     */
    public function missingFields(array $state): array
    {
        $missing = [];

        if (trim((string) ($state['title'] ?? '')) === '') {
            $missing[] = 'title';
        }

        $hasUrl = $this->hasValidUrl($state);
        $noUrl = (bool) ($state['no_url'] ?? false);

        if (! $hasUrl && ! $noUrl) {
            $missing[] = 'url_or_no_url';
        }

        return $missing;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function isComplete(array $state): bool
    {
        return $this->missingFields($state) === [];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function applyMissingFields(array $state): array
    {
        $state['missing_fields'] = $this->missingFields($state);

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function hasValidUrl(array $state): bool
    {
        $url = trim((string) ($state['url'] ?? ''));

        if ($url === '') {
            return false;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }
}
