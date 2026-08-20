<?php

namespace App\Services\Cursor;

use App\Models\Issue;

class AutofixPromptBuilder
{
    public function build(Issue $issue, array $repo, ?string $gitBranch = null): string
    {
        $issue->loadMissing('firstComment');

        $title = trim((string) $issue->title);
        $url = trim((string) ($issue->url ?? ''));
        $comment = trim((string) ($issue->firstComment?->comment ?? ''));
        $files = (array) ($issue->firstComment?->files ?? []);
        $priority = (string) $issue->priority;
        $issueNumber = (string) $issue->issue_number;

        $lines = [
            'You are fixing an IMS (Issue Management System) ticket automatically.',
            'Work only inside this repository checkout. Prefer a minimal, correct fix.',
            '',
            'Repository: '.$repo['name'].' ('.$repo['key'].')',
            'Working directory: '.$repo['path'],
        ];

        if ($gitBranch) {
            $lines[] = 'Git branch to use: '.$gitBranch;
            $lines[] = 'If not already on that branch, create/switch to it before editing.';
        }

        $lines[] = '';
        $lines[] = 'IMS issue:';
        $lines[] = '- Number: '.$issueNumber;
        $lines[] = '- Title: '.$title;
        $lines[] = '- Priority: '.$priority;
        $lines[] = '- URL: '.($url !== '' ? $url : '(none)');
        $lines[] = '- Description:';
        $lines[] = $comment !== '' ? $comment : '(empty)';

        if ($files !== []) {
            $lines[] = '- Attached file paths (relative to IMS storage; may be unavailable in this workspace):';
            foreach ($files as $file) {
                $lines[] = '  - '.(string) $file;
            }
        }

        $lines[] = '';
        $lines[] = 'Tasks:';
        $lines[] = '1. Investigate the reported problem using the title, URL, and description.';
        $lines[] = '2. Implement the smallest safe fix in this repository.';
        $lines[] = '3. Run relevant checks/tests if practical.';
        $lines[] = '4. Summarize what you changed and any remaining risks.';
        $lines[] = 'Do not push to remote unless explicitly asked. Commit locally only if the change is clearly correct.';

        return implode("\n", $lines);
    }
}
