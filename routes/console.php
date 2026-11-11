<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('evently:make-admin', function () {
    $name = text('Administrator name', required: true);
    $email = text('Administrator email', required: true, validate: ['email']);
    $plainPassword = password('Administrator password (minimum 12 characters)', required: true,
        validate: fn (string $value) => strlen($value) >= 12 ? null : 'Use at least 12 characters.');
    $confirmation = password('Confirm password', required: true);

    if (! hash_equals($plainPassword, $confirmation)) {
        $this->error('The passwords did not match. Run the command again.');
        return 1;
    }

    if (User::where('email', $email)->exists() && ! confirm('This account already exists. Promote it to administrator?', false)) {
        $this->info('No changes made.');
        return 0;
    }

    User::updateOrCreate(['email' => $email], [
        'name' => $name,
        'password' => Hash::make($plainPassword),
        'role' => 'admin',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $this->info("Administrator account ready for {$email}.");
    return 0;
})->purpose('Create or promote an Evently administrator account');
