<?php

namespace App\Events;

use App\Enums\Status;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssueMoved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Issue $issue,
        public Status $from,
        public Status $to,
        public ?User $actor,
    ) {}
}
