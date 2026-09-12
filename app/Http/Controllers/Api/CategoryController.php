<?php

namespace App\Http\Controllers\Api;

use App\DTO\CategoryDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\CreateCategoryRequest;
use App\Http\Requests\Category\ListCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\User;
use App\UseCases\Category\CreateCategoryUseCase;
use App\UseCases\Category\ListCategoryUseCase;
use App\UseCases\Category\UpdateCategoryUseCase;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function store(CreateCategoryRequest $request, CreateCategoryUseCase $useCase): CategoryResource
    {
        /** @var User $user */
        $user = $request->user();

        return new CategoryResource($useCase->execute(CategoryDTO::fromArray($request->validated()), $user));
    }

    public function index(ListCategoryRequest $request, ListCategoryUseCase $useCase): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return CategoryResource::collection($useCase->execute($request->integer('wallet_id'), $user));
    }

    public function update(int $category, UpdateCategoryRequest $request, UpdateCategoryUseCase $useCase): CategoryResource
    {
        /** @var User $user */
        $user = $request->user();

        return new CategoryResource($useCase->execute($category, CategoryDTO::fromArray($request->validated()), $user));
    }
}
