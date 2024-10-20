'use client'

import React, { useState, useEffect } from 'react';
import { fetchEvents } from '../../services/api';

interface Event {
  id: number;
  name: string;
  date: string;
}

const EventsPage: React.FC = () => {
  const [events, setEvents] = useState<Event[]>([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterDate, setFilterDate] = useState('');

  useEffect(() => {
    const loadEvents = async () => {
      try {
        const fetchedEvents = await fetchEvents();
        setEvents(fetchedEvents);
      } catch (error) {
        console.error('Failed to fetch events:', error);
      }
    };

    loadEvents();
  }, []);

  const filteredEvents = events.filter(event => 
    event.name.toLowerCase().includes(searchTerm.toLowerCase()) &&
    (filterDate ? event.date.includes(filterDate) : true)
  );

  return (
    <div>
      <h1>Events</h1>
      <input 
        type="text" 
        placeholder="Search events" 
        value={searchTerm}
        onChange={(e) => setSearchTerm(e.target.value)}
      />
      <input 
        type="date" 
        value={filterDate}
        onChange={(e) => setFilterDate(e.target.value)}
      />
      {filteredEvents.map((event) => (
        <div key={event.id}>
          <h2>{event.name}</h2>
          <p>Date: {new Date(event.date).toLocaleDateString()}</p>
        </div>
      ))}
    </div>
  );
};

export default EventsPage;
