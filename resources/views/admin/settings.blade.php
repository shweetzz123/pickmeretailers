@extends('admin.maindesign')

@section('page_title', 'GCash Payment Settings')
@section('page_header', 'GCash Payment Settings')
@section('page_subtitle', 'The GCash account details and QR code customers see on the payment page.')

@section('settings')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

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
        <div class="col-lg-7 mb-4">
            <div class="block">
                <div class="title mb-3">
                    <strong>Payment details</strong>
                    <p class="text-muted mb-0">Shown to customers when they pay for an order.</p>
                </div>

                <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label for="gcash_number">GCash mobile number</label>
                        <input type="text" name="gcash_number" id="gcash_number" maxlength="30"
                               value="{{ old('gcash_number', $settings['gcash_number']) }}"
                               class="form-control" placeholder="09XX XXX XXXX">
                    </div>

                    <div class="form-group">
                        <label for="gcash_name">GCash account name</label>
                        <input type="text" name="gcash_name" id="gcash_name" maxlength="255"
                               value="{{ old('gcash_name', $settings['gcash_name']) }}"
                               class="form-control" placeholder="Juan Dela Cruz">
                    </div>

                    <div class="form-group">
                        <label for="gcash_instructions">Instructions</label>
                        <textarea name="gcash_instructions" id="gcash_instructions" rows="4" maxlength="2000"
                                  class="form-control">{{ old('gcash_instructions', $settings['gcash_instructions']) }}</textarea>
                        <small class="form-text text-muted">Leave blank to use the default instructions.</small>
                    </div>

                    <div class="form-group">
                        <label for="gcash_qr">GCash QR code image</label>
                        <input type="file" name="gcash_qr" id="gcash_qr" accept="image/*" class="form-control-file">
                        <small class="form-text text-muted">JPG, PNG or WebP up to 4 MB. Uploading replaces the current QR code.</small>
                    </div>

                    <button type="submit" class="btn btn-primary" data-submit-once>
                        <i class="fa fa-save mr-1"></i> Save settings
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="block text-center">
                <div class="title mb-3">
                    <strong>Current QR code</strong>
                </div>

                @if($settings['gcash_qr_path'])
                    <img src="{{ asset('payment-qr/'.$settings['gcash_qr_path']) }}" alt="GCash QR code"
                         class="img-fluid border rounded" style="max-width: 260px;">
                @else
                    <p class="text-muted">No QR code uploaded yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
