# Osoyoos Events Ticketing System

This is the repository for the Osoyoos Events ticketing system, a web-based platform for managing local events in Osoyoos, BC.

## Setup Instructions

1. Clone the repository:
   ```
   git clone https://github.com/your-username/osoyoos-events.git
   ```

2. Install dependencies:
   ```
   composer install
   ```

3. Copy `.env.example` to `.env` and update with your local configuration:
   ```
   cp .env.example .env
   ```

4. Update the `.env` file with your database credentials and JWT secret.

5. Set up your local database according to the schema (instructions to be added).

6. Start your local PHP server:
   ```
   php -S localhost:8000 -t public
   ```

7. Access the application at `http://localhost:8000`

## Development

This project uses PHP 8.2.18 with the Slim Framework and MySQL. It follows PSR-4 autoloading standards.

## Contributing

Please read CONTRIBUTING.md for details on our code of conduct and the process for submitting pull requests.

## License

This project is licensed under the MIT License - see the LICENSE.md file for details.

