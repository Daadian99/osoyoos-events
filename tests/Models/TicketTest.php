<?php

namespace Tests\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Ticket;
use PDO;

class TicketTest extends TestCase
{
    private $pdo;
    private $ticket;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->ticket = new Ticket($this->pdo);
    }

    public function testCreateTicket()
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
             ->method('execute')
             ->with([1, 'General Admission', 50.00, 100])
             ->willReturn(true);

        $this->pdo->expects($this->once())
             ->method('prepare')
             ->with("INSERT INTO tickets (event_id, ticket_type, price, quantity) VALUES (?, ?, ?, ?)")
             ->willReturn($stmt);

        $this->pdo->expects($this->once())
             ->method('lastInsertId')
             ->willReturn('1');

        $result = $this->ticket->createTicket(1, 'General Admission', 50.00, 100);

        $this->assertEquals('1', $result);
    }

    public function testGetTicketsByEvent()
    {
        $expectedTickets = [
            ['id' => 1, 'ticket_type' => 'General Admission', 'price' => 50.00, 'quantity' => 100],
            ['id' => 2, 'ticket_type' => 'VIP', 'price' => 100.00, 'quantity' => 50]
        ];

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
             ->method('fetchAll')
             ->willReturn($expectedTickets);

        $this->pdo->expects($this->once())
             ->method('prepare')
             ->with("SELECT * FROM tickets WHERE event_id = ?")
             ->willReturn($stmt);

        $result = $this->ticket->getTicketsByEvent(1);

        $this->assertEquals($expectedTickets, $result);
    }

    public function testGetTicket()
    {
        $expectedTicket = [
            'id' => 1,
            'event_id' => 1,
            'ticket_type' => 'General Admission',
            'price' => 50.00,
            'quantity' => 100
        ];

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
             ->method('fetch')
             ->willReturn($expectedTicket);

        $this->pdo->expects($this->once())
             ->method('prepare')
             ->with("SELECT * FROM tickets WHERE id = ? AND event_id = ?")
             ->willReturn($stmt);

        $result = $this->ticket->getTicket(1, 1);

        $this->assertEquals($expectedTicket, $result);
    }

    public function testUpdateTicket()
    {
        // Mock for checking if the ticket exists
        $checkStmt = $this->createMock(\PDOStatement::class);
        $checkStmt->expects($this->once())
                  ->method('fetch')
                  ->willReturn(['id' => 1]); // Assume the ticket exists

        // Mock for updating the ticket
        $updateStmt = $this->createMock(\PDOStatement::class);
        $updateStmt->expects($this->once())
                   ->method('execute')
                   ->with(['VIP', 100.00, 50, 1, 1])
                   ->willReturn(true);

        $this->pdo->expects($this->exactly(2))
             ->method('prepare')
             ->withConsecutive(
                 ["SELECT * FROM tickets WHERE id = ? AND event_id = ?"],
                 ["UPDATE tickets SET ticket_type = ?, price = ?, quantity = ? WHERE id = ? AND event_id = ?"]
             )
             ->willReturnOnConsecutiveCalls($checkStmt, $updateStmt);

        $result = $this->ticket->updateTicket(1, 1, ['ticket_type' => 'VIP', 'price' => 100.00, 'quantity' => 50]);

        $this->assertTrue($result);
    }

    public function testDeleteTicket()
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
             ->method('execute')
             ->with([1, 1])
             ->willReturn(true);

        $this->pdo->expects($this->once())
             ->method('prepare')
             ->with("DELETE FROM tickets WHERE id = ? AND event_id = ?")
             ->willReturn($stmt);

        $result = $this->ticket->deleteTicket(1, 1);

        $this->assertTrue($result);
    }

    public function testPurchaseTicket()
    {
        // Mock the PDO and PDOStatement
        $pdoStatement = $this->createMock(\PDOStatement::class);
        $pdoStatement->method('execute')->willReturn(true);
        $pdoStatement->method('fetch')->willReturn(['available' => 10]);

        $this->pdo->method('prepare')->willReturn($pdoStatement);
        $this->pdo->method('beginTransaction')->willReturn(true);
        $this->pdo->method('commit')->willReturn(true);
        $this->pdo->method('lastInsertId')->willReturn('1');

        $result = $this->ticket->purchaseTicket(1, 1, 2);

        $this->assertEquals('1', $result);
    }

    public function testPurchaseTicketInsufficientQuantity()
    {
        // Mock the PDO and PDOStatement to simulate insufficient quantity
        $pdoStatement = $this->createMock(\PDOStatement::class);
        $pdoStatement->method('execute')->willReturn(true);
        $pdoStatement->method('fetch')->willReturn(['available' => 1]);

        $this->pdo->method('prepare')->willReturn($pdoStatement);
        $this->pdo->method('beginTransaction')->willReturn(true);
        $this->pdo->method('rollBack')->willReturn(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Not enough tickets available");

        $this->ticket->purchaseTicket(1, 1, 2);
    }

    public function testCancelTicketPurchase()
    {
        // Mock the PDO and PDOStatement
        $pdoStatement = $this->createMock(\PDOStatement::class);
        $pdoStatement->method('execute')->willReturn(true);
        $pdoStatement->method('fetch')->willReturn([
            'quantity' => 2,
            'ticket_id' => 1,
            'event_id' => 1
        ]);

        $this->pdo->method('prepare')->willReturn($pdoStatement);
        $this->pdo->method('beginTransaction')->willReturn(true);
        $this->pdo->method('commit')->willReturn(true);

        $result = $this->ticket->cancelTicketPurchase(1, 1);

        $this->assertEquals(1, $result);  // Expecting 1 row affected
    }

    public function testGetUserPurchasedTickets()
    {
        $expectedResult = [
            ['purchase_id' => 1, 'quantity' => 2, 'ticket_type' => 'General', 'event_title' => 'Test Event']
        ];

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchAll')
             ->willReturn($expectedResult);

        $this->pdo->expects($this->once())
         ->method('prepare')
         ->willReturn($stmt);

        $result = $this->ticket->getUserPurchasedTickets(1);

        $this->assertEquals($expectedResult, $result);
    }
}
