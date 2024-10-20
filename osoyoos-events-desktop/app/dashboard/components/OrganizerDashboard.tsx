import React, { useEffect, useState } from 'react';
import { fetchOrganizerEvents } from '../../../services/api';

interface Event {
  id: number;
  name: string;
  date: string;
  ticketsSold: number;
  revenue: number;
}

const OrganizerDashboard: React.FC = () => {
  const [events, setEvents] = useState<Event[]>([]);

  useEffect(() => {
    const loadEvents = async () => {
      try {
        const organizerEvents = await fetchOrganizerEvents();
        setEvents(organizerEvents);
      } catch (error) {
        console.error('Failed to fetch organizer events:', error);
      }
    };

    loadEvents();
  }, []);

  return (
    <div>
      <h1>My Events</h1>
      {events.map((event) => (
        <div key={event.id}>
          <h2>{event.name}</h2>
          <p>Date: {new Date(event.date).toLocaleDateString()}</p>
          <p>Tickets Sold: {event.ticketsSold}</p>
          <p>Revenue: ${event.revenue.toFixed(2)}</p>
        </div>
      ))}
    </div>
  );
};

export default OrganizerDashboard;
