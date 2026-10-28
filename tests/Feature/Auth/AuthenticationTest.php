<?php

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => seedDemo());

it('logs in and returns a bearer token with flattened permissions', function (): void {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'finance@rgoli.ge',
        'password' => 'finance123',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['token', 'token_type', 'user' => ['id', 'name', 'email'], 'roles', 'permissions', 'disciplines']])
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.disciplines', ['finance']);

    expect($response->json('data.permissions'))
        ->toContain('finance.view', 'salary.approve')
        ->not->toContain('users.manage');

    $this->withToken((string) $response->json('data.token'))
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.user.email', 'finance@rgoli.ge');
});

it('gives the owner every permission', function (): void {
    $permissions = $this->postJson('/api/v1/auth/login', ['email' => 'admin@rgoli.ge', 'password' => 'admin123'])
        ->json('data.permissions');

    expect($permissions)->toEqualCanonicalizing(PermissionCatalog::all());
});

it('rejects wrong credentials with a translated message', function (string $locale, string $message): void {
    $this->withHeader('Accept-Language', $locale)
        ->postJson('/api/v1/auth/login', ['email' => 'admin@rgoli.ge', 'password' => 'nope'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', $message);
})->with([
    ['ka', 'არასწორი ელ. ფოსტა ან პაროლი'],
    ['en', 'Wrong email or password'],
    ['ru', 'Неверный e-mail или пароль'],
]);

it('does not reveal whether the email exists', function (): void {
    $unknown = $this->postJson('/api/v1/auth/login', ['email' => 'ghost@rgoli.ge', 'password' => 'x'])->json('errors.email.0');
    $wrong = $this->postJson('/api/v1/auth/login', ['email' => 'admin@rgoli.ge', 'password' => 'x'])->json('errors.email.0');

    expect($unknown)->toBe($wrong);
});

it('blocks deactivated users', function (): void {
    User::query()->where('email', 'designer@rgoli.ge')->update(['is_active' => false]);

    $this->postJson('/api/v1/auth/login', ['email' => 'designer@rgoli.ge', 'password' => 'designer123'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('validates the login payload', function (): void {
    $this->postJson('/api/v1/auth/login', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('rate-limits login attempts', function (): void {
    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/auth/login', ['email' => 'admin@rgoli.ge', 'password' => 'wrong'])->assertUnprocessable();
    }

    $this->postJson('/api/v1/auth/login', ['email' => 'admin@rgoli.ge', 'password' => 'wrong'])->assertTooManyRequests();
});

it('revokes only the current token on logout', function (): void {
    $first = $this->postJson('/api/v1/auth/login', ['email' => 'admin@rgoli.ge', 'password' => 'admin123'])->json('data.token');
    $second = $this->postJson('/api/v1/auth/login', ['email' => 'admin@rgoli.ge', 'password' => 'admin123'])->json('data.token');

    $this->withToken((string) $first)->postJson('/api/v1/auth/logout')->assertNoContent();
    app('auth')->forgetGuards();

    $this->withToken((string) $first)->getJson('/api/v1/me')->assertUnauthorized();
    app('auth')->forgetGuards();
    $this->withToken((string) $second)->getJson('/api/v1/me')->assertOk();
});

it('returns 401 with a translated message without a token', function (): void {
    $this->withHeader('Accept-Language', 'en')
        ->getJson('/api/v1/me')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Please sign in.');
});

it('sends a password reset link without revealing unknown emails', function (): void {
    Notification::fake();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'admin@rgoli.ge'])->assertOk();
    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'ghost@rgoli.ge'])->assertOk();

    Notification::assertSentTo(User::query()->where('email', 'admin@rgoli.ge')->firstOrFail(), ResetPassword::class);
    Notification::assertCount(1);
});
