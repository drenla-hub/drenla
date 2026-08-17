<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Support\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $roles = Role::withCount('users')->orderBy('sort_order')->orderBy('label')->get();

        return view('admin.roles.index', [
            'roles' => $roles,
            'permissionCatalog' => Permission::catalog(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.roles.form', [
            'role' => new Role,
            'permissionCatalog' => Permission::catalog(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = $this->uniqueSlug($data['label']);
        $data['is_system'] = false;

        $role = Role::create($data);

        return redirect()->route('admin.roles.edit', $role)->with('status', 'Role created.');
    }

    public function edit(Request $request, Role $role): View
    {
        return view('admin.roles.form', [
            'role' => $role,
            'permissionCatalog' => Permission::catalog(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $role->update($this->validatedData($request));

        return redirect()->route('admin.roles.edit', $role)->with('status', 'Role updated.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->is_system, 422, 'System roles can be relabeled but not deleted.');
        abort_if($role->users()->exists(), 422, 'Reassign staff off this role before deleting it.');

        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', 'Role deleted.');
    }

    private function validatedData(Request $request): array
    {
        $catalogKeys = array_keys(Permission::catalog());

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:'.implode(',', $catalogKeys)],
        ]);

        $data['permissions'] = array_values($request->input('permissions', []));

        return $data;
    }

    private function uniqueSlug(string $label): string
    {
        $base = Str::slug($label, '_');
        $slug = $base;
        $suffix = 1;

        while (Role::where('slug', $slug)->exists()) {
            $slug = $base.'_'.(++$suffix);
        }

        return $slug;
    }
}
