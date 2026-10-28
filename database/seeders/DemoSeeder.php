<?php

namespace Database\Seeders;

use App\Enums\Discipline;
use App\Enums\FileType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Priority;
use App\Enums\SprintState;
use App\Enums\Status;
use App\Models\ClientPayment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectPhase;
use App\Models\Role;
use App\Models\SalaryRequest;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo workspace from the product brief. All dates are relative to today.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $today = now()->startOfDay();
        $d = fn (int $days): string => $today->copy()->addDays($days)->toDateString();

        // ── Users ───────────────────────────────────────────────────────
        $admin = $this->user('Admin', 'admin@rgoli.ge', 'admin123', 'owner', 'Studio Owner', Discipline::values());
        $designer = $this->user('Interior Designer', 'designer@rgoli.ge', 'designer123', 'interior_designer', 'Interior Designer', [Discipline::Interior->value]);
        $finance = $this->user('Finance Manager', 'finance@rgoli.ge', 'finance123', 'finance_manager', 'Finance Manager', [Discipline::Finance->value]);
        $manager = $this->user('Project Manager', 'manager@rgoli.ge', 'manager123', 'project_manager', 'Project Manager', [Discipline::Architecture->value, Discipline::Construction->value]);

        // ── Projects ────────────────────────────────────────────────────
        $p1 = $this->project([
            'key' => 'URK',
            'name' => 'ინდივიდუალური საცხოვრებელი სახლი',
            'client_name' => 'შოთა ფალავანდიშვილი',
            'client_phone' => '+995 555 12 34 56',
            'address' => 'ქალაქი ბათუმი, ურეხის დასახლება #35',
            'discipline' => Discipline::Architecture,
            'status' => Status::Progress,
            'budget' => '13800.00',
            'discount_price' => '9960.00',
            'lat' => 41.651,
            'lng' => 41.6367,
            'progress' => 42,
            'start_date' => $d(-10),
            'end_date' => $d(65),
            'description' => 'ორსართულიანი ინდივიდუალური საცხოვრებელი სახლის არქიტექტურული პროექტი ურეხის დასახლებაში.',
            'created_by' => $admin->id,
        ], [$admin, $designer, $manager], [
            ['ფაზა 1 — საწყისი კვლევა და კონცეფცია', $d(-10), $d(5), Status::Done],
            ['ფაზა 2 — არქიტექტურული პროექტირება', $d(5), $d(40), Status::Progress],
            ['ფაზა 3 — სამუშაო ნახაზები', $d(40), $d(65), Status::Todo],
        ]);

        $p2 = $this->project([
            'key' => 'OFF',
            'name' => 'RGOLI Office Interior',
            'client_name' => 'RGOLI',
            'address' => 'Batumi, Rustaveli Ave',
            'discipline' => Discipline::Interior,
            'status' => Status::Review,
            'budget' => '6200.00',
            'discount_price' => '6200.00',
            'lat' => 41.6461,
            'lng' => 41.6405,
            'progress' => 68,
            'start_date' => $d(-25),
            'end_date' => $d(25),
            'description' => 'Interior design of the RGOLI studio office.',
            'created_by' => $admin->id,
        ], [$admin, $designer], [
            ['Concept & moodboard', $d(-25), $d(-5), Status::Done],
            ['Interior design review', $d(-5), $d(10), Status::Review],
            ['Furniture & procurement', $d(10), $d(25), Status::Todo],
        ]);

        $p3 = $this->project([
            'key' => 'SUP',
            'name' => 'Construction Supervision',
            'client_name' => 'Private Client',
            'address' => 'Batumi Boulevard area',
            'discipline' => Discipline::Construction,
            'status' => Status::Todo,
            'budget' => '4200.00',
            'discount_price' => '4200.00',
            'lat' => 41.6508,
            'lng' => 41.6253,
            'progress' => 12,
            'start_date' => $d(5),
            'end_date' => $d(80),
            'description' => 'Technical supervision of the construction works.',
            'created_by' => $admin->id,
        ], [$admin, $manager], [
            ['Preparation', $d(5), $d(20), Status::Todo],
            ['Site supervision', $d(20), $d(80), Status::Todo],
        ]);

        // ── Client payments ─────────────────────────────────────────────
        $this->payment($p1, '2000.00', $d(-9), $finance);
        $this->payment($p1, '1500.00', $d(-2), $finance);
        $this->payment($p2, '1200.00', $d(-20), $finance);

        // ── Tasks ───────────────────────────────────────────────────────
        $t1 = $this->issue($p1, 1, 'მუნიციპალიტეტთან კონსულტაცია', Status::Done, Priority::High, $manager, $d(-2), 3, $admin);
        $t2 = $this->issue($p1, 2, 'ტოპოგრაფიული გეგმის მომზადება', Status::Progress, Priority::Critical, $manager, $d(4), 8, $admin);
        $t3 = $this->issue($p1, 3, 'ვიზუალიზაცია / რენდერი', Status::Todo, Priority::Medium, $designer, $d(10), 5, $admin);
        $t4 = $this->issue($p2, 1, 'მასალების პალიტრის დამტკიცება', Status::Review, Priority::High, $designer, $d(3), 2, $admin);
        $this->issue($p3, 1, 'საწყისი ინსპექციის სია', Status::Blocked, Priority::Medium, $manager, $d(14), 3, $admin);

        // ── Sprints ─────────────────────────────────────────────────────
        $sprint1 = Sprint::query()->create([
            'name' => 'Sprint 01 — Permit Docs',
            'goal' => 'Permit documentation for the Urekhi house.',
            'start_date' => $d(-7),
            'end_date' => $d(7),
            'state' => SprintState::Active,
            'started_at' => $today->copy()->subDays(7),
        ]);
        $sprint1->issues()->attach([$t1->id => ['added_at' => now()], $t2->id => ['added_at' => now()]]);

        $sprint2 = Sprint::query()->create([
            'name' => 'Sprint 02 — Visual Package',
            'goal' => 'Renders and the material palette.',
            'start_date' => $d(8),
            'end_date' => $d(22),
            'state' => SprintState::Planned,
        ]);
        $sprint2->issues()->attach([$t3->id => ['added_at' => now()], $t4->id => ['added_at' => now()]]);

        // ── Salary requests ─────────────────────────────────────────────
        SalaryRequest::query()->create([
            'project_id' => $p1->id,
            'user_id' => $manager->id,
            'title' => 'ტოპოგრაფიული გეგმა',
            'amount' => '500.00',
            'status' => PaymentStatus::Pending,
        ]);
        SalaryRequest::query()->create([
            'project_id' => $p2->id,
            'user_id' => $designer->id,
            'title' => 'Interior moodboard',
            'amount' => '300.00',
            'status' => PaymentStatus::Approved,
            'decided_by' => $admin->id,
            'decided_at' => $today->copy()->subDays(3),
        ]);

        // ── Files (small placeholders on the private disk) ─────────────
        $this->file($p1, $finance, 'ფინანსური კალკულატორი.pdf', 'application/pdf', FileType::Pdf, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n");
        $this->file($p1, $manager, 'Urekhi site photos.zip', 'application/zip', FileType::Zip, "PK\x05\x06".str_repeat("\0", 18));
        $this->file($p2, $designer, 'moodboard.png', 'image/png', FileType::Image, (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
    }

    /**
     * @param  list<string>  $disciplines
     */
    private function user(string $name, string $email, string $password, string $roleSlug, string $jobTitle, array $disciplines): User
    {
        $user = User::query()->updateOrCreate(['email' => $email], [
            'name' => $name,
            'password' => $password,
            'job_title' => $jobTitle,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->syncRoles([Role::query()->where('slug', $roleSlug)->firstOrFail()]);
        $user->syncDisciplines($disciplines);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<User>  $members
     * @param  list<array{0: string, 1: string, 2: string, 3: Status}>  $phases
     */
    private function project(array $attributes, array $members, array $phases): Project
    {
        $project = Project::query()->create($attributes);
        $project->members()->sync(array_map(fn (User $u): int => $u->id, $members));

        foreach ($phases as $index => [$name, $start, $end, $status]) {
            ProjectPhase::query()->create([
                'project_id' => $project->id,
                'name' => $name,
                'sort_order' => $index + 1,
                'start_date' => $start,
                'end_date' => $end,
                'status' => $status,
            ]);
        }

        return $project;
    }

    private function payment(Project $project, string $amount, string $paidAt, User $recorder): void
    {
        ClientPayment::query()->create([
            'project_id' => $project->id,
            'amount' => $amount,
            'paid_at' => $paidAt,
            'method' => PaymentMethod::BankTransfer,
            'recorded_by' => $recorder->id,
        ]);
    }

    private function issue(Project $project, int $number, string $title, Status $status, Priority $priority, User $assignee, string $due, int $points, User $creator): Issue
    {
        $issue = Issue::query()->create([
            'project_id' => $project->id,
            'number' => $number,
            'title' => $title,
            'status' => $status,
            'priority' => $priority,
            'due_date' => $due,
            'points' => $points,
            'position' => (int) Issue::query()->where('status', $status->value)->count(),
            'created_by' => $creator->id,
        ]);
        $issue->assignees()->sync([$assignee->id]);

        return $issue;
    }

    private function file(Project $project, User $uploader, string $name, string $mime, FileType $type, string $contents): void
    {
        $disk = (string) config('rgoli.uploads.disk');
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $path = 'projects/'.$project->id.'/'.Str::uuid()->toString().'.'.$extension;
        Storage::disk($disk)->put($path, $contents);

        ProjectFile::query()->create([
            'project_id' => $project->id,
            'uploaded_by' => $uploader->id,
            'original_name' => $name,
            'disk' => $disk,
            'path' => $path,
            'mime' => $mime,
            'size_bytes' => strlen($contents),
            'type' => $type,
        ]);
    }
}
