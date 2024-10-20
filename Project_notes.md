# Osoyoos Event Ticketing System - Project Notes

## Project Structure and Goals

1. Separate Interfaces:
   - Desktop Web Interface (Current Focus): osoyoos-events-desktop
   - Mobile Web Interface (Future): osoyoos-events-mobile
   - Native Mobile Apps (Future): To be developed after web interfaces

2. Desktop Interface Focus:
   - Optimize for wider screens
   - No need for mobile responsiveness in the desktop version

3. API-First Approach:
   - Develop a robust API to support all interfaces and future mobile apps

## Current Project Status (as of 2024-10-21)
- All core functionalities are implemented and working:
  - User authentication (registration, login) with JWT
  - Full CRUD operations for Events
  - Full CRUD operations for Tickets
  - Ticket purchasing feature
  - Role-based access control
  - Ticket cancellation feature
- API documentation is available through Swagger UI
- Error logging and debugging practices have been improved
- Unit tests have been implemented and are passing (14 tests, 35 assertions)

## Recent Achievements
1. Fixed EmailService class not found error in index.php
2. Successfully implemented and tested login functionality
3. Implemented and verified ticket cancellation feature
4. Completed ticket purchasing feature with all necessary checks and validations
5. Implemented unit tests for Event and Ticket models
6. Resolved issues with mocking in Ticket model tests
7. Successfully implemented ticket type fetching in the frontend
8. Updated ticket quantity selection to allow for 0 tickets
9. Updated the TicketSelection component to display event details in a more user-friendly format
10. Implemented date formatting using date-fns library
11. Implemented total price calculation in the TicketSelection component
12. Adjusted strategy to use immediate split payments instead of batch payouts
13. Implemented organizer choice for fee display in ticket prices
14. Implemented ticket quantity validation to prevent overbooking
15. Added client-side validation for purchase submissions
16. Implemented backend API endpoint for ticket purchases
17. Created frontend functionality to submit purchases
18. Added loading state and error handling for purchase submissions
19. Completed and tested the full ticket purchase flow, including frontend and backend integration
20. Implemented proper error handling and user feedback for the purchase process
21. Successfully debugged and resolved server configuration issues
22. Optimized .htaccess file for better performance and security
23. Improved error logging and debugging practices
24. Verified functionality of all existing API endpoints

## Key Components and Their Status
1. UserController & User Model: Fully functional for registration and login
2. EventController & Event Model: CRUD operations working as expected, unit tests passing
3. TicketController & Ticket Model: All CRUD operations verified and working, including ticket purchasing and cancellation, unit tests passing
4. JwtAuthMiddleware: Correctly authenticating users
5. RoleMiddleware: Properly enforcing role-based access control
6. EmailService: Integrated and functional (ensure SMTP settings are correct in .env)

## API Endpoints
- POST /register: User registration
- POST /login: User login, returns JWT token
- GET /events: List all events
- POST /events: Create a new event (protected)
- GET /events/{id}: Get a specific event
- PUT /events/{id}: Update an event (protected)
- DELETE /events/{id}: Delete an event (protected)
- GET /events/{eventId}/tickets: List all tickets for an event
- POST /events/{eventId}/tickets: Create a new ticket for an event (protected)
- GET /events/{eventId}/tickets/{ticketId}: Get a specific ticket
- PUT /events/{eventId}/tickets/{ticketId}: Update a ticket (protected)
- DELETE /events/{eventId}/tickets/{ticketId}: Delete a ticket (protected)
- POST /events/{eventId}/tickets/{ticketId}/purchase: Purchase a ticket (protected)
- GET /user/tickets: Get user's purchased tickets (protected)
- DELETE /user/tickets/{purchaseId}: Cancel a ticket purchase (protected)
- GET /user/ticket-history: Get user's ticket purchase history (protected)

## Next Steps
1. Begin frontend development (priority for next session)
2. Implement pagination for listing endpoints (events, tickets)
3. Enhance user management (profile management, password reset)
4. Implement event search and filtering
5. Add support for event categories
6. Implement a global error handler for consistent error responses
7. Set up automated testing and deployment pipelines
8. Consider implementing integration tests

