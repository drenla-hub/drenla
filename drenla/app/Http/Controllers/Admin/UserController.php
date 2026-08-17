<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::withCount([
            'assignedProjectTasks as total_tasks_count',
            'assignedProjectTasks as done_tasks_count' => fn ($query) => $query->where('status', 'done'),
            'assignedProjectTasks as active_tasks_count' => fn ($query) => $query->whereIn('status', ['todo', 'in_progress', 'review', 'blocked']),
        ])
            ->orderByRaw("case role when 'super_admin' then 1 when 'commercial_owner' then 2 when 'client_lead' then 3 when 'manager' then 4 else 5 end")
            ->orderBy('name')
            ->get();

        return view('admin.users.index', [
            'users' => $users,
            'roleOptions' => User::roleOptions(),
            'roleDescriptions' => User::roleDescriptions(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.users.form', [
            'staffUser' => new User,
            'roleOptions' => User::roleOptions(),
            'roleDescriptions' => User::roleDescriptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::create($this->validatedData($request));

        return redirect()->route('admin.users.index')->with('status', 'Staff user created.');
    }

    public function edit(Request $request, User $user): View
    {
        return view('admin.users.form', [
            'staffUser' => $user,
            'roleOptions' => User::roleOptions(),
            'roleDescriptions' => User::roleDescriptions(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validatedData($request, $user);
        if (! array_key_exists('password', $data)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('status', 'Staff user updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'You cannot delete your own staff account.');

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'Staff user deleted.');
    }

    private function validatedData(Request $request, ?User $user = null): array
    {
        $passwordRules = $user?->exists
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::in(array_keys(User::roleOptions()))],
            'password' => $passwordRules,
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }
}
