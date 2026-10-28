<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use App\Repositories\Contracts\FileRepositoryInterface;
use App\Repositories\Contracts\IssueRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;

/**
 * Global search over visible projects, tasks and files, grouped. Each group requires its view permission.
 */
class SearchService
{
    private const LIMIT = 6;

    public function __construct(
        private ProjectRepositoryInterface $projects,
        private IssueRepositoryInterface $issues,
        private FileRepositoryInterface $files,
    ) {}

    /**
     * @return array{projects: list<array<string, mixed>>, issues: list<array<string, mixed>>, files: list<array<string, mixed>>}
     */
    public function search(User $user, string $term): array
    {
        $term = trim($term);
        $empty = ['projects' => [], 'issues' => [], 'files' => []];
        if (mb_strlen($term) < 2) {
            return $empty;
        }

        return [
            'projects' => $user->can('projects.view')
                ? array_values($this->projects->search($user, $term, self::LIMIT)->map(fn (Project $p): array => [
                    'id' => $p->id, 'key' => $p->key, 'name' => $p->name, 'client_name' => $p->client_name, 'status' => $p->status->value,
                ])->all())
                : [],
            'issues' => $user->can('issues.view')
                ? array_values($this->issues->search($user, $term, self::LIMIT)->map(fn (Issue $i): array => [
                    'id' => $i->id, 'key' => $i->key(), 'title' => $i->title, 'status' => $i->status->value, 'project' => $i->project->name,
                ])->all())
                : [],
            'files' => $user->can('files.view')
                ? array_values($this->files->search($user, $term, self::LIMIT)->map(fn (ProjectFile $f): array => [
                    'id' => $f->id, 'name' => $f->original_name, 'type' => $f->type->value, 'project' => $f->project->name,
                ])->all())
                : [],
        ];
    }
}
