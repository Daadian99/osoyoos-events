# Osoyoos Event Ticketing System - Project Notes

## Current Project Status (as of 2024-10-18)
- Basic CRUD operations for Events are implemented and working
- Authentication using JWT is functional
- Role-based access control is in place for protected routes
- Full CRUD operations for Tickets have been implemented, tested, and verified successfully
- OpenAPI/Swagger documentation has been implemented manually
- Basic API documentation is now visible through Swagger UI

## Recent Progress
- Successfully implemented and tested ticket update functionality
- Successfully implemented and tested ticket deletion functionality
- Verified correct handling of events with no tickets
- Improved error logging and debugging practices
- Updated project structure to include new files related to API documentation

## Key Learnings and Improvements
1. Middleware Order: We learned the importance of middleware order in Slim. The JWT middleware must be applied before the role middleware for proper authentication.

2. Error Logging: Implementing detailed error logging was crucial in identifying and resolving issues, particularly with authentication and database operations.

3. Environment Variables: Using .env for sensitive information like database credentials and JWT secrets has improved our security practices.

4. Input Validation: We've implemented more robust input validation, especially for ticket operations, to ensure data integrity.

5. Error Handling: We've improved our error handling to provide more meaningful responses to the client, which aids in debugging and improves user experience.

6. Database Checks: We learned the importance of checking for the existence of related entities (e.g., events) before performing operations like ticket creation, updating, or deletion.

## Project Overview
- Web-based event ticketing system for Osoyoos, BC
- Focus: Local music events, wine tours, outdoor activities
- Tech Stack: PHP 8.2.18, MySQL, Slim Framework, Bootstrap
- Target: $1000/month revenue

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

## Debugging Practices
1. Use detailed error logging in middleware and controllers.
2. Implement step-by-step logging to trace the flow of requests through the application.
3. Use curl commands for testing API endpoints and debugging.
4. Regularly check and analyze the contents of php-error.log and direct-log.txt.

## Next Steps
1. Implement ticket purchasing feature
2. Add more robust input validation for all ticket operations
3. Implement pagination for ticket listing
4. Begin frontend development
5. Implement unit and integration tests
6. Enhance user management features (profile management, password reset)
7. Implement event search and filtering
8. Add support for event categories
9. Implement a global error handler for consistent error responses
10. Set up automated testing and deployment pipelines

## Notes for Future Development
- Always apply the JWT middleware before role-based middleware in protected routes.
- Regularly review and update the OpenAPI documentation as new endpoints are added or modified.
- Maintain comprehensive error logging to facilitate debugging.
- Consider implementing a more robust logging solution like Monolog in the future.
- Regularly backup the database and keep the schema updated in version control.
- As the project grows, consider implementing a caching layer to improve performance.
- Keep security at the forefront: regularly update dependencies and conduct security audits.

## Reminders
- Regularly check for PHP and dependency updates.
- Keep the .env file secure and never commit it to version control.
- Regularly review and update the README.md file with setup instructions and project updates.
- Consider setting up automated testing and deployment pipelines as the project matures.

## Development Environment Notes
- The project is being developed on a Windows system.
- When providing curl commands or any shell commands, ensure they are Windows Command Prompt compatible.
- Avoid using single quotes for JSON data in curl commands. Use escaped double quotes instead.

## Recent Progress (as of 2024-10-18)
- Successfully implemented and tested the ticket update functionality.
- Successfully implemented and tested the ticket deletion functionality.
- Verified correct handling of events with no tickets.
- Sample successful update command:
  ```
  curl -X PUT http://osoyoos-events.localhost/events/3/tickets/2 -H "Content-Type: application/json" -d "{\"ticket_type\":\"VIP\", \"price\":100.00, \"quantity\":50}"
  ```
- Sample successful delete command:
  ```
  curl -X DELETE http://osoyoos-events.localhost/events/3/tickets/2
  ```
- Verified deletion by fetching tickets for an event:
  ```
  curl http://osoyoos-events.localhost/events/3/tickets
  {"event":{"id":3,"title":"Organizer Event","description":"An event created by an organizer","date":"2024-08-01 18:00:00","location":"Osoyoos Community Centre","organizer_id":2,"created_at":"2024-10-14 21:34:17"},"tickets":[]}
  ```

## Current Status
- Full CRUD (Create, Read, Update, Delete) operations for tickets have been implemented, tested, and verified successfully.
- The system correctly handles the scenario of an event with no tickets.
