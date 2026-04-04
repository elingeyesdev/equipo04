<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('authority:create {carnet} {--name=} {--phone=} {--address=} {--email=} {--password=}', function () {
    $carnet = (string) $this->argument('carnet');

    $name = $this->option('name') ?: $this->ask('Nombre');
    $phone = $this->option('phone') ?: $this->ask('Teléfono');
    $address = $this->option('address') ?: $this->ask('Dirección');
    $email = $this->option('email') ?: $this->ask('Email (opcional)', null);
    $password = $this->option('password') ?: Str::password(12);

    $validator = Validator::make([
        'carnet' => $carnet,
        'name' => $name,
        'phone' => $phone,
        'address' => $address,
        'email' => $email,
        'password' => $password,
    ], [
        'carnet' => ['required', 'string', 'max:20', 'regex:/^\d+$/', 'unique:users,carnet'],
        'name' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'string', 'max:30'],
        'address' => ['required', 'string', 'max:255'],
        'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8'],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    $user = User::create([
        'carnet' => $carnet,
        'name' => $name,
        'phone' => $phone,
        'address' => $address,
        'email' => $email ?: null,
        'password' => Hash::make($password),
        'role' => User::ROLE_AUTHORITY,
    ]);

    $this->info('Authority creada: '.$user->carnet);

    if (! $this->option('password')) {
        $this->line('Password generado: '.$password);
    }

    return 0;
})->purpose('Crear un usuario autoridad (solo admin/sistema)');
