@extends('admin.maindesign')

@section('page_title', 'Users')
@section('page_header', 'Users')
@section('page_subtitle', 'Customer, clerk, and owner accounts.')

@section('users')
<div class="container-fluid">
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="statistic-block block">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-users"></i></div><strong>Total</strong>
                    </div>
                    <div class="number dashtext-1">{{ number_format($stats['total']) }}</div>
                </div>
                <small class="text-muted">All accounts</small>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="statistic-block block">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-shopping-basket"></i></div><strong>Customers</strong>
                    </div>
                    <div class="number dashtext-2">{{ number_format($stats['customers']) }}</div>
                </div>
                <small class="text-muted">Shopper accounts</small>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="statistic-block block">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-shield"></i></div><strong>Staff</strong>
                    </div>
                    <div class="number dashtext-4">{{ number_format($stats['owners'] + $stats['clerks']) }}</div>
                </div>
                <small class="text-muted">{{ $stats['owners'] }} owner(s), {{ $stats['clerks'] }} clerk(s)</small>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            <i class="fa fa-check mr-1"></i> {{ session('success') }}
        </div>
    @endif
    @if($errors->has('role'))
        <div class="alert alert-danger">{{ $errors->first('role') }}</div>
    @endif

    <div class="block">
        <form method="GET" action="{{ route('admin.users') }}" class="form-inline mb-3">
            <div class="form-group mr-2 mb-2">
                <label class="sr-only" for="user-q">Search</label>
                <input type="text" id="user-q" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Name or email">
            </div>
            <button type="submit" class="btn btn-primary btn-sm mb-2 mr-2"><i class="fa fa-filter mr-1"></i> Filter</button>
            <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary btn-sm mb-2">Reset</a>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Account</th>
                        <th class="text-right">Orders</th>
                        <th>Joined</th>
                        @if(auth()->user()?->isOwner())
                        <th class="text-right">Role</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->name }} @if($user->id === Auth::id())<span class="badge badge-secondary">you</span>@endif</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="badge {{ $user->isOwner() ? 'badge-dark' : ($user->isClerk() ? 'badge-info' : 'badge-light') }}">
                                    {{ $user->isOwner() ? 'Owner' : ($user->isClerk() ? 'Clerk' : 'Customer') }}
                                </span>
                            </td>
                            <td class="text-right">{{ number_format($user->orders_count) }}</td>
                            <td>{{ $user->created_at?->format('M d, Y') ?? '—' }}</td>
                            @if(auth()->user()?->isOwner())
                            <td class="text-right">
                                @if(! $user->isOwner() && $user->id !== Auth::id())
                                    <form action="{{ route('admin.users.role', $user) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm {{ $user->isClerk() ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                            onclick="return confirm('{{ $user->isClerk() ? 'Remove clerk access from' : 'Grant clerk access to'}} {{ $user->name }}?')">
                                            {{ $user->isClerk() ? 'Remove Clerk' : 'Make Clerk' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()?->isOwner() ? 6 : 5 }}" class="text-center text-muted py-4">No users match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    </div>
</div>
@endsection
