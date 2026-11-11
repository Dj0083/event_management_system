<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const ROLES = ['admin', 'staff'];

    public function index()
    {
        return view('users.index', ['users' => User::with('department')->orderBy('name')->paginate(20), 'departments' => Department::orderBy('name')->get(), 'roles' => self::ROLES]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12'], 'role' => ['required', Rule::in(self::ROLES)],
            'department_id' => ['nullable', 'exists:departments,id'], 'phone' => ['nullable', 'string', 'max:40'],
        ]);
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = true;
        User::create($data);

        return back()->with('success', 'User account created.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'role' => ['required', Rule::in(self::ROLES)],
            'department_id' => ['nullable', 'exists:departments,id'], 'phone' => ['nullable', 'string', 'max:40'],
            'is_active' => ['required', 'boolean'],
        ]);
        abort_if($user->is($request->user()) && ! $data['is_active'], 422, 'You cannot deactivate your own account.');
        if ($user->role === 'admin' && ($data['role'] !== 'admin' || ! $data['is_active'])) {
            abort_if(User::where('role', 'admin')->where('is_active', true)->whereKeyNot($user->id)->doesntExist(), 422, 'Keep at least one active administrator account.');
        }
        $user->update($data);

        return back()->with('success', 'User account updated.');
    }
}
