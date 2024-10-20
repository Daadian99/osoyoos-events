// app/events/[id]/page.tsx
'use client';

import { useState, useEffect } from 'react';
import { useParams } from 'next/navigation';
import TicketSelection from './TicketSelection'; // Import the TicketSelection component

interface EventDetails {
  id: number;
  title: string;
  description: string;
  date: string;
  location: string;
  organizer_id: number;
  organizer_name: string | null;
  show_organizer: boolean;
  organizer_presentation_phrase: string;
}

const EventDetailsPage = () => {
  const { id } = useParams();
  const [eventDetails, setEventDetails] = useState<EventDetails | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchEventDetails = async () => {
      setIsLoading(true);
      setError(null);
      try {
        const response = await fetch(`http://osoyoos-events.localhost/events/${id}`);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        setEventDetails(data);
      } catch (e) {
        console.error("Error fetching event details:", e);
        setError("Failed to load event details");
      } finally {
        setIsLoading(false);
      }
    };

    if (id) {
      fetchEventDetails();
    }
  }, [id]);

  if (isLoading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;
  if (!eventDetails) return <div>No event details found</div>;

  return (
    <div className="max-w-4xl mx-auto mt-8 p-6">
      <div className="mb-8">
        <h1 className="text-3xl font-bold mb-2">{eventDetails.title}</h1>
        {eventDetails.show_organizer && eventDetails.organizer_name && (
          <p className="text-xl mb-2">
            {eventDetails.organizer_presentation_phrase} {eventDetails.organizer_name}
          </p>
        )}
        <p className="text-lg mb-2">
          {new Date(eventDetails.date).toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric', 
            hour: 'numeric', 
            minute: 'numeric',
            timeZone: 'UTC'
          })}
        </p>
        <p className="text-lg mb-2">{eventDetails.location}</p>
        <p className="text-lg">{eventDetails.description}</p>
      </div>
      <TicketSelection eventId={eventDetails.id} />
    </div>
  );
};

export default EventDetailsPage;
