<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    private string $jsonFile = 'products.json';

    public function index(): View|JsonResponse
    {
        $products = Product::query()
            ->orderByDesc('created_at')
            ->get();

        $this->syncJsonFile($products);

        $grandTotal = $products->sum(
            fn (Product $product) => $product->total_value
        );

        if (request()->expectsJson()) {
            return response()->json([
                'products' => $products,
                'grand_total' => $grandTotal,
            ]);
        }

        return view('products.index');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_name' => [
                'required',
                'string',
                'max:255',
            ],
            'quantity_in_stock' => [
                'required',
                'integer',
                'min:0',
            ],
            'price_per_item' => [
                'required',
                'numeric',
                'min:0',
            ],
        ]);

        $product = Product::create($validated);

        $this->syncJsonFile(
            Product::query()
                ->orderByDesc('created_at')
                ->get()
        );

        return response()->json([
            'message' => 'Product added successfully.',
            'product' => $product,
        ], 201);
    }

    public function update(
        Request $request,
        Product $product
    ): JsonResponse {
        $validated = $request->validate([
            'product_name' => [
                'required',
                'string',
                'max:255',
            ],
            'quantity_in_stock' => [
                'required',
                'integer',
                'min:0',
            ],
            'price_per_item' => [
                'required',
                'numeric',
                'min:0',
            ],
        ]);

        $product->update($validated);

        $product = $product->fresh();

        $this->syncJsonFile(
            Product::query()
                ->orderByDesc('created_at')
                ->get()
        );

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $product,
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        $this->syncJsonFile(
            Product::query()
                ->orderByDesc('created_at')
                ->get()
        );

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    private function syncJsonFile($products): void
    {
        $data = [
            'products' => $products
                ->values()
                ->map(function (Product $product) {
                    return [
                        'id' => $product->id,
                        'product_name' => $product->product_name,
                        'quantity_in_stock' => $product->quantity_in_stock,
                        'price_per_item' => (float) $product->price_per_item,
                        'datetime_submitted' => $product->datetime_submitted,
                        'total_value' => $product->total_value,
                    ];
                })
                ->all(),
        ];

        Storage::disk('local')->put(
            $this->jsonFile,
            json_encode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );
    }
}