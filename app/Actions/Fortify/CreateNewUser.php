<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\Clinic;
use App\Models\User;
use App\Services\ClinicProvisioner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user for the single clinic.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        if (Clinic::query()->exists() && ! config('dentaflow.allow_public_registration')) {
            abort(403, 'El registro público está cerrado. Solicita al administrador del consultorio que cree tu cuenta.');
        }

        Validator::make($input, [
            ...$this->profileRules(),
            'clinic_name' => ['nullable', 'string', 'max:160'],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $clinic = Clinic::current();
            $isFirstClinicSetup = $clinic === null;
            $normalizedEmail = Str::lower(trim($input['email']));

            if ($clinic === null) {
                $clinicName = isset($input['clinic_name']) && trim($input['clinic_name']) !== ''
                    ? trim($input['clinic_name'])
                    : 'Consultorio Odontológico '.$input['name'];

                $clinic = ClinicProvisioner::createClinicWithDefaults([
                    'name' => $clinicName,
                    'email' => $normalizedEmail,
                ]);
            }

            return User::create([
                'clinic_id' => $clinic->id,
                'name' => $input['name'],
                'email' => $normalizedEmail,
                'role' => $isFirstClinicSetup ? UserRole::AdminConsultorio : UserRole::Odontologo,
                'password' => $input['password'],
                'is_active' => true,
            ]);
        });
    }
}
