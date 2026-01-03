<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::all();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }
}