## Reminders
- Always apply JwtAuthMiddleware before RoleMiddleware in protected routes
- Keep OpenAPI documentation updated as you modify or add endpoints
- Regularly check for PHP and dependency updates
- Never commit .env file to version control
- When suggesting changes, always check existing code first to avoid redundancy
- Always check the existing implementation before suggesting changes to avoid redundancy
- Remember that the project is using Slim 4, and adjust all API response handling accordingly

## Development Environment
- Windows system
- Use Windows Command Prompt compatible commands for testing
- For JSON data in curl commands, use escaped double quotes

## File Structure
[Abbreviated for brevity. Full structure available in folderstructure.txt]

C:.
│   .env
│   .env.example
│   .gitignore
│   composer.json
│   composer.lock
│   osoyoos_events.sql
│   Project_notes.md
│   README.md
│
├───public
│       .htaccess
│       index.php
│       swagger-ui.html
│       swagger.php
│
├───src
│   │   OpenApiConfig.php
│   │   OpenApiDefinitions.php
│   │
│   ├───Controllers
│   │       EventController.php
│   │       TicketController.php
│   │       UserController.php
│   │
│   ├───Middleware
│   │       JwtAuthMiddleware.php
│   │       RoleMiddleware.php
│   │
│   └───Models
│           Event.php
│           Ticket.php
│           User.php
│
├───logs
│       php-error.log
│       direct-log.txt
│
├───tests
└───vendor
    [... vendor contents ...]

## Database Schema
[No changes to the schema in this update]

## Key Components
1. EventController: Handles event-related operations (create, read, update, delete)
2. Event Model: Interacts with the database for event operations
3. UserController: Manages user registration and login
4. User Model: Handles user-related database operations
5. JwtAuthMiddleware: Authenticates users using JWT
6. RoleMiddleware: Enforces role-based access control
7. TicketController: Manages full CRUD operations for tickets
8. Ticket Model: Handles all ticket-related database operations including create, read, update, and delete

## API Endpoints
[Previous endpoints remain the same, with the addition of:]
- PUT /events/{eventId}/tickets/{ticketId}: Update an existing ticket (protected)
- DELETE /events/{eventId}/tickets/{ticketId}: Delete a ticket (protected)

## Recent Challenges and Solutions
1. JWT Authentication Issues:
   - Challenge: User was not being authenticated properly in the ticket creation process.
   - Solution: Added more detailed logging in JwtAuthMiddleware and ensured proper order of middleware application.

2. Role-based Access Control:
   - Challenge: RoleMiddleware was not receiving the authenticated user information.
   - Solution: Adjusted middleware order to ensure JWT authentication occurs before role checking.

3. Error Logging:
   - Challenge: Difficulty in identifying the source of authentication and authorization errors.
   - Solution: Implemented comprehensive logging throughout the authentication process and in key controller methods.

4. Event Existence Verification:
   - Challenge: Attempted to create tickets for non-existent events.
   - Solution: Added checks in the TicketController to verify event existence before ticket creation.

5. Ticket Update and Delete Operations:
   - Challenge: Ensuring proper authorization and validation for ticket modifications.
   - Solution: Implemented checks for event existence, ticket ownership, and user roles before allowing updates or deletions.

6. Unit Testing for Ticket Model:
   - Challenge: Mocking PDO and PDOStatement for complex database operations in tests.
   - Solution: Adjusted test setup to properly mock database interactions and match the actual implementation of methods like `purchaseTicket`.

7. Ticket Type Fetching:
   - Challenge: The API endpoint for fetching ticket types was returning an error due to the use of `withJson()` method in Slim 4.
   - Solution: Updated the `EventController` to use the correct method for sending JSON responses in Slim 4:
     ```php
     $response->getBody()->write(json_encode($data));
     return $response
         ->withHeader('Content-Type', 'application/json')
         ->withStatus(200);
     ```

