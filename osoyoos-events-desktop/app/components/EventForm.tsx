'use client';
import React, { useState, useEffect } from 'react';

interface EventFormData {
  title: string;
  description: string;
  date: string;
  location_id: string;
  organizer_presentation_phrase: string;
}

interface Location {
  id: string;
  name: string;
}

interface EventFormProps {
  eventId?: number;
  onSubmit: (formData: EventFormData) => void;
}

const EventForm: React.FC<EventFormProps> = ({ eventId, onSubmit }) => {
  const [formData, setFormData] = useState<EventFormData>({
    title: '',
    description: '',
    date: '',
    location_id: '',
    organizer_presentation_phrase: 'Presented by',
  });
  const [locations, setLocations] = useState<Location[]>([]);

  useEffect(() => {
    // Fetch locations
    const fetchLocations = async () => {
      try {
        const response = await fetch('http://osoyoos-events.localhost/locations');
        if (!response.ok) {
          throw new Error('Failed to fetch locations');
        }
        const data = await response.json();
        setLocations(data.locations);
      } catch (error) {
        console.error('Error fetching locations:', error);
      }
    };

    fetchLocations();

    // If eventId is provided, fetch event details
    if (eventId) {
      const fetchEventDetails = async () => {
        try {
          const response = await fetch(`http://osoyoos-events.localhost/events/${eventId}`);
          if (!response.ok) {
            throw new Error('Failed to fetch event details');
          }
          const eventData = await response.json();
          setFormData({
            title: eventData.title,
            description: eventData.description,
            date: eventData.date.split('T')[0], // Assuming the date is in ISO format
            location_id: eventData.location_id,
            organizer_presentation_phrase: eventData.organizer_presentation_phrase || 'Presented by',
          });
        } catch (error) {
          console.error('Error fetching event details:', error);
        }
      };

      fetchEventDetails();
    }
  }, [eventId]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFormData(prevData => ({
      ...prevData,
      [name]: value,
    }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit(formData);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div>
        <label htmlFor="title" className="block text-sm font-medium text-gray-700">Title</label>
        <input
          type="text"
          id="title"
          name="title"
          value={formData.title}
          onChange={handleChange}
          required
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
        />
      </div>

      <div>
        <label htmlFor="description" className="block text-sm font-medium text-gray-700">Description</label>
        <textarea
          id="description"
          name="description"
          value={formData.description}
          onChange={handleChange}
          required
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
        />
      </div>

      <div>
        <label htmlFor="date" className="block text-sm font-medium text-gray-700">Date</label>
        <input
          type="date"
          id="date"
          name="date"
          value={formData.date}
          onChange={handleChange}
          required
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
        />
      </div>

      <div>
        <label htmlFor="location_id" className="block text-sm font-medium text-gray-700">Location</label>
        <select
          id="location_id"
          name="location_id"
          value={formData.location_id}
          onChange={handleChange}
          required
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
        >
          <option value="">Select a location</option>
          {locations.map(location => (
            <option key={location.id} value={location.id}>{location.name}</option>
          ))}
        </select>
      </div>

      <div>
        <label htmlFor="organizer_presentation_phrase" className="block text-sm font-medium text-gray-700">Organizer Presentation Phrase</label>
        <input
          type="text"
          id="organizer_presentation_phrase"
          name="organizer_presentation_phrase"
          value={formData.organizer_presentation_phrase}
          onChange={handleChange}
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
        />
      </div>

      <button
        type="submit"
        className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        {eventId ? 'Update Event' : 'Create Event'}
      </button>
    </form>
  );
};

export default EventForm;
