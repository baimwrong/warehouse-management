<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\BarcodeService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected BarcodeService $barcodeService
    ) {}

    public function index(Request $request)
    {
        $search = $request->query('search');
        $categoryId = $request->query('category_id');
        $stockStatus = $request->query('stock_status');

        $categories = Category::orderBy('name')->get();

        $query = Product::with(['category', 'stocks.warehouse', 'stocks.location']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('sku', 'ilike', "%{$search}%")
                  ->orWhere('barcode', 'ilike', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($stockStatus === 'in_stock') {
            $query->whereHas('stocks', fn($q) => $q->where('quantity_on_hand', '>', 0));
        } elseif ($stockStatus === 'low_stock') {
            $query->whereHas('stocks', function ($q) {
                $q->select('product_id')
                  ->groupBy('product_id')
                  ->havingRaw('SUM(quantity_on_hand) <= products.reorder_point AND SUM(quantity_on_hand) > 0');
            });
        } elseif ($stockStatus === 'out_of_stock') {
            $query->whereDoesntHave('stocks', fn($q) => $q->where('quantity_on_hand', '>', 0));
        }

        $products = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('products.index', compact('products', 'categories', 'search', 'categoryId', 'stockStatus'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:60|unique:products,sku',
            'barcode' => 'nullable|string|max:80|unique:products,barcode',
            'name' => 'required|string|max:200',
            'category_id' => 'nullable|exists:categories,id',
            'uom' => 'required|string|in:PCS,BOX,PALLET,KG,LTR,METER',
            'min_stock_level' => 'required|numeric|min:0',
            'max_stock_level' => 'required|numeric|min:0',
            'reorder_point' => 'required|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'weight_kg' => 'required|numeric|min:0',
            'volume_cbm' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = true;
        $product = Product::create($validated);

        return redirect()->route('products.show', $product)
            ->with('success', "Product '{$product->name}' [SKU: {$product->sku}] created successfully.");
    }

    public function show(Product $product)
    {
        $product->load([
            'category',
            'batches.supplier',
            'stocks.warehouse',
            'stocks.location.zone',
            'stockMovements.fromLocation',
            'stockMovements.toLocation',
            'stockMovements.user',
        ]);

        $barcodeSvg = $this->barcodeService->generateSvg($product->barcode ?? $product->sku);

        return view('products.show', compact('product', 'barcodeSvg'));
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sku' => "required|string|max:60|unique:products,sku,{$product->id}",
            'barcode' => "nullable|string|max:80|unique:products,barcode,{$product->id}",
            'name' => 'required|string|max:200',
            'category_id' => 'nullable|exists:categories,id',
            'uom' => 'required|string|in:PCS,BOX,PALLET,KG,LTR,METER',
            'min_stock_level' => 'required|numeric|min:0',
            'max_stock_level' => 'required|numeric|min:0',
            'reorder_point' => 'required|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'weight_kg' => 'required|numeric|min:0',
            'volume_cbm' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $product->update($validated);

        return redirect()->route('products.show', $product)
            ->with('success', "Product '{$product->name}' updated successfully.");
    }

    public function destroy(Product $product)
    {
        if ($product->stocks()->where('quantity_on_hand', '>', 0)->exists()) {
            return back()->withErrors(['error' => 'Cannot delete product with existing inventory on hand.']);
        }

        if ($product->stockMovements()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete product with existing audit stock movement history. Please deactivate it instead.']);
        }

        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }

    public function barcodes(Request $request)
    {
        $products = Product::where('is_active', true)->with('category')->orderBy('name')->paginate(24)->withQueryString();

        $labels = $products->through(function ($product) {
            return [
                'product' => $product,
                'barcodeSvg' => $this->barcodeService->generateSvg($product->barcode ?? $product->sku, 200, 50),
            ];
        });

        return view('products.barcodes', compact('labels'));
    }
}
