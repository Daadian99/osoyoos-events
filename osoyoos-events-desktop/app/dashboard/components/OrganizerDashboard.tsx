'use client';
import React, { useEffect, useState } from 'react';
import EventForm from '../../components/EventForm';

interface Event {
  id: number;
  title: string;
  date: string;
  ticketsSold: number;
  revenue: number;
  organizer_presentation_phrase: string;
}

const OrganizerDashboard: React.FC = () => {
  const [events, setEvents] = useState<Event[]>([]);
  const [isCreatingEvent, setIsCreatingEvent] = useState(false);
  const [editingEventId, setEditingEventId] = useState<number | null>(null);

  useEffect(() => {
    loadEvents();
  }, []);

  const loadEvents = async () => {
    try {
      const response = await fetch('http://osoyoos-events.localhost/events?organizer=true', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });
      if (!response.ok) {
        throw new Error('Failed to fetch organizer events');
      }
      const organizerEvents = await response.json();
      setEvents(organizerEvents.events);
    } catch (error) {
      console.error('Failed to fetch organizer events:', error);
    }
  };

  const handleCreateEvent = async (formData: any) => {
    try {
      const response = await fetch('http://osoyoos-events.localhost/events', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify(formData)
      });

      if (!response.ok) {
        throw new Error('Failed to create event');
      }

      setIsCreatingEvent(false);
      loadEvents();
    } catch (error) {
      console.error('Error creating event:', error);
    }
  };

  const handleUpdateEvent = async (formData: any) => {
    if (!editingEventId) return;

    try {
      const response = await fetch(`http://osoyoos-events.localhost/events/${editingEventId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify(formData)
      });

      if (!response.ok) {
        throw new Error('Failed to update event');
      }

      setEditingEventId(null);
      loadEvents();
    } catch (error) {
      console.error('Error updating event:', error);
    }
  };

  return (
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold mb-6">Organizer Dashboard</h1>
      
      <button
        onClick={() => setIsCreatingEvent(true)}
        className="mb-4 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
      >
        Create New Event
      </button>

      {isCreatingEvent && (
        <div className="mb-6">
          <h2 className="text-2xl font-bold mb-4">Create New Event</h2>
          <EventForm onSubmit={handleCreateEvent} />
        </div>
      )}

      {editingEventId && (
        <div className="mb-6">
          <h2 className="text-2xl font-bold mb-4">Edit Event</h2>
          <EventForm eventId={editingEventId} onSubmit={handleUpdateEvent} />
        </div>
      )}

      <h2 className="text-2xl font-bold mb-4">My Events</h2>
      {events.map((event) => (
        <div key={event.id} className="mb-4 p-4 border rounded">
          <h3 className="text-xl font-semibold">{event.title}</h3>
          <p>Date: {new Date(event.date).toLocaleDateString()}</p>
          <p>Tickets Sold: {event.ticketsSold}</p>
          <p>Revenue: ${event.revenue.toFixed(2)}</p>
          <p>Presentation Phrase: {event.organizer_presentation_phrase}</p>
          <button
            onClick={() => setEditingEventId(event.id)}
            className="mt-2 bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-2 rounded"
          >
            Edit
          </button>
        </div>
      ))}
    </div>
  );
};

export default OrganizerDashboard;
