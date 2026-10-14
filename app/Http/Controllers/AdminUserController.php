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
            'customers' => User::where('user_type', '!=', 'admin')->count(),
            'admins' => User::where('user_type', 'admin')->count(),
        ];

        return view('admin.users', compact('users', 'stats', 'search'));
    }
}
