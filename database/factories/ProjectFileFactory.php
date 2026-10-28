<?php

namespace Database\Factories;

use App\Enums\FileType;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProjectFile>
 */
class ProjectFileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'uploaded_by' => User::factory(),
            'original_name' => fake()->word().'.pdf',
            'disk' => 'local',
            'path' => 'projects/'.Str::uuid()->toString().'.pdf',
            'mime' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1000, 5_000_000),
            'type' => FileType::Pdf,
        ];
    }
}
