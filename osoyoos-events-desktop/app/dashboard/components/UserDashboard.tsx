import React, { useEffect, useState } from 'react';
import { fetchUserTickets } from '../../../services/api';

interface Ticket {
  id: number;
  eventName: string;
  purchaseDate: string;
}

const UserDashboard: React.FC = () => {
  const [tickets, setTickets] = useState<Ticket[]>([]);

  useEffect(() => {
    const loadTickets = async () => {
      try {
        const userTickets = await fetchUserTickets();
        setTickets(userTickets);
      } catch (error) {
        console.error('Failed to fetch user tickets:', error);
      }
    };

    loadTickets();
  }, []);

  return (
    <div>
      <h1>My Tickets</h1>
      {tickets.map((ticket) => (
        <div key={ticket.id}>
          <h2>{ticket.eventName}</h2>
          <p>Purchased on: {new Date(ticket.purchaseDate).toLocaleDateString()}</p>
        </div>
      ))}
    </div>
  );
};

export default UserDashboard;
