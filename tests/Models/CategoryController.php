<?php

use PHPUnit\Framework\TestCase;
use App\Controllers\CategoryController;
use App\Models\Category;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CategoryControllerTest extends TestCase
{
    private $categoryModel;
    private $categoryController;

    protected function setUp(): void
    {
        $this->categoryModel = $this->createMock(Category::class);
        $this->categoryController = new CategoryController($this->categoryModel);
    }

    public function testGetAllCategories()
    {
        $categories = [
            ['id' => 1, 'name' => 'Category 1'],
            ['id' => 2, 'name' => 'Category 2'],
        ];

        $this->categoryModel->expects($this->once())
            ->method('getAllCategories')
            ->willReturn($categories);

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->createMock(\Psr\Http\Message\StreamInterface::class));

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $result = $this->categoryController->getAllCategories($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testCreateCategory()
    {
        $newCategory = ['id' => 1, 'name' => 'New Category'];

        $this->categoryModel->expects($this->once())
            ->method('createCategory')
            ->with('New Category')
            ->willReturn(1);

        $this->categoryModel->expects($this->once())
            ->method('getCategoryById')
            ->with(1)
            ->willReturn($newCategory);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn(['name' => 'New Category']);

        $response = $this->createMock(ResponseInterface::class);

        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->createMock(\Psr\Http\Message\StreamInterface::class));

        $response->expects($this->once())
            ->method('withStatus')
            ->with(201)
            ->willReturnSelf();

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $result = $this->categoryController->createCategory($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}

