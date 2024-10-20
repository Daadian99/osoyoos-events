<?php

namespace Tests\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Category;
use PDO;

class CategoryTest extends TestCase
{
    private $pdo;
    private $category;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->category = new Category($this->pdo);
    }

    public function testCreateCategory()
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
             ->method('execute')
             ->with(['Test Category'])
             ->willReturn(true);

        $this->pdo->expects($this->once())
             ->method('prepare')
             ->with("INSERT INTO categories (name) VALUES (?)")
             ->willReturn($stmt);

        $this->pdo->expects($this->once())
             ->method('lastInsertId')
             ->willReturn('1');

        $result = $this->category->createCategory('Test Category');

        $this->assertEquals('1', $result);
    }

    public function testGetAllCategories()
    {
        $expectedCategories = [
            ['id' => 1, 'name' => 'Category 1'],
            ['id' => 2, 'name' => 'Category 2']
        ];

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
             ->method('fetchAll')
             ->willReturn($expectedCategories);

        $this->pdo->expects($this->once())
             ->method('query')
             ->with("SELECT * FROM categories ORDER BY name")
             ->willReturn($stmt);

        $result = $this->category->getAllCategories();

        $this->assertEquals($expectedCategories, $result);
    }

    public function testUpdateCategory()
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
             ->method('execute')
             ->with(['New Name', 1])
             ->willReturn(true);

        $this->pdo->expects($this->once())
             ->method('prepare')
             ->with("UPDATE categories SET name = ? WHERE id = ?")
             ->willReturn($stmt);

        $result = $this->category->updateCategory(1, 'New Name');

        $this->assertTrue($result);
    }

    public function testDeleteCategory()
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
             ->method('execute')
             ->with([1])
             ->willReturn(true);

        $this->pdo->expects($this->once())
             ->method('prepare')
             ->with("DELETE FROM categories WHERE id = ?")
             ->willReturn($stmt);

        $result = $this->category->deleteCategory(1);

        $this->assertTrue($result);
    }
}
