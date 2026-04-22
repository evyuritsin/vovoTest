<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        // Filters
        if ($request->has('q')) {
            $driver = $query->getConnection()->getDriverName();
            if ($driver === 'mysql') {
                $query->whereFullText('name', $request->q);
            } else {
                $query->where('name', 'like', '%' . $request->q . '%');
            }
        }

        if ($request->has('price_from')) {
            $query->where('price', '>=', $request->price_from);
        }

        if ($request->has('price_to')) {
            $query->where('price', '<=', $request->price_to);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('in_stock')) {
            $inStock = filter_var($request->in_stock, FILTER_VALIDATE_BOOLEAN);
            $query->where('in_stock', $inStock);
        }

        if ($request->has('rating_from')) {
            $query->where('rating', '>=', $request->rating_from);
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'rating_desc':
                $query->orderBy('rating', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        return ProductResource::collection($products);
    }
}
