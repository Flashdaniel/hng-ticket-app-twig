# Ticket Management App (PHP + Twig)

A traditional server-side rendered ticket management system built with PHP, Slim Framework, and Twig templating engine. Features a responsive interface and comprehensive ticket management capabilities.

## Features

- 🔐 User Authentication (Login/Signup)
- 📊 Dashboard with ticket statistics
- 🎫 Full CRUD operations for tickets
- 🔍 Ticket filtering and search
- 📱 Responsive design (max-width: 1440px)
- 🔔 Toast notifications for feedback
- 🎨 Clean, modern UI with consistent styling
- 🖥️ Server-side rendering with Twig
- 💾 Session-based storage (for development)

## Tech Stack

- **Backend:** PHP 8+
- **Framework:** Slim Framework 4
- **Template Engine:** Twig 3
- **Frontend:** Vanilla JavaScript
- **Styling:** CSS3 with CSS Variables
- **Package Manager:** Composer

## Getting Started

1. Clone the repository
2. Install dependencies:

   ```bash
   composer install
   ```

3. Start the PHP development server:

   ```bash
   cd public
   php -S localhost:3000
   ```

4. Open [http://localhost:3000](http://localhost:3000) in your browser

## Project Structure

```
ticket-app-twig/
  ├── composer.json        # PHP dependencies
  ├── public/
  │   ├── index.php       # Application entry point
  │   └── .htaccess      # Apache configuration
  ├── src/
  │   ├── Controllers/    # PHP controllers
  │   └── Models/         # PHP models
  ├── templates/
  │   ├── layouts/        # Base templates
  │   │   └── base.html.twig
  │   └── pages/          # Page templates
  │       ├── dashboard.html.twig
  │       ├── landing.html.twig
  │       ├── login.html.twig
  │       ├── signup.html.twig
  │       └── tickets.html.twig
  ├── assets/
  │   ├── css/           # Stylesheets
  │   │   ├── styles.css
  │   │   └── tickets.css
  │   └── js/            # JavaScript
  │       ├── main.js
  │       └── tickets.js
  └── vendor/            # Composer dependencies
```

## Features in Detail

### Authentication

- Login and signup functionality
- Session-based authentication
- Protected routes
- Secure password handling

### Dashboard

- Total tickets count
- Open tickets count
- In-progress tickets count
- Closed tickets count
- Recent tickets list

### Ticket Management

- Create new tickets with validation
- View ticket details
- Edit existing tickets
- Delete tickets with confirmation
- Filter tickets by status
- Search tickets by title/description

### Form Validation

- Server-side validation
- Client-side validation
- Required field checking
- Status validation (open/in_progress/closed)
- Real-time feedback

### UI/UX Features

- Toast notifications for actions
- Modal dialogs for forms
- Responsive layout
- Loading states
- Error handling
- Clean and consistent design

## API Endpoints

### Authentication

- POST `/auth/login` - User login
- POST `/auth/signup` - User registration
- POST `/auth/logout` - User logout

### Tickets

- GET `/api/tickets` - List tickets
- POST `/api/tickets` - Create ticket
- PUT `/api/tickets/{id}` - Update ticket
- DELETE `/api/tickets/{id}` - Delete ticket

## Development Storage

For development purposes, the application uses PHP sessions to store ticket data. In a production environment, this should be replaced with a proper database implementation.

## Template System

The application uses Twig as its template engine, providing:

- Template inheritance
- Block system
- Filters and functions
- Automatic escaping
- Easy integration with PHP

## JavaScript Features

- Form validation
- AJAX requests for API interaction
- Toast notifications
- Modal management
- Dynamic content updates

## CSS Architecture

- CSS Variables for theming
- Responsive design principles
- Component-based styles
- Utility classes
- Consistent spacing and colors

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## Production Deployment

For production deployment:

1. Set up a proper web server (Apache/Nginx)
2. Configure PHP environment
3. Implement a proper database
4. Set up proper session handling
5. Enable error reporting appropriately
6. Configure SSL/TLS

## License

This project is licensed under the MIT License.
