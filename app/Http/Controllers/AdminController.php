<?php

namespace App\Http\Controllers;

use App\Mail\OrderDeliveredMail;
use App\Mail\OrderShippedMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function dashboard()
    {
        $isOwner = auth()->user()?->isOwner() ?? false;
        $today = Carbon::today();
        $salesStartDate = $today->copy()->subDays(6)->startOfDay();
        $salesEndDate = $today->copy()->endOfDay();
        $hasReorderLevel = Schema::hasColumn('products', 'reorder_level');
        $hasExpiryDate = Schema::hasColumn('products', 'expiry_date');

        // Clerks never see sales, revenue, or order figures — only neutral
        // inventory/product stats. The sales queries below are owner-only.
        $dashboardStats = [
            'totalProducts' => Product::count(),
            'totalOrders' => 0,
            'ordersToday' => 0,
            'pendingOrders' => 0,
            'paidRevenue' => 0,
            'todayRevenue' => 0,
            'monthRevenue' => 0,
            'lowStockProducts' => $hasReorderLevel
                ? Product::whereColumn('product_quantity', '<', 'reorder_level')->count()
                : 0,
            'expiringProducts' => $hasExpiryDate
                ? Product::whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '<=', $today->copy()->addDays(3))
                    ->where('product_quantity', '>', 0)
                    ->count()
                : 0,
            'totalCustomers' => 0,
            'awaitingVerification' => 0,
            'pendingPaymentValue' => 0,
        ];

        $recentOrders = collect();
        $topProducts = collect();
        $orderStatusCounts = collect();
        $salesTrend = ['labels' => [], 'revenues' => [], 'orders' => []];

        if ($isOwner) {
            $dashboardStats['totalOrders'] = Order::count();
            $dashboardStats['ordersToday'] = Order::whereDate('created_at', $today)->count();
            $dashboardStats['pendingOrders'] = Order::where('status', 'pending')->count();
            $dashboardStats['paidRevenue'] = Order::where('payment_status', 'paid')->sum('total');
            $dashboardStats['todayRevenue'] = Order::where('payment_status', 'paid')
                ->whereDate('created_at', $today)
                ->sum('total');
            $dashboardStats['monthRevenue'] = Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
                ->sum('total');
            $dashboardStats['totalCustomers'] = User::customers()->count();
            $dashboardStats['awaitingVerification'] = Order::where('payment_status', 'awaiting_verification')->count();
            $dashboardStats['pendingPaymentValue'] = Order::where('payment_status', 'awaiting_verification')->sum('total');

            $recentOrders = Order::with(['user', 'items.product'])
                ->latest()
                ->take(6)
                ->get()
                ->each(function ($order) {
                    $order->setRelation('items', $order->items->take(5));
                });

            $topProducts = OrderItem::query()
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->whereNotIn('orders.status', ['cancelled', 'returned'])
                ->select(
                    'products.product_title',
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                    DB::raw('SUM(order_items.quantity * order_items.price) as total_sales')
                )
                ->groupBy('products.id', 'products.product_title')
                ->orderByDesc('total_quantity')
                ->take(5)
                ->get();

            $orderStatusCounts = Order::query()
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            $salesTrend = $this->dashboardSalesTrend($salesStartDate, $salesEndDate);
        }

        $lowStockProducts = $hasReorderLevel
            ? Product::whereColumn('product_quantity', '<', 'reorder_level')
                ->orderBy('product_quantity')
                ->take(5)
                ->get(['id', 'product_title', 'product_quantity', 'reorder_level'])
            : collect();

        $expiringProducts = $hasExpiryDate
            ? Product::whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<=', $today->copy()->addDays(3))
                ->where('product_quantity', '>', 0)
                ->orderBy('expiry_date')
                ->take(5)
                ->get(['id', 'product_title', 'product_quantity', 'expiry_date'])
            : collect();

        return view('admin.dashboard', compact(
            'isOwner',
            'dashboardStats',
            'recentOrders',
            'topProducts',
            'lowStockProducts',
            'expiringProducts',
            'orderStatusCounts',
            'salesTrend'
        ));
    }

    public function addCategory()
    {
        return view('admin.addcategory');
    }

    public function postAddCategory(Request $request)
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:255', 'unique:categories,category'],
        ]);

        $category = new Category;
        $category->category = $validated['category'];
        $category->save();

        Cache::forget('shop_categories');

        return redirect()->back()->with('category_message', 'Category added successfully!');
    }

    public function viewCategory()
    {
        $categories = Category::all();

        return view('admin.viewcategory', compact('categories'));
    }

    public function deleteCategory($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        Cache::forget('shop_categories');

        return redirect()->back()->with('deletecategory_message', 'Deleted successfully!');
    }

    public function updateCategory($id)
    {
        $category = Category::findOrFail($id);

        return view('admin.updatecategory', compact('category'));
    }

    public function postUpdatecategory(Request $request, $id)
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:255', 'unique:categories,category,'.$id],
        ]);

        $category = Category::findOrFail($id);
        $category->category = $validated['category'];
        $category->save();

        Cache::forget('shop_categories');

        return redirect()->back()->with('category_updated_message', 'Category updated successfully!');
    }

    public function addProduct()
    {
        $categories = Category::all();
        $allProducts = Product::orderBy('product_title')->get(['id', 'product_title']);

        return view('admin.addproduct', compact('categories', 'allProducts'));
    }

    public function postAddProduct(Request $request)
    {
        $validated = $this->validateProduct($request);

        $files = $this->uploadedProductImages($request);

        if ($files === []) {
            throw ValidationException::withMessages([
                'product_images' => 'Please upload at least one product image.',
            ]);
        }

        $product = new Product;
        $product->product_title = $validated['product_title'];
        $product->product_description = $validated['product_description'];
        $product->product_category = $validated['product_category'];
        // product_type removed
        $product->product_quantity = $validated['product_quantity'] ?? 0;
        $product->reorder_level = $validated['reorder_level'] ?? null;
        $product->expiry_date = $validated['expiry_date'] ?? null;
        $product->product_price = $validated['product_price'] ?? 0;
        $product->wholesale_price = $validated['wholesale_price'] ?? null;

        // The first uploaded image becomes the main product image; the rest
        // are kept as additional gallery images.
        $stored = $this->storeProductImages($files);
        $product->product_image = array_shift($stored);
        $product->product_images = $stored !== [] ? $stored : null;

        $product->save();

        $this->syncCategories($product, $request);
        $this->syncVariants($product, $request);
        $product->pairings()->sync($request->input('pairings', []));

        return redirect()->back()->with('product_message', 'Product added successfully!');
    }

    public function viewProduct()
    {
        $products = Product::orderBy('created_at', 'desc')->paginate(5);

        return view('admin.viewproduct', compact('products'));
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);

        foreach ($product->galleryImages() as $image) {
            $imagePath = public_path('products/'.$image);

            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $product->delete();

        return redirect()->back()->with('deleteproduct_message', 'Deleted successfully.');
    }

    public function updateProduct($id)
    {
        $product = Product::with(['pairings', 'categories'])->findOrFail($id);
        $categories = Category::all();
        $allProducts = Product::where('id', '!=', $id)->orderBy('product_title')->get(['id', 'product_title']);

        return view('admin.updateproduct', compact('product', 'categories', 'allProducts'));
    }

    public function postUpdateProduct(Request $request, $id)
    {
        $validated = $this->validateProduct($request, true);
        $product = Product::findOrFail($id);

        // Clerks can edit products but must never lower stock from this form;
        // stock can only go up via a restock (inventory screen).
        if (
            isset($validated['product_quantity'])
            && ! $request->user()->isOwner()
            && (int) $validated['product_quantity'] < (int) $product->product_quantity
        ) {
            throw ValidationException::withMessages([
                'product_quantity' => 'Clerks cannot reduce product stock. Ask the owner to adjust stock.',
            ]);
        }

        $product->product_title = $validated['product_title'];
        $product->product_description = $validated['product_description'];
        $product->product_category = $validated['product_category'];
        // product_type removed
        $product->product_quantity = $validated['product_quantity'] ?? $product->product_quantity;
        $product->reorder_level = $validated['reorder_level'] ?? $product->reorder_level;
        $product->expiry_date = $validated['expiry_date'] ?? $product->expiry_date;
        $product->product_price = $validated['product_price'] ?? $product->product_price;
        $product->wholesale_price = $validated['wholesale_price'] ?? null;

        // Gallery images the admin ticked for removal.
        $remove = collect($request->input('remove_images', []))
            ->filter(fn ($image) => is_string($image) && $image !== '')
            ->all();

        if ($remove !== []) {
            $remaining = array_values(array_diff($product->product_images ?? [], $remove));
            $product->product_images = $remaining !== [] ? $remaining : null;

            foreach ($remove as $image) {
                $imagePath = public_path('products/'.$image);

                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
        }

        // A new main image replaces the existing one.
        if ($request->hasFile('product_image')) {
            $oldImagePath = public_path('products/'.$product->product_image);

            if ($product->product_image && file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }

            $product->product_image = $this->storeProductImageFile($request->file('product_image'));
        }

        // Any additional uploads are appended to the gallery.
        $newGalleryImages = $this->storeProductImages($this->uploadedProductImages($request, false));

        if ($newGalleryImages !== []) {
            $gallery = array_values(array_unique(array_merge($product->product_images ?? [], $newGalleryImages)));
            $product->product_images = $gallery !== [] ? $gallery : null;
        }

        $product->save();

        $this->syncCategories($product, $request);
        $this->syncVariants($product, $request);
        $product->pairings()->sync($request->input('pairings', []));

        return redirect()->back()->with('update_product_message', 'Product updated successfully!');
    }

    public function searchProduct(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = $validated['search'] ?? '';

        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
        $products = Product::query()
            ->when($search !== '', function ($query) use ($escaped) {
                $query->where('product_title', 'LIKE', '%'.$escaped.'%')
                    ->orWhere('product_description', 'LIKE', '%'.$escaped.'%')
                    ->orWhere('product_category', 'LIKE', '%'.$escaped.'%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(5)
            ->appends(['search' => $search]);

        return view('admin.viewproduct', compact('products'));
    }

    public function viewOrders()
    {
        $orders = Order::with('user', 'items.product')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $orderStats = [
            'total' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'paid' => Order::where('payment_status', 'paid')->count(),
            'revenue' => Order::where('payment_status', 'paid')->sum('total'),
        ];

        return view('admin.vieworders', compact('orders', 'orderStats'));
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::with(['items.product', 'user'])->findOrFail($id);
        $validated = $request->validate([
            'status' => ['required', 'in:pending,processing,shipped,delivered,cancelled,returned,failed'],
        ]);

        $allowedTransitions = [
            'pending' => ['processing', 'shipped', 'cancelled', 'failed'],
            'processing' => ['shipped', 'delivered', 'cancelled', 'failed'],
            'shipped' => ['delivered', 'returned', 'failed'],
            'delivered' => ['returned'],
            'cancelled' => [],
            'returned' => [],
        ];

        $current = $order->status;
        $target = $validated['status'];

        if (isset($allowedTransitions[$current]) && ! in_array($target, $allowedTransitions[$current], true)) {
            return redirect()->back()->withErrors([
                'status' => "Cannot change an order from \"{$current}\" to \"{$target}\".",
            ]);
        }

        $order->status = $target;
        $order->save();

        $mailWarning = null;

        if ($order->status === 'shipped' && $order->user) {
            try {
                Mail::to($order->user->email)->queue(new OrderShippedMail($order));
            } catch (\Throwable $e) {
                Log::error('Failed to queue the shipped email.', [
                    'order_id' => $order->id,
                    'recipient' => $order->user->email,
                    'exception' => $e->getMessage(),
                ]);
                $mailWarning = 'Status updated, but the shipped email could not be queued.';
            }
        }

        if ($order->status === 'delivered' && $order->user) {
            try {
                Mail::to($order->user->email)->queue(new OrderDeliveredMail($order));
            } catch (\Throwable $e) {
                Log::error('Failed to queue the delivered email.', [
                    'order_id' => $order->id,
                    'recipient' => $order->user->email,
                    'exception' => $e->getMessage(),
                ]);
                $mailWarning = 'Status updated, but the delivered email could not be queued.';
            }
        }

        return redirect()->back()->with(
            'success',
            'Order status updated!'.($mailWarning ? ' '.$mailWarning : '')
        );
    }

    public function deleteOrder($id)
    {
        $order = Order::findOrFail($id);

        DB::transaction(function () use ($order) {
            OrderItem::where('order_id', $order->id)->delete();
            $order->delete();
        });

        return redirect()->back()->with('success', 'Order deleted successfully.');
    }

    protected function dashboardSalesTrend(Carbon $startDate, Carbon $endDate): array
    {
        $dailySales = Order::query()
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as order_date'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders_count')
            )
            ->groupBy('order_date')
            ->orderBy('order_date')
            ->get()
            ->keyBy('order_date');

        $labels = [];
        $revenues = [];
        $orders = [];

        foreach (CarbonPeriod::create($startDate->copy()->startOfDay(), $endDate->copy()->startOfDay()) as $date) {
            $key = $date->toDateString();
            $labels[] = $date->format('M d');
            $revenues[] = round((float) ($dailySales[$key]->revenue ?? 0), 2);
            $orders[] = (int) ($dailySales[$key]->orders_count ?? 0);
        }

        return [
            'labels' => $labels,
            'revenues' => $revenues,
            'orders' => $orders,
        ];
    }

    protected function validateProduct(Request $request, bool $isUpdate = false): array
    {
        $hasVariants = ! empty($request->input('variant_weight')) && collect($request->input('variant_weight'))->filter()->isNotEmpty();

        return $request->validate([
            'product_title' => ['required', 'string', 'max:255'],
            'product_description' => ['required', 'string'],
            'product_quantity' => [$hasVariants ? 'nullable' : 'required', 'integer', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'expiry_date' => ['nullable', 'date'],
            'product_price' => [$hasVariants ? 'nullable' : 'required', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'product_category' => ['required', 'string', 'max:255'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],

            'pairings' => ['nullable', 'array'],
            'pairings.*' => ['integer', 'exists:products,id'],
            'product_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'product_images' => ['nullable', 'array', 'max:8'],
            'product_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'variant_weight.*' => ['nullable', 'string', 'max:50'],
            'variant_price.*' => ['nullable', 'numeric', 'min:0'],
            'variant_quantity.*' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    protected function syncCategories(Product $product, Request $request): void
    {
        $categoryIds = collect($request->input('categories', []))->filter();

        $primaryName = $request->input('product_category');
        if ($primaryName) {
            // Create the category if it doesn't exist yet so any category typed
            // on a product becomes a real, filterable category in the shop.
            $primaryId = Category::firstOrCreate(['category' => $primaryName])->id;
            $categoryIds->push($primaryId);
        }

        $product->categories()->sync($categoryIds->unique()->values()->all());

        // Keep the shop's cached category filter list fresh.
        Cache::forget('shop_categories');
    }

    /**
     * Collect the uploaded product images: the multi-select gallery plus the
     * legacy single "product_image" field (kept for backwards compatibility).
     *
     * @return array<int, \Illuminate\Http\UploadedFile>
     */
    protected function uploadedProductImages(Request $request, bool $includeMain = true): array
    {
        $files = [];

        if ($request->hasFile('product_images')) {
            foreach ($request->file('product_images') as $file) {
                if ($file && $file->isValid()) {
                    $files[] = $file;
                }
            }
        }

        if ($includeMain && $request->hasFile('product_image')) {
            $file = $request->file('product_image');

            if ($file && $file->isValid()) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $files
     * @return array<int, string>
     */
    protected function storeProductImages(array $files): array
    {
        return array_map(fn ($file) => $this->storeProductImageFile($file), $files);
    }

    protected function storeProductImageFile($image): string
    {
        $imageName = uniqid('product_', true).'.'.$image->guessExtension();
        $directory = public_path('products');
        File::ensureDirectoryExists($directory);
        $image->move($directory, $imageName);

        return $imageName;
    }

    protected function syncVariants(Product $product, Request $request): void
    {
        $weights = $request->input('variant_weight', []);
        $prices = $request->input('variant_price', []);
        $quantities = $request->input('variant_quantity', []);

        $data = [];
        for ($i = 0; $i < count($weights); $i++) {
            $weight = trim($weights[$i] ?? '');
            $price = $prices[$i] ?? null;
            $qty = $quantities[$i] ?? 0;

            if ($weight !== '' && $price !== null && $price !== '') {
                $data[] = [
                    'weight' => $weight,
                    'price' => $price,
                    'quantity' => (int) $qty,
                ];
            }
        }

        $existingIds = $product->variants->pluck('id')->toArray();
        $incomingCount = count($data);
        $keepIds = array_slice($existingIds, 0, $incomingCount);

        ProductVariant::where('product_id', $product->id)
            ->whereNotIn('id', $keepIds)
            ->delete();

        foreach ($data as $index => $variant) {
            if (isset($keepIds[$index])) {
                ProductVariant::where('id', $keepIds[$index])->update($variant);
            } else {
                ProductVariant::create(array_merge($variant, ['product_id' => $product->id]));
            }
        }
    }
}
