<?php

namespace Modules\Category\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Routing\Controller;
use Modules\Category\Entities\Category;

class RestaurantCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function list()
    {
        $categories = Category::latest()->get();

        return view('category::restaurant.index', ['categories' => $categories]);
    }
}