8. Ticket Quantity Default Value:
   - Challenge: Ticket quantities were defaulting to 1 instead of 0.
   - Solution: Updated the `fetchTicketTypes` function to initialize all quantities to 0, and modified the select element to use the nullish coalescing operator for defaulting to 0.

9. TypeScript Error Handling:
    - Challenge: The catch block in the purchase function was causing a TypeScript error due to the implicit 'any' type of the caught error.
    - Solution: Implemented proper error typing and checking in the catch block to ensure type safety and provide meaningful error messages.

## Debugging Practices
1. Use detailed error logging in middleware and controllers.
2. Implement step-by-step logging to trace the flow of requests through the application.
3. Use curl commands for testing API endpoints and debugging.
4. Regularly check and analyze the contents of php-error.log and direct-log.txt.
5. When encountering API errors, always check the PHP error logs and the response from direct API calls before making changes to the frontend.

## Notes for Future Development
- Always apply the JWT middleware before role-based middleware in protected routes.
- Regularly review and update the OpenAPI documentation as new endpoints are added or modified.
- Maintain comprehensive error logging to facilitate debugging.
- Consider implementing a more robust logging solution like Monolog in the future.
- Regularly backup the database and keep the schema updated in version control.
- As the project grows, consider implementing a caching layer to improve performance.
- Keep security at the forefront: regularly update dependencies and conduct security audits.
- As we move to frontend development, consider implementing a state management solution (e.g., Redux for React) to handle complex application state.
- Plan for responsive design from the start of frontend development to ensure mobile-friendliness.
- When implementing new features or fixing bugs, always refer to these project notes to avoid repeating past mistakes or redundant implementations.
- Regularly update these project notes with new information, challenges faced, and solutions implemented.

## Development Environment Notes
- The project is being developed on a Windows system.
- When providing curl commands or any shell commands, ensure they are Windows Command Prompt compatible.
- Avoid using single quotes for JSON data in curl commands. Use escaped double quotes instead.

## Current Status
- Backend development is largely complete with all planned features implemented and tested.
- Unit tests are in place and passing for core functionalities.
- The project is ready to move into the frontend development phase.
- Frontend development is progressing, with the TicketSelection component now correctly defaulting ticket quantities to 0 and allowing selection from 0 to 20 tickets.

## Next Session Focus
- Continue refining the TicketSelection component:
  - Implement total price calculation based on selected ticket quantities
  - Add validation to ensure users can't purchase more tickets than available capacity
  - Implement the purchase functionality, integrating with the backend API
- Begin work on the event listing and details pages
- Implement user authentication in the frontend

## Dependencies Added
- date-fns: For advanced date formatting in the frontend

## Recent Achievements
[Add to the existing list]
10. Implemented total price calculation in the TicketSelection component

## Current Status
[Update or add]
- The TicketSelection component now calculates and displays the total price based on selected ticket quantities

## Next Session Focus
1. Implement validation for ticket purchases:
   - Add checks to ensure users can't select more tickets than available capacity
   - Implement client-side validation before submitting the purchase
2. Create the purchase functionality:
   - Develop a function to handle the ticket purchase process
   - Integrate with the backend API to submit the purchase
3. Improve user feedback:
   - Add loading states during API calls
   - Implement error handling and display error messages to the user
4. Enhance the UI:
   - Add a summary section showing the selected tickets and total price
   - Improve the overall styling and responsiveness of the component
5. Begin work on the event listing page:
   - Create a new component to display a list of available events
   - Implement pagination or infinite scrolling for the event list

## Reminders
- Ensure all new functionality is thoroughly tested
- Keep accessibility in mind when implementing new UI features
- Update the API documentation if any new endpoints are required for the purchase functionality

## Recent Challenges and Solutions
[Add to the existing list]
9. TypeScript Error in Total Price Calculation:
   - Challenge: The editor was showing an error for `ticket.price` in the total price calculation.
   - Solution: Updated the `TicketType` interface to explicitly type `price` as a string or number, and used `Number(ticket.price)` in the calculation to handle both cases.

