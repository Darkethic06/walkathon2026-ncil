<?php

namespace App\Models;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WalkathonRegistration extends Model
{
    protected $fillable = [
        'token',
        'bib_number',
        'first_name',
        'last_name',
        'gender',
        'email',
        'whatsapp',
        'dob',
        'blood_group',
        'address',
        'emergency_contact',
        'medical_history',
        'privacy_accepted_at',
        'mail_sent_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date:Y-m-d',
            'privacy_accepted_at' => 'datetime',
            'mail_sent_at' => 'datetime',
            'attended_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $registration) {
            $registration->token ??= (string) Str::uuid();
        });

        // The bib number comes from the auto-increment id, so it is unique
        // without any locking or retry loop.
        static::created(function (self $registration) {
            $registration->bib_number = self::bibFor($registration->id);
            $registration->saveQuietly();
        });
    }

    /**
     * Bib numbers always start with 4 and are at least 4 digits long:
     * id 1 -> 4001, id 999 -> 4999, id 1000 -> 41000, id 12345 -> 412345.
     * ("4" + the id padded to 3 digits, so two ids can never give the same bib.)
     */
    public static function bibFor(int $id): string
    {
        return '4'.str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    }

    public function attendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attended_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function markAttended(User $by): void
    {
        $this->forceFill(['attended_at' => now(), 'attended_by' => $by->id])->save();
    }

    public function clearAttendance(): void
    {
        $this->forceFill(['attended_at' => null, 'attended_by' => null])->save();
    }

    /**
     * What the admin panel sends to the browser. Sub-users (volunteers) get
     * only what they need to check a walker in; admins ($full) get everything.
     * Fields are left out server-side, not just hidden in the page.
     */
    public function toPanelArray(bool $full): array
    {
        $data = [
            'id' => $this->id,
            'bib' => $this->bib_number,
            'name' => $this->full_name,
            'gender' => $this->gender,
            'age' => $this->dob?->age,
            'blood_group' => $this->blood_group,
            'emergency_contact' => $this->emergency_contact,
            'registered_at' => $this->created_at?->toIso8601String(),
            'attended_at' => $this->attended_at?->toIso8601String(),
            'attended_by' => $this->attendedBy?->full_name,
        ];

        if ($full) {
            $data += [
                'email' => $this->email,
                'whatsapp' => $this->whatsapp,
                'dob' => $this->dob?->toDateString(),
                'address' => $this->address,
                'medical_history' => $this->medical_history,
                'mail_sent_at' => $this->mail_sent_at?->toIso8601String(),
                'created_by' => $this->createdBy?->full_name,
            ];
        }

        return $data;
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /** Text inside the QR code: what a volunteer's scanner shows on the day. */
    public function qrPayload(): string
    {
        return implode("\n", [
            'NephroCare Annual Walkathon 2026',
            "BIB: {$this->bib_number}",
            "Name: {$this->full_name}",
            "Ref: {$this->token}",
        ]);
    }

    /** PNG bytes of the QR code (used for the email attachment). */
    public function qrPng(): string
    {
        return $this->buildQr()->getString();
    }

    /** data:image/png;base64,... (used on the thank-you page). */
    public function qrDataUri(): string
    {
        return $this->buildQr()->getDataUri();
    }

    protected function buildQr()
    {
        return (new Builder(
            writer: new PngWriter,
            data: $this->qrPayload(),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 360,
            margin: 16,
        ))->build();
    }
}
