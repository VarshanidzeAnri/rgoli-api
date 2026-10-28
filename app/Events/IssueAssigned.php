<?php

namespace App\Events;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssueAssigned
{
    use Dispatchable, SerializesModels;

    /**
     * @param  list<int>  $userIds  newly added assignees
     */
    public function __construct(public Issue $issue, public array $userIds, public ?User $actor) {}
}
