<?php

use App\Models\User;

it('creates an owner with every discipline', function (): void {
    $this->artisan('rgoli:create-owner', ['email' => 'Boss@RGOLI.ge', '--name' => 'Boss', '--password' => 'secret123'])
        ->assertSuccessful();

    $user = User::query()->where('email', 'boss@rgoli.ge')->firstOrFail();
    expect($user->isOwner())->toBeTrue()
        ->and($user->disciplineValues())->toHaveCount(4);
});

it('rejects a short password', function (): void {
    $this->artisan('rgoli:create-owner', ['email' => 'boss@rgoli.ge', '--password' => 'short'])->assertFailed();
});
