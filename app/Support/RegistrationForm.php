<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * Validation for a walkathon registration, shared by the public form
 * (Frontend\RegistrationController) and the admin "Add registration" form
 * (Admin\RegistrationController), so both always apply the same rules.
 *
 * Field names match the Vue forms (firstName, whatsapp, bloodGroup, ...).
 */
final class RegistrationForm
{
    // Indian mobile number: 10 digits, starting 6-9.
    const MOBILE = 'regex:/^[6-9]\d{9}$/';

    // Youngest age allowed to register.
    const MIN_AGE = 5;

    const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public static function rules(): array
    {
        $latestDob = now()->subYears(self::MIN_AGE)->toDateString();

        return [
            'firstName' => ['required', 'string', 'min:2', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'gender' => ['required', 'in:Male,Female'],
            'email' => ['required', 'email', 'max:255', Rule::unique('walkathon_registrations', 'email')],
            'whatsapp' => ['required', self::MOBILE, Rule::unique('walkathon_registrations', 'whatsapp')],
            'dob' => ['required', 'date', 'before_or_equal:'.$latestDob],
            'bloodGroup' => ['required', Rule::in(self::BLOOD_GROUPS)],
            'address' => ['required', 'string', 'min:6', 'max:1000'],
            'emergency' => ['required', self::MOBILE, 'different:whatsapp'],
            'medical' => ['required', 'string', 'min:2', 'max:2000'],
            'privacy' => ['accepted'],
        ];
    }

    public static function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered.',
            'whatsapp.regex' => 'Enter a 10 digit mobile number.',
            'whatsapp.unique' => 'This WhatsApp number is already registered.',
            'emergency.regex' => 'Enter a 10 digit emergency number.',
            'emergency.different' => 'Emergency contact must be different from your WhatsApp number.',
            'dob.before_or_equal' => 'Participants must be at least '.self::MIN_AGE.' years old.',
            'privacy.accepted' => 'Please accept the Privacy Policy to continue.',
        ];
    }

    /** Validated form data -> WalkathonRegistration attributes. */
    public static function toAttributes(array $data): array
    {
        return [
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'gender' => $data['gender'],
            'email' => $data['email'],
            'whatsapp' => $data['whatsapp'],
            'dob' => $data['dob'],
            'blood_group' => $data['bloodGroup'],
            'address' => $data['address'],
            'emergency_contact' => $data['emergency'],
            'medical_history' => $data['medical'],
            'privacy_accepted_at' => now(),
        ];
    }
}
