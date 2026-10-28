<?php

namespace App\Actions\Issue;

use App\Models\Issue;
use App\Repositories\Contracts\IssueRepositoryInterface;

class DeleteIssueAction
{
    public function __construct(private IssueRepositoryInterface $issues) {}

    public function handle(Issue $issue): void
    {
        $this->issues->delete($issue);
    }
}
