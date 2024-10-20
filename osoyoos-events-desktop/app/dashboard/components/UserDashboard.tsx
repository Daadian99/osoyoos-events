'use client';
import React, { useEffect, useState } from 'react';

interface Ticket {
  id: number;
  eventName: string;
  purchaseDate: string;
}

const UserDashboard: React.FC = () => {
  const [tickets, setTickets] = useState<Ticket[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const loadTickets = async () => {
      try {
        const response = await fetch('http://osoyoos-events.localhost/user/tickets', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        });
        if (!response.ok) {
          throw new Error('Failed to fetch user tickets');
        }
        const data = await response.json();
        setTickets(data.tickets);
      } catch (error) {
        console.error('Failed to fetch user tickets:', error);
        setError('Failed to load tickets. Please try again later.');
      } finally {
        setIsLoading(false);
      }
    };

    loadTickets();
  }, []);

  if (isLoading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <div>
      <h1>My Tickets</h1>
      {tickets.length === 0 ? (
        <p>You haven't purchased any tickets yet.</p>
      ) : (
        tickets.map((ticket) => (
          <div key={ticket.id}>
            <h2>{ticket.eventName}</h2>
            <p>Purchased on: {new Date(ticket.purchaseDate).toLocaleDateString()}</p>
          </div>
        ))
      )}
    </div>
  );
};

export default UserDashboard;
