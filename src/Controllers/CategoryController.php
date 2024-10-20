<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Category;

class CategoryController
{
    private $categoryModel;

    public function __construct(Category $categoryModel)
    {
        $this->categoryModel = $categoryModel;
    }

    public function getAllCategories(Request $request, Response $response): Response
    {
        $categories = $this->categoryModel->getAllCategories();
        $response->getBody()->write(json_encode($categories));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function createCategory(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $categoryId = $this->categoryModel->createCategory($data['name']);
        $response->getBody()->write(json_encode(['id' => $categoryId]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    // Add other methods for updating, deleting categories, etc.
}

