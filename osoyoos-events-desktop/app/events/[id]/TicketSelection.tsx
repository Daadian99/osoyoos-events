'use client'

import React, { useState, useEffect, useMemo, useRef, useCallback } from 'react';

// Custom Tooltip component
const Tooltip: React.FC<{ content: string; children: React.ReactNode }> = ({ content, children }) => (
  <div className="relative group inline-block">
    {children}
    <span className="absolute hidden group-hover:inline-block bg-gray-800 text-white text-xs rounded p-2 -mt-2 ml-2">
      {content}
    </span>
  </div>
);

interface TicketType {
  id: number;
  name: string;
  price: string;
  capacity: number;
  includeFeeInPrice: boolean;
}

const TicketSelection: React.FC<{ eventId: number }> = ({ eventId }) => {
  const [ticketTypes, setTicketTypes] = useState<TicketType[]>([]);
  const [quantities, setQuantities] = useState<{ [key: number]: number }>({});
  const [groupName, setGroupName] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchTicketTypes = async () => {
      try {
        const response = await fetch(`http://osoyoos-events.localhost/events/${eventId}/ticket-types`);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        setTicketTypes(data);
        const initialQuantities = data.reduce((acc: { [key: number]: number }, ticket: TicketType) => {
          return { ...acc, [ticket.id]: 0 };
        }, {});
        setQuantities(initialQuantities);
      } catch (e) {
        console.error("Error fetching ticket types:", e);
        setError("Failed to load ticket types");
      }
    };

    fetchTicketTypes().finally(() => setIsLoading(false));
  }, [eventId]);

  const handleQuantityChange = (ticketId: number, newQuantity: number) => {
    const ticket = ticketTypes.find(t => t.id === ticketId);
    if (ticket && newQuantity <= ticket.capacity) {
      setQuantities(prev => ({ ...prev, [ticketId]: newQuantity }));
    } else {
      alert(`Sorry, only ${ticket?.capacity} tickets are available for this type.`);
    }
  };

  const handleGroupNameChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setGroupName(e.target.value);
  };

  const isValidPurchase = useMemo(() => {
    return ticketTypes.every(ticket => {
      const quantity = quantities[ticket.id] || 0;
      return quantity <= ticket.capacity;
    }) && Object.values(quantities).some(q => q > 0);
  }, [ticketTypes, quantities]);

  const handlePurchase = async () => {
    if (!isValidPurchase) {
      setError("Please check your ticket selection. Ensure you've selected at least one ticket and haven't exceeded the available capacity.");
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const response = await fetch(`http://osoyoos-events.localhost/events/${eventId}/purchase`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify({
          eventId,
          tickets: quantities,
          groupName
        })
      });

      if (!response.ok) {
        throw new Error('Failed to purchase tickets');
      }

      const result = await response.json();
      console.log('Purchase successful:', result);
      // TODO: Show success message and clear selection
      setQuantities({});
      setGroupName('');
    } catch (err: unknown) {
      if (err instanceof Error) {
        setError(err.message);
      } else {
        setError('An unknown error occurred');
      }
    } finally {
      setIsLoading(false);
    }
  };

  const calculateFee = (price: number) => {
    const percentageFee = price * 0.05; // 5% fee
    const minimumFee = 0.50; // $0.50 minimum fee
    return Math.max(percentageFee, minimumFee);
  };

  const { subtotal, fees, total } = useMemo(() => {
    return ticketTypes.reduce((acc, ticket) => {
      const quantity = quantities[ticket.id] || 0;
      const ticketPrice = Number(ticket.price);
      const ticketFee = calculateFee(ticketPrice);
      const displayPrice = ticket.includeFeeInPrice ? ticketPrice : ticketPrice + ticketFee;
      const ticketSubtotal = quantity * displayPrice;
      const ticketFees = quantity * ticketFee;
      return {
        subtotal: acc.subtotal + (ticket.includeFeeInPrice ? ticketSubtotal - ticketFees : ticketSubtotal),
        fees: acc.fees + ticketFees,
        total: acc.total + ticketSubtotal
      };
    }, { subtotal: 0, fees: 0, total: 0 });
  }, [ticketTypes, quantities]);

  const selectRefs = useRef<{ [key: number]: HTMLSelectElement | null }>({});

  const setSelectRef = useCallback((el: HTMLSelectElement | null, id: number) => {
    selectRefs.current[id] = el;
  }, []);

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      Object.values(selectRefs.current).forEach(select => {
        if (select && !select.contains(event.target as Node)) {
          select.size = 1;
          select.classList.remove('selectOpen');
        }
      });
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, []);

  const handleSelectMouseDown = (e: React.MouseEvent<HTMLSelectElement>) => {
    const select = e.currentTarget;
    if (select.size === 1) {
      e.preventDefault();
    }
  };

  const handleSelectClick = (e: React.MouseEvent<HTMLSelectElement>) => {
    const select = e.currentTarget;
    if (select.size === 1) {
      select.classList.add('selectOpen');
      select.size = 5; // Show 5 options when open
    } else {
      select.classList.remove('selectOpen');
      select.size = 1;
    }
  };

  if (isLoading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <div className="bg-white shadow-md rounded-lg p-6">
      <h2 className="text-2xl font-bold mb-6">Select Tickets</h2>
      <div className="mb-6">
        <label htmlFor="groupName" className="block mb-2">Group Name:</label>
        <div className="flex items-center">
          <input 
            type="text" 
            id="groupName" 
            value={groupName} 
            onChange={handleGroupNameChange}
            className="p-2 border rounded w-full"
          />
          <Tooltip content="People with the same group name will be seated together">
            <span className="ml-2 text-blue-500">ⓘ</span>
          </Tooltip>
        </div>
      </div>

      <div className="mb-6">
        <h3 className="text-xl font-semibold mb-2">Ticket Types</h3>
        {ticketTypes.map(ticket => (
          <div key={ticket.id} className="flex items-center justify-between mb-2 p-2 bg-gray-100 rounded">
            <div>
              <h4 className="font-medium">{ticket.name}</h4>
              <p className="text-sm">Price: ${ticket.price}</p>
            </div>
            <div>
              <label htmlFor={`quantity-${ticket.id}`} className="mr-2 text-sm">Qty:</label>
              <select
                id={`quantity-${ticket.id}`}
                value={quantities[ticket.id] || 0}
                onChange={(e) => handleQuantityChange(ticket.id, parseInt(e.target.value))}
                className="p-1 border rounded compact-select"
              >
                {[...Array(21)].map((_, i) => (
                  <option key={i} value={i}>{i}</option>
                ))}
              </select>
            </div>
          </div>
        ))}
      </div>

      <div className="mb-4 text-right">
        <p className="text-lg">Subtotal: ${subtotal.toFixed(2)}</p>
        {fees > 0 && <p className="text-lg">Fees: ${fees.toFixed(2)}</p>}
        <p className="text-xl font-bold">Total: ${total.toFixed(2)}</p>
      </div>

      {error && <p className="text-red-500 mb-4">{error}</p>}

      <button 
        onClick={handlePurchase} 
        disabled={!isValidPurchase || isLoading}
        className={`w-full font-bold py-2 px-4 rounded ${
          isValidPurchase && !isLoading
            ? 'bg-blue-500 hover:bg-blue-700 text-white' 
            : 'bg-gray-300 text-gray-500 cursor-not-allowed'
        }`}
      >
        {isLoading ? 'Processing...' : 'Purchase Tickets'}
      </button>
    </div>
  );
};

export default TicketSelection;
