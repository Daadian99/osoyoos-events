'use client';

import { useEffect, useState } from 'react';
import { useAuth } from '../AuthContext';
import { useRouter } from 'next/navigation';
import ProtectedRoute from '../ProtectedRoute';
import UserDashboard from './components/UserDashboard';
import OrganizerDashboard from './components/OrganizerDashboard';
import AdminDashboard from './components/AdminDashboard';

const Dashboard = () => {
  const { user, logout } = useAuth();
  const router = useRouter();
  const [userName, setUserName] = useState('');

  useEffect(() => {
    const fetchUserDetails = async () => {
      try {
        const response = await fetch('http://osoyoos-events.localhost/user', {
          headers: {
            'Authorization': `Bearer ${user?.token}`
          }
        });
        if (response.ok) {
          const userData = await response.json();
          setUserName(userData.name);
        }
      } catch (error) {
        console.error('Failed to fetch user details', error);
      }
    };

    if (user) {
      fetchUserDetails();
    }
  }, [user]);

  const handleLogout = () => {
    logout();
    router.push('/login');
  };

  const renderDashboard = () => {
    switch (user?.role) {
      case 'user':
        return <UserDashboard />;
      case 'organizer':
        return <OrganizerDashboard />;
      case 'admin':
        return <AdminDashboard />;
      default:
        return <p>Invalid user role</p>;
    }
  };

  return (
    <ProtectedRoute>
      <div className="container mx-auto p-4">
        <h1 className="text-2xl font-bold mb-4">Welcome to your Dashboard, {userName}</h1>
        <button 
          onClick={handleLogout}
          className="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded mb-4"
        >
          Logout
        </button>
        {renderDashboard()}
      </div>
    </ProtectedRoute>
  );
};

export default Dashboard;
