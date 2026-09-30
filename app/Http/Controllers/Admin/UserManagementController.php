<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | USERS LIST
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        // Removed Firebase sync on page load for performance
        // \App\Services\FirebaseService::syncFirebaseUsersToLocal();

        $status = $request->query('status', 'active');
        $perPage = $request->query('per_page', 'all');

        /*
        |--------------------------------------------------------------------------
        | VALID STATUS
        |--------------------------------------------------------------------------
        */

        if (!in_array($status, ['active', 'trashed', 'all'])) {
            $status = 'active';
        }

        /*
        |--------------------------------------------------------------------------
        | USERS QUERY
        |--------------------------------------------------------------------------
        */

        $query = User::query();

        if ($status === 'trashed') {

            $query->onlyTrashed();

        } elseif ($status === 'all') {

            $query->withTrashed();

        } else {

            // Explicitly active users
            $query->whereNull('deleted_at');
        }

        /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        */

        if ($perPage === 'all') {

            $users = $query
                ->orderByDesc('id')
                ->paginate(1000)
                ->withQueryString();

        } else {

            $limit = (int) $perPage;

            if (!in_array($limit, [5, 10, 25, 50, 100])) {
                $limit = 10;
            }

            $users = $query
                ->orderByDesc('id')
                ->paginate($limit)
                ->withQueryString();
        }

        /*
        |--------------------------------------------------------------------------
        | COUNTS
        |--------------------------------------------------------------------------
        */

        $counts = [
            'active' => User::whereNull('deleted_at')->count(),

            'trashed' => User::onlyTrashed()->count(),

            'all' => User::withTrashed()->count(),
        ];

        return view(
            'admin.settings.users.index',
            compact(
                'users',
                'status',
                'perPage',
                'counts'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE USER PAGE
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $roles = \App\Models\Role::orderBy('name')->get();
        return view('admin.settings.users.create', compact('roles'));
    }


    /*
    |--------------------------------------------------------------------------
    | STORE USER
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'role' => [
                'required',
                Rule::exists('roles', 'slug'),
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);


        $user = DB::transaction(function () use ($validated) {

            return User::create([
                'name' => trim($validated['name']),

                'email' => strtolower(
                    trim($validated['email'])
                ),

                'role' => $validated['role'],

                'password' => $validated['password'],
            ]);
        });


        return redirect()
            ->route('admin.settings.users.index')
            ->with(
                'message',
                "User '{$user->name}' created successfully."
            );
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT USER
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        $user = User::withTrashed()
            ->findOrFail($id);

        $roles = \App\Models\Role::orderBy('name')->get();

        return view(
            'admin.settings.users.edit',
            compact('user', 'roles')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE USER
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        $user = User::withTrashed()
            ->findOrFail($id);


        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',

                Rule::unique('users', 'email')
                    ->ignore($user->id),
            ],

            'role' => [
                'required',
                Rule::exists('roles', 'slug'),
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);


        DB::transaction(function () use (
            $user,
            $validated
        ) {

            $user->name = trim(
                $validated['name']
            );

            $user->email = strtolower(
                trim($validated['email'])
            );

            $user->role = $validated['role'];


            if (!empty($validated['password'])) {

                $user->password =
                    $validated['password'];
            }


            $user->save();
        });


        return redirect()
            ->route('admin.settings.users.index')
            ->with(
                'message',
                "User '{$user->name}' updated successfully."
            );
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE USER
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $user = User::findOrFail($id);


        if (
            auth()->check()
            &&
            auth()->id() === $user->id
        ) {

            return back()->with(
                'error',
                'You cannot delete your own logged-in account.'
            );
        }


        DB::transaction(function () use ($user) {

            $user->delete();
        });


        return redirect()
            ->route('admin.settings.users.index')
            ->with(
                'message',
                "User '{$user->name}' moved to trash successfully."
            );
    }


    /*
    |--------------------------------------------------------------------------
    | RESTORE USER
    |--------------------------------------------------------------------------
    */

    public function restore($id)
    {
        $user = User::onlyTrashed()
            ->findOrFail($id);


        DB::transaction(function () use ($user) {

            $user->restore();
        });


        return redirect()
            ->route(
                'admin.settings.users.index',
                [
                    'status' => 'active',
                ]
            )
            ->with(
                'message',
                "User '{$user->name}' restored successfully."
            );
    }


    /*
    |--------------------------------------------------------------------------
    | PERMANENT DELETE
    |--------------------------------------------------------------------------
    */

    public function forceDelete($id)
    {
        $user = User::onlyTrashed()
            ->findOrFail($id);


        if (
            auth()->check()
            &&
            auth()->id() === $user->id
        ) {

            return back()->with(
                'error',
                'You cannot permanently delete your own account.'
            );
        }


        $name = $user->name;


        DB::transaction(function () use ($user) {

            $user->forceDelete();
        });


        return redirect()
            ->route(
                'admin.settings.users.index',
                [
                    'status' => 'trashed',
                ]
            )
            ->with(
                'message',
                "User '{$name}' permanently deleted."
            );
    }
}