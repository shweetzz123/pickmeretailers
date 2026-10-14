@extends('admin.maindesign')

@section('page_title', 'Update Product')
@section('page_header', 'Update Product')
@section('page_subtitle', 'Edit product details, pricing, pack sizes, categories, and pairings.')

@section('page_actions')
    <a href="{{ route('admin.viewproduct') }}" class="btn btn-outline-primary btn-sm">
        <i class="fa fa-list mr-1"></i> View Products
    </a>
@endsection

@section('update_product')

    @if(session('update_product_message'))
        <div class="alert alert-success mb-4">{{ session('update_product_message') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="container-fluid">
        <form action="{{ route('admin.postupdateproduct', $product->id) }}" method="POST" enctype="multipart/form-data" data-submit-once>
            @csrf

            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header"><h5 class="card-title mb-0">Product Information</h5></div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label for="product_title"><strong>Product Title</strong></label>
                                <input type="text" name="product_title" id="product_title" class="form-control @error('product_title') is-invalid @enderror" placeholder="Enter product title" value="{{ old('product_title', $product->product_title) }}">
                                @error('product_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-group mb-3">
                                <label for="product_description"><strong>Product Description</strong></label>
                                <textarea name="product_description" id="product_description" rows="4" class="form-control @error('product_description') is-invalid @enderror" placeholder="Describe the product">{{ old('product_description', $product->product_description) }}</textarea>
                                @error('product_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="product_category"><strong>Product Category</strong></label>
                                        <input type="text" name="product_category" id="product_category" list="category-options" class="form-control @error('product_category') is-invalid @enderror" placeholder="e.g. Household" value="{{ old('product_category', $product->product_category) }}">
                                        <datalist id="category-options">
                                            @foreach($categories as $category)
                                                <option value="{{ $category->category }}"></option>
                                            @endforeach
                                        </datalist>
                                        @error('product_category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="expiry_date"><strong>Expiry Date</strong> <small class="text-muted">(optional)</small></label>
                                        <input type="date" name="expiry_date" id="expiry_date" class="form-control @error('expiry_date') is-invalid @enderror" value="{{ old('expiry_date', optional($product->expiry_date)->format('Y-m-d')) }}">
                                        @error('expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header"><h5 class="card-title mb-0">Pricing &amp; Inventory</h5></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="product_price"><strong>Retail Price</strong></label>
                                        <input type="number" name="product_price" id="product_price" step="0.01" min="0" class="form-control @error('product_price') is-invalid @enderror" placeholder="0.00" value="{{ old('product_price', $product->product_price) }}">
                                        @error('product_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="wholesale_price"><strong>Bulk / Resale Price</strong> <small class="text-muted">(optional — used when a line reaches 10+ units)</small></label>
                                        <input type="number" name="wholesale_price" id="wholesale_price" class="form-control @error('wholesale_price') is-invalid @enderror" placeholder="Bulk price" step="0.01" min="0" value="{{ old('wholesale_price', $product->wholesale_price) }}">
                                        @error('wholesale_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="product_quantity"><strong>Stock Quantity</strong></label>
                                        <input type="number" name="product_quantity" id="product_quantity" class="form-control @error('product_quantity') is-invalid @enderror" placeholder="0" min="0" value="{{ old('product_quantity', $product->product_quantity) }}">
                                        <small class="text-muted">Leave empty if you add pack sizes below.</small>
                                        @error('product_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="reorder_level"><strong>Reorder Level</strong> <small class="text-muted">(optional)</small></label>
                                        <input type="number" name="reorder_level" id="reorder_level" class="form-control @error('reorder_level') is-invalid @enderror" placeholder="Low-stock alert threshold" min="0" value="{{ old('reorder_level', $product->reorder_level) }}">
                                        @error('reorder_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header"><h5 class="card-title mb-0">Category Assignment</h5></div>
                        <div class="card-body">
                            <div class="form-group mb-0">
                                <label for="categories"><strong>Categories</strong> <small class="text-muted">(optional — hold Ctrl/Cmd to select multiple)</small></label>
                                <select name="categories[]" id="categories" multiple class="form-control @error('categories') is-invalid @enderror" size="6">
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ in_array($category->id, old('categories', $product->categories->pluck('id')->all())) ? 'selected' : '' }}>{{ $category->category }}</option>
                                    @endforeach
                                </select>
                                @error('categories')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header"><h5 class="card-title mb-0">Product Pairings</h5></div>
                        <div class="card-body">
                            <div class="form-group mb-0">
                                <label for="pairings"><strong>Frequently bought together</strong> <small class="text-muted">(optional — hold Ctrl/Cmd to select multiple)</small></label>
                                <select name="pairings[]" id="pairings" multiple class="form-control @error('pairings') is-invalid @enderror" size="6">
                                    @foreach($allProducts as $related)
                                        <option value="{{ $related->id }}" {{ in_array($related->id, old('pairings', $product->pairings->pluck('id')->all())) ? 'selected' : '' }}>{{ $related->product_title }}</option>
                                    @endforeach
                                </select>
                                @error('pairings')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header"><h5 class="card-title mb-0">Product Images</h5></div>
                        <div class="card-body">
                            @if($product->product_image)
                                <p class="text-muted small mb-2"><strong>Main image</strong></p>
                                <div class="mb-3 text-center">
                                    <img src="{{ asset('products/'.$product->product_image) }}" alt="{{ $product->product_title }}" class="img-fluid rounded border" style="max-height: 200px;">
                                </div>
                            @endif

                            @php($galleryImages = $product->product_images ?? [])
                            @if(!empty($galleryImages))
                                <p class="text-muted small mb-2"><strong>Gallery images</strong> <small>(tick to remove)</small></p>
                                <div class="d-flex flex-wrap mb-3">
                                    @foreach($galleryImages as $image)
                                        <label class="mr-2 mb-2 text-center" style="cursor: pointer;">
                                            <img src="{{ asset('products/'.$image) }}" alt="{{ $product->product_title }}" class="rounded border d-block" style="height: 90px; width: 90px; object-fit: cover;">
                                            <small class="d-block mt-1">
                                                <input type="checkbox" name="remove_images[]" value="{{ $image }}"> remove
                                            </small>
                                        </label>
                                    @endforeach
                                </div>
                            @endif

                            <div class="form-group mb-3">
                                <label for="product_image"><strong>Replace Main Image</strong> <small class="text-muted">(optional)</small></label>
                                <input type="file" name="product_image" id="product_image" accept="image/jpeg,image/png,image/webp" class="form-control-file @error('product_image') is-invalid @enderror">
                                @error('product_image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-group mb-0">
                                <label for="product_images"><strong>Add Gallery Images</strong> <small class="text-muted">(optional)</small></label>
                                <input type="file" name="product_images[]" id="product_images" accept="image/jpeg,image/png,image/webp" class="form-control-file @error('product_images') is-invalid @enderror @error('product_images.*') is-invalid @enderror" multiple>
                                <small class="form-text text-muted d-block mt-2">JPG, PNG or WEBP. Max 10MB each.</small>
                                @error('product_images')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                @error('product_images.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div id="product-images-preview" class="d-flex flex-wrap mt-3"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header"><h5 class="card-title mb-0">Pack Sizes</h5></div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">Add pack size options like 1 pc, 6 pcs, 1 box. If pack sizes are added, quantity and price above can be left empty.</p>
                            <div id="variants-container">
                                @forelse($product->variants as $variant)
                                    <div class="variant-row form-row mb-2">
                                        <div class="col">
                                            <input type="text" name="variant_weight[]" class="form-control form-control-sm" placeholder="Pack size (e.g. 1 box)" value="{{ old('variant_weight.'.$loop->index, $variant->weight) }}">
                                        </div>
                                        <div class="col">
                                            <input type="number" name="variant_price[]" class="form-control form-control-sm" placeholder="Price" step="0.01" min="0" value="{{ old('variant_price.'.$loop->index, $variant->price) }}">
                                        </div>
                                        <div class="col">
                                            <input type="number" name="variant_quantity[]" class="form-control form-control-sm" placeholder="Stock" min="0" value="{{ old('variant_quantity.'.$loop->index, $variant->quantity) }}">
                                        </div>
                                        <div class="col-auto">
                                            <button type="button" onclick="removeVariant(this)" class="btn btn-danger btn-sm"><i class="fa fa-times"></i></button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="variant-row form-row mb-2">
                                        <div class="col">
                                            <input type="text" name="variant_weight[]" class="form-control form-control-sm" placeholder="Pack size (e.g. 1 box)">
                                        </div>
                                        <div class="col">
                                            <input type="number" name="variant_price[]" class="form-control form-control-sm" placeholder="Price" step="0.01" min="0">
                                        </div>
                                        <div class="col">
                                            <input type="number" name="variant_quantity[]" class="form-control form-control-sm" placeholder="Stock" min="0">
                                        </div>
                                        <div class="col-auto">
                                            <button type="button" onclick="removeVariant(this)" class="btn btn-danger btn-sm"><i class="fa fa-times"></i></button>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                            <button type="button" onclick="addVariant()" class="btn btn-success btn-sm mt-2">
                                <i class="fa fa-plus mr-1"></i> Add Pack Size
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mb-4">
                <a href="{{ route('admin.viewproduct') }}" class="btn btn-secondary mr-2">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save mr-1"></i> Update Product</button>
            </div>
        </form>
    </div>

    <script>
        function addVariant() {
            var container = document.getElementById('variants-container');
            var row = document.createElement('div');
            row.className = 'variant-row form-row mb-2';
            row.innerHTML = '<div class="col"><input type="text" name="variant_weight[]" class="form-control form-control-sm" placeholder="Pack size (e.g. 1 box)"></div>' +
                '<div class="col"><input type="number" name="variant_price[]" class="form-control form-control-sm" placeholder="Price" step="0.01" min="0"></div>' +
                '<div class="col"><input type="number" name="variant_quantity[]" class="form-control form-control-sm" placeholder="Stock" min="0"></div>' +
                '<div class="col-auto"><button type="button" onclick="removeVariant(this)" class="btn btn-danger btn-sm"><i class="fa fa-times"></i></button></div>';
            container.appendChild(row);
        }

        function removeVariant(button) {
            var container = document.getElementById('variants-container');
            var row = button.closest('.variant-row');
            if (container.querySelectorAll('.variant-row').length > 1) {
                row.remove();
            } else {
                row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
            }
        }

        function renderProductImagePreview(input, targetId) {
            var preview = document.getElementById(targetId);
            if (!preview || !input) return;
            preview.innerHTML = '';
            Array.prototype.forEach.call(input.files || [], function (file) {
                if (!file.type || file.type.indexOf('image/') !== 0) return;
                var wrapper = document.createElement('div');
                wrapper.className = 'position-relative mr-2 mb-2';
                var img = document.createElement('img');
                img.className = 'rounded border';
                img.style.height = '90px';
                img.style.width = '90px';
                img.style.objectFit = 'cover';
                img.src = URL.createObjectURL(file);
                wrapper.appendChild(img);
                preview.appendChild(wrapper);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            var input = document.getElementById('product_images');
            if (input) {
                input.addEventListener('change', function () {
                    renderProductImagePreview(input, 'product-images-preview');
                });
            }
        });
    </script>

@endsection
