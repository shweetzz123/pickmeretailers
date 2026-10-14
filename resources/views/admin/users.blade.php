@extends('admin.maindesign')

@section('page_title', 'Users')
@section('page_header', 'Users')
@section('page_subtitle', 'Customer and administrator accounts.')

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
                        <div class="icon"><i class="fa fa-shield"></i></div><strong>Admins</strong>
                    </div>
                    <div class="number dashtext-4">{{ number_format($stats['admins']) }}</div>
                </div>
                <small class="text-muted">Full access</small>
            </div>
        </div>
    </div>

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
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->name }} @if($user->id === Auth::id())<span class="badge badge-secondary">you</span>@endif</td>
                            <td>{{ $user->email }}</td>
                            <td><span class="badge {{ $user->isAdmin() ? 'badge-dark' : 'badge-light' }}">{{ $user->isAdmin() ? 'Administrator' : 'Customer' }}</span></td>
                            <td class="text-right">{{ number_format($user->orders_count) }}</td>
                            <td>{{ $user->created_at?->format('M d, Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No users match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    </div>
</div>
@endsection
