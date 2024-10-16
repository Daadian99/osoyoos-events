# Osoyoos Event Ticketing System - Project Notes

## Project Overview
- Web-based event ticketing system for Osoyoos, BC
- Focus: Local music events, wine tours, outdoor activities
- Tech Stack: PHP 8.2.18, MySQL, Slim Framework, Bootstrap
- Target: $1000/month revenue

## Current Status
- Basic CRUD operations for Events are implemented and working
- Authentication using JWT is functional
- Role-based access control is in place for protected routes
- Project initiated in C:\wamp64\www\osoyoos-events
- Basic directory structure created
- Composer.json file manually created
- Dependencies installed
- Basic index.php file created in public directory

## Key Components
1. EventController: Handles event-related operations (create, read, update, delete)
2. Event Model: Interacts with the database for event operations
3. UserController: Manages user registration and login
4. User Model: Handles user-related database operations
5. JwtAuthMiddleware: Authenticates users using JWT
6. RoleMiddleware: Enforces role-based access control

## Database Structure
- Users table: id, username, email, password, role
- Events table: id, title, description, date, location, organizer_id

## API Endpoints
- POST /register: User registration
- POST /login: User login
- POST /events: Create a new event (protected)
- GET /events: Retrieve all events
- GET /events/{id}: Retrieve a specific event
- PUT /events/{id}: Update an event (protected)
- DELETE /events/{id}: Delete an event (protected)

## Next Steps
1. Verify development environment setup
2. Implement ticket creation and management features
3. Develop event creation and management features (completed)
4. Create ticket purchasing system
5. Integrate payment gateway
6. Design and implement responsive UI
7. Develop basic reporting functionality
8. Implement pagination for event listings
9. Add search and filter functionality for events
10. Enhance error handling and input validation
11. Write unit and integration tests
12. Plan frontend development

## Detailed Task List
1. Verify development environment
   - [x] Confirm PHP 8.2 is running
   - [x] Verify Composer installation and version
   - [x] Check if Slim Framework is properly installed
   - [x] Test basic "Hello, Osoyoos Events!" page

2. Create database schema
   - [x] Design tables for users, events, tickets, organizers
   - [x] Create SQL script for database initialization

3. Implement user authentication system
   - [x] Set up user registration endpoint
   - [x] Implement login functionality
   - [x] Add password hashing and security measures

4. Develop event creation and management features
   - [x] Create API endpoints for CRUD operations on events
   - [x] Implement event listing and details pages
   - [ ] Add image upload functionality for event photos

5. Create ticket purchasing system
   - [ ] Develop shopping cart functionality
   - [ ] Implement checkout process
   - [ ] Create order confirmation system

6. Integrate payment gateway
   - [ ] Set up PayPal SDK
   - [ ] Implement payment processing
   - [ ] Add payment confirmation and error handling

7. Design and implement responsive UI
   - [ ] Set up Bootstrap
   - [ ] Create responsive layouts for all pages
   - [ ] Implement mobile-friendly navigation

8. Develop basic reporting functionality
   - [ ] Create dashboard for event organizers
   - [ ] Implement ticket sales reports
   - [ ] Add basic analytics for event performance

## Notes
- Focus on simplicity and ease of use for local businesses
- Prioritize mobile responsiveness for tourists
- Consider local partnerships for initial user base
- Plan for scalability to handle peak tourist season
- JWT secret key is stored in the index.php file - consider moving this to an environment variable for better security
- Current role system uses 'organizer' and 'admin' roles - may need to expand this in the future
- Remember to check the PHP error logs (C:\wamp64\logs\php_error.log) for debugging

## Development Process Improvements

1. Version Control:
   - Set up a Git repository for the project
   - Create a .gitignore file to exclude sensitive information and unnecessary files

2. Environment Configuration:
   - Implement a .env file for storing configuration variables
   - Update code to use these environment variables instead of hardcoded values

3. API Documentation:
   - Set up Swagger/OpenAPI for documenting API endpoints
   - Keep documentation up-to-date as we develop new features

4. Automated Testing:
   - Set up PHPUnit for unit testing
   - Start writing tests for existing models and controllers
   - Aim for at least 70% code coverage

5. Code Linting:
   - Install PHP_CodeSniffer
   - Set up a configuration file (.phpcs.xml) with our preferred coding standards

6. Development Workflow:
   - Set up a GitHub Projects board or Trello board for task management
   - Use feature branches for new developments

7. Logging:
   - Implement Monolog for more comprehensive logging
   - Set up different log levels (debug, info, warning, error)

8. Error Handling:
   - Implement a global error handler
   - Create custom exception classes for different types of errors

9. Postman Collection:
   - Create a Postman collection for all API endpoints
   - Include example requests and responses

10. README File:
    - Create a comprehensive README.md in the project root
    - Include setup instructions, requirements, and basic usage guide

Priority Order:
1. Version Control
2. Environment Configuration
3. README File
4. API Documentation
5. Automated Testing
6. Logging and Error Handling
7. Code Linting
8. Development Workflow
9. Postman Collection

Next immediate steps:
1. Set up Git repository and create .gitignore file
2. Create .env file and update code to use environment variables
3. Draft initial README.md with setup instructions