## Reminders
[Add to the existing list]
- When working with data from the API, ensure that the TypeScript interfaces accurately reflect the structure of the returned data.
- Use `Number()` instead of `parseFloat()` or `parseInt()` when converting string numbers to ensure compatibility with both string and number types.

## Monetization Strategy Considerations
- Implemented a transparent fee structure for paid events:
  1. Free tier for organizers creating only free events
  2. Per-ticket fee for paid events (e.g., 5% of ticket price or $0.50, whichever is higher)
  3. Fees are clearly displayed to customers during purchase
- Added flexibility for organizers to choose whether to include fees in ticket prices or display them separately
- System calculates and tracks fees regardless of display preference, ensuring platform revenue

## PayPal Integration Considerations
- Use PayPal's split payment feature to divide funds between platform and organizer immediately
- Organizers handle their own refunds, reducing platform liability
- Fees are collected at the time of purchase, not in batches

## Recent Achievements
[Add to the existing list]
11. Implemented detailed fee calculation and display in the TicketSelection component
12. Adjusted strategy to use immediate split payments instead of batch payouts

## Next Steps
1. Implement backend support for fee calculation and PayPal split payments
2. Integrate PayPal SDK for split payment processing
3. Create an admin interface for managing fee structures
4. Develop a clear pricing page to communicate the fee structure to users
5. Implement analytics to track usage and revenue for different event types

## Reminders
- Ensure the PayPal integration properly handles split payments
- Clearly communicate to organizers that they are responsible for handling refunds
- Regularly review and adjust the fee structure based on user feedback and platform growth
- Maintain transparency in fee communication to both organizers and ticket buyers

## Next Steps
[Add to the existing list]
6. Update event creation/editing interface to allow organizers to set fee display preference
7. Modify backend API to store and return fee display preference for each event/ticket type

## Reminders
[Add to the existing list]
- Ensure clear communication to organizers about fee structure and display options
- Consider providing guidance to organizers on pros and cons of including fees in ticket prices

## Recent Achievements
[Add to the existing list]
14. Implemented ticket quantity validation to prevent overbooking
15. Added client-side validation for purchase submissions

## Current Status
- The TicketSelection component now includes validation to ensure users can't select more tickets than available
- The purchase button is disabled when the selection is invalid

## Next Session Focus
1. Implement the purchase functionality:
   - Create a new API endpoint for submitting purchases
   - Develop the frontend function to call this API
   - Handle successful purchases (e.g., show confirmation, clear selection)
2. Improve user feedback:
   - Add loading states during API calls
   - Implement error handling and display error messages to the user
3. Begin integration with PayPal for payment processing

## Reminders
- Ensure all validations are also implemented on the server-side
- Consider adding a "max tickets per purchase" limit if needed
- Test thoroughly with various scenarios (e.g., multiple users trying to book simultaneously)

## Recent Achievements
[Add to the existing list]
16. Implemented backend API endpoint for ticket purchases
17. Created frontend functionality to submit purchases
18. Added loading state and error handling for purchase submissions

## Current Status
- The TicketSelection component now includes a functional purchase button that interacts with the backend
- Basic error handling and loading states have been implemented
- The backend now has a route and logic to handle ticket purchases

## Next Session Focus
1. Implement PayPal integration for payment processing
2. Add success feedback after successful purchases (e.g., confirmation message, redirect to receipt page)
3. Implement more robust error handling, including specific error messages for different scenarios
4. Begin work on a user dashboard to view purchased tickets

## Reminders
- Ensure all API endpoints are properly secured and validated
- Implement proper logging for purchases and errors
- Consider implementing a queue system for high-traffic events to prevent overbooking
- Test the purchase flow thoroughly, including edge cases and error scenarios

## Recent Achievements
[Add to the existing list]
19. Completed and tested the full ticket purchase flow, including frontend and backend integration
20. Implemented proper error handling and user feedback for the purchase process

## Current Status
- The ticket purchase functionality is now fully implemented and tested, including frontend-backend integration and error handling
- The project is ready for PayPal integration and further refinement of the user experience

