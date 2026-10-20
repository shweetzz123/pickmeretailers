<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $search = trim($validated['q'] ?? '');

        $users = User::query()
            ->withCount('orders')
            ->when($search !== '', function ($query) use ($search) {
                $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
                $query->where(function ($q) use ($escaped) {
                    $q->where('name', 'LIKE', '%'.$escaped.'%')
                        ->orWhere('email', 'LIKE', '%'.$escaped.'%');
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->appends(['q' => $search]);

        $stats = [
            'total' => User::count(),
            'customers' => User::customers()->count(),
            'owners' => User::where('user_type', 'owner')->count(),
            'clerks' => User::where('user_type', 'clerk')->count(),
        ];

        return view('admin.users', compact('users', 'stats', 'search'));
    }

    /**
     * Promote a customer to clerk, or demote a clerk back to customer.
     * Owner accounts can never be changed.
     */
    public function updateRole(Request $request, User $user)
    {
        if ($user->isOwner()) {
            return redirect()->back()->withErrors(['role' => 'The store owner\'s role cannot be changed.']);
        }

        $newType = $user->isClerk() ? 'user' : 'clerk';

        $user->forceFill(['user_type' => $newType])->save();

        return redirect()->back()->with(
            'success',
            $newType === 'clerk'
                ? "{$user->name} is now a clerk."
                : "{$user->name} is no longer a clerk."
        );
    }
}
