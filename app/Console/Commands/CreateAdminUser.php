<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin 
                            {--name= : Name of the administrator}
                            {--email= : Email address of the administrator}
                            {--password= : Password for the administrator}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new CricBail administrator user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Administrator full name');
        $email = $this->option('email') ?: $this->ask('Administrator email address');
        $password = $this->option('password') ?: $this->secret('Administrator password (min 8 characters)');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->line("  - {$error}");
            }
            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => true,
        ]);

        $this->info("Administrator user [{$user->email}] successfully created with ID #{$user->id}!");
        return self::SUCCESS;
    }
}