## Next Session Focus
1. Implement PayPal integration for payment processing
2. Create a user dashboard for viewing and managing purchased tickets
3. Enhance the event listing page with search and filter functionality
4. Implement an organizer dashboard for managing events and viewing sales data
5. Begin work on the admin interface for managing the platform

## Reminders
- Ensure all API endpoints are properly secured and validated
- Implement proper logging for purchases and errors
- Consider implementing a queue system for high-traffic events to prevent overbooking
- Test the purchase flow thoroughly, including edge cases and error scenarios

## Recent Achievements
[Add to the existing list]
21. Successfully debugged and resolved server configuration issues
22. Optimized .htaccess file for better performance and security
23. Improved error logging and debugging practices
24. Verified functionality of all existing API endpoints

## Current Project Status (as of 2024-10-22)
- All core functionalities remain implemented and working
- Backend API is fully functional and tested
- Frontend development is progressing, with ticket selection and purchase flow implemented
- Server configuration has been optimized for better performance and security

## Next Steps
1. Implement PayPal integration for payment processing:
   - Research PayPal SDK and API requirements
   - Implement server-side logic for handling PayPal payments
   - Create frontend components for PayPal payment flow
   - Test and verify PayPal integration thoroughly

2. Develop user dashboard:
   - Design and implement a user interface for viewing purchased tickets
   - Create API endpoints for fetching user-specific ticket data
   - Implement ticket management features (e.g., viewing QR codes, cancellation)

3. Enhance event listing page:
   - Implement search functionality
   - Add filtering options (e.g., by date, category, price range)
   - Implement pagination or infinite scrolling for better performance

4. Create organizer dashboard:
   - Design and implement interface for event management
   - Add sales data visualization
   - Implement features for managing ticket types and pricing

5. Begin development of admin interface:
   - Design and implement admin dashboard
   - Create interfaces for managing users, events, and platform settings
   - Implement analytics and reporting features

6. Improve overall user experience:
   - Refine UI/UX design across all pages
   - Implement more robust error handling and user feedback
   - Optimize performance, especially for image loading and data fetching

7. Implement advanced features:
   - Add support for recurring events
   - Implement a notification system for users and organizers
   - Create a review and rating system for events

8. Enhance security measures:
   - Implement rate limiting for API endpoints
   - Add two-factor authentication option for users
   - Conduct a thorough security audit of the entire application

## Reminders
- Continuously update API documentation as new endpoints are added or modified
- Regularly review and update dependencies for security and performance improvements
- Maintain comprehensive error logging and monitoring
- Conduct regular code reviews and refactoring sessions
- Keep the Project_notes.md file updated with new challenges faced and solutions implemented

## Development Environment Notes
- Ensure all team members are using consistent development environments
- Regularly update local databases with the latest schema changes
- Use feature branches and pull requests for all new development work

## Debugging Practices
- Utilize detailed error logging in both frontend and backend
- Implement step-by-step request tracing for complex operations
- Regularly review server logs for potential issues or optimization opportunities
- Use browser developer tools for frontend debugging and performance optimization

## Upcoming Features and Enhancements

1. Seating Chart System:
   - Allow organizers to create custom seating charts for venues
   - Implement a user interface for attendees to select specific seats

2. Enhanced Group Ticketing:
   - Improve the group option in ticket selection
   - Implement logic to match users with existing groups or create new groups
   - Consider group management features (e.g., group leader, invitations)

3. Event Creation/Editing Form:
   - Develop a comprehensive form for organizers to create and edit events
   - Include all necessary fields, including the new "Presented by" customization

4. User Profile Management:
   - Create interfaces for users to manage their profiles, preferences, and settings

5. Advanced Search and Filtering:
   - Implement robust search functionality for events
   - Add filters for categories, dates, locations, etc.

## Development Guidelines

1. Focus on desktop-specific design and functionality
2. Ensure all new features are API-driven to support future mobile development
3. Maintain clear separation between desktop and future mobile interfaces
4. Prioritize user experience for desktop users (e.g., larger screens, mouse interactions)
