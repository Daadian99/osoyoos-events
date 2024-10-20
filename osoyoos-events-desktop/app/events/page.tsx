'use client';
import React, { useState, useEffect } from 'react';
import Link from 'next/link';

interface Event {
  id: number;
  title: string;
  description: string;
  date: string;
  location: string;
  organizer_name?: string;
  show_organizer: boolean;
  organizer_presentation_phrase: string;
}

const EventsPage: React.FC = () => {
  const [events, setEvents] = useState<Event[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const loadEvents = async () => {
      try {
        console.log('Fetching events...');
        const response = await fetch('http://osoyoos-events.localhost/events');
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        const text = await response.text();
        console.log('Raw response:', text);

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        let data;
        try {
          data = JSON.parse(text);
        } catch (error) {
          console.error('Failed to parse JSON:', error);
          throw new Error('Invalid JSON response from server');
        }
        
        console.log('Parsed data:', data);
        
        if (Array.isArray(data.events)) {
          setEvents(data.events);
        } else {
          console.error('Unexpected data structure:', data);
          setError('Unexpected data structure received from server');
        }
      } catch (error: any) {
        console.error('Failed to fetch events:', error);
        setError(`Failed to load events: ${error.message}`);
      } finally {
        setIsLoading(false);
      }
    };

    loadEvents();
  }, []);

  if (isLoading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold mb-6">Upcoming Events</h1>
      {events.length === 0 ? (
        <p>No upcoming events available at the moment.</p>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {events.map((event) => (
            <Link href={`/events/${event.id}`} key={event.id}>
              <div className="border rounded-lg p-4 hover:shadow-lg transition duration-300">
                <h2 className="text-xl font-semibold mb-2">{event.title}</h2>
                {event.show_organizer && event.organizer_name && (
                  <p className="text-sm text-gray-600 mb-2">
                    {event.organizer_presentation_phrase} {event.organizer_name}
                  </p>
                )}
                <p className="text-gray-600 mb-2">{event.description}</p>
                <p className="text-sm text-gray-500">
                  {new Date(event.date).toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric',
                  })}
                </p>
                <p className="text-sm text-gray-500">{event.location}</p>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
};

export default EventsPage;
