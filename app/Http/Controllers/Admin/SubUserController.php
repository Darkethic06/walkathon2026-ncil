<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalkathonRegistration;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * SUPER-ADMIN only: create and manage event-day volunteer accounts.
 */
class SubUserController extends Controller
{
    public function index()
    {
        $subUsers = User::role(Roles::SUB_USER)
            ->orderBy('first_name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->full_name,
                'email' => $u->email,
                'phone' => $u->phone,
                'active' => (int) $u->active === 1,
                'checkIns' => WalkathonRegistration::where('attended_by', $u->id)->count(),
            ]);

        return Inertia::render('Admin/SubUsers/Index', ['subUsers' => $subUsers]);
    }

    public function create()
    {
        return Inertia::render('Admin/SubUsers/Form', ['subUser' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $user = User::create($data + ['active' => $request->boolean('active')]);
        $user->assignRole(Roles::SUB_USER);

        return redirect()->route('admin.subusers.index')
            ->with('success', "Sub-user {$user->full_name} created.");
    }

    public function edit(User $subUser)
    {
        $this->ensureSubUser($subUser);

        return Inertia::render('Admin/SubUsers/Form', [
            'subUser' => [
                'id' => $subUser->id,
                'first_name' => $subUser->first_name,
                'last_name' => $subUser->last_name,
                'email' => $subUser->email,
                'phone' => $subUser->phone,
                'active' => (int) $subUser->active === 1,
            ],
        ]);
    }

    public function update(Request $request, User $subUser)
    {
        $this->ensureSubUser($subUser);

        $data = $this->validated($request, $subUser);
        if (empty($data['password'])) {
            unset($data['password']); // blank = keep the current password
        }

        $subUser->update($data + ['active' => $request->boolean('active')]);

        return redirect()->route('admin.subusers.index')
            ->with('success', "Sub-user {$subUser->full_name} updated.");
    }

    public function destroy(User $subUser)
    {
        $this->ensureSubUser($subUser);

        // Their past check-ins stay; "attended by" just becomes empty.
        $name = $subUser->full_name;
        $subUser->delete();

        return redirect()->route('admin.subusers.index')
            ->with('success', "Sub-user {$name} deleted.");
    }

    protected function validated(Request $request, ?User $existing = null): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($existing?->id)],
            'phone' => ['nullable', 'regex:/^[6-9]\d{9}$/', Rule::unique('users', 'phone')->ignore($existing?->id)],
            'password' => [$existing ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ], [
            'phone.regex' => 'Enter a 10 digit mobile number.',
        ]);
    }

    /** These routes must never touch an admin account. */
    protected function ensureSubUser(User $user): void
    {
        abort_unless($user->hasRole(Roles::SUB_USER), 404);
    }
}
