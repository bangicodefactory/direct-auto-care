<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('Users/Index', [
            'users' => User::with('roles')
                ->orderBy('name')
                ->paginate(15)
                ->through(fn ($u) => [
                    'id'        => $u->id,
                    'name'      => $u->name,
                    'email'     => $u->email,
                    'is_active' => $u->is_active,
                    'roles'     => $u->roles->pluck('name'),
                ]),
        ]);
    }

    public function create()
    {
        $this->authorize('create', User::class);

        return Inertia::render('Users/Create', [
            'roles' => Role::pluck('name'),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create($request->safe()->except('role', 'password_confirmation'));
        $user->assignRole($request->validated('role'));

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        return Inertia::render('Users/Edit', [
            'user' => $user->load('roles'),
            'roles' => Role::pluck('name'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->safe()->except('role'));
        $user->syncRoles([$request->validated('role')]);

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        if ($user->is(auth()->user())) {
            return back()->withErrors(['delete' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur supprimé.');
    }
}
