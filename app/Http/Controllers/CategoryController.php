<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
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

    public function store(CategoryStoreRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return Redirect::route('categories.index');
    }

    public function update(CategoryUpdateRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return Redirect::route('categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return Redirect::route('categories.index');
    }
}
