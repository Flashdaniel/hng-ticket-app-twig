<?php
use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Start session for simple in-memory ticket storage (development only)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create Container
$container = new Container();

// Set view in container
$container->set('view', function () {
    $view = Twig::create(__DIR__ . '/../templates', ['cache' => false]);
    
    // Add base_path function
    $view->getEnvironment()->addFunction(new \Twig\TwigFunction('base_path', function () {
        return rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    }));
    
    return $view;
});

// Create App
$app = AppFactory::createFromContainer($container);

// Add Twig Middleware
$app->add(TwigMiddleware::createFromContainer($app));

// Initialize session tickets if not exists
if (!isset($_SESSION['tickets'])) {
    $_SESSION['tickets'] = [];
}

// Helper functions for ticket management
function validateTicket($data) {
    $errors = [];
    if (empty($data['title'])) {
        $errors['title'] = 'Title is required';
    }
    if (empty($data['status'])) {
        $errors['status'] = 'Status is required';
    } elseif (!in_array($data['status'], ['open', 'in_progress', 'closed'])) {
        $errors['status'] = 'Invalid status value';
    }
    return $errors;
}

// Routes (mirroring React/Vue routes)
$app->get('/', function ($request, $response, $args) {
    return $this->get('view')->render($response, 'pages/landing.html.twig');
});

$app->get('/auth/login', function ($request, $response, $args) {
    return $this->get('view')->render($response, 'pages/login.html.twig');
});

$app->get('/auth/signup', function ($request, $response, $args) {
    return $this->get('view')->render($response, 'pages/signup.html.twig');
});

// Simple JSON API endpoints for auth (simulate token issuance)
$app->post('/auth/login', function ($request, $response, $args) {
    $body = $request->getBody()->getContents();
    $data = json_decode($body, true) ?: [];

    // Authentication middleware
$authMiddleware = function ($request, $handler) {
    if (!isset($_SESSION['user'])) {
        $response = new \Slim\Psr7\Response();
        return $response->withHeader('Location', '/auth/login')->withStatus(302);
    }
    return $handler->handle($request);
};

// Protected routes
$app->group('', function ($group) {
    $group->get('/dashboard', function ($request, $response) {
        $tickets = $_SESSION['tickets'] ?? [];
        $totalTickets = count($tickets);
        $openTickets = count(array_filter($tickets, fn($t) => $t['status'] === 'open'));
        $inProgressTickets = count(array_filter($tickets, fn($t) => $t['status'] === 'in_progress'));
        $closedTickets = count(array_filter($tickets, fn($t) => $t['status'] === 'closed'));
        $recentTickets = array_slice(array_reverse($tickets), 0, 5);

        return $this->get('view')->render($response, 'pages/dashboard.html.twig', [
            'totalTickets' => $totalTickets,
            'openTickets' => $openTickets,
            'inProgressTickets' => $inProgressTickets,
            'closedTickets' => $closedTickets,
            'recentTickets' => $recentTickets,
            'is_authenticated' => true
        ]);
    });

    $group->get('/tickets', function ($request, $response) {
        $tickets = $_SESSION['tickets'] ?? [];
        return $this->get('view')->render($response, 'pages/tickets.html.twig', [
            'tickets' => $tickets,
            'is_authenticated' => true
        ]);
    });
})->add($authMiddleware);

// Login route
$app->post('/auth/login', function ($request, $response) {
    $data = json_decode($request->getBody()->getContents(), true) ?: [];
    $email = $data['email'] ?? null;
    $password = $data['password'] ?? null;

    if (!$email || !$password) {
        return $response->withJson(['error' => 'Email and password are required'], 400);
    }

    // For development, accept any valid email/password
    $_SESSION['user'] = [
        'email' => $email,
        'isAuthenticated' => true
    ];

    return $response->withJson(['success' => true]);
});

// Logout route
$app->post('/auth/logout', function ($request, $response) {
    session_destroy();
    return $response->withHeader('Location', '/auth/login')->withStatus(302);
});

// Ticket API endpoints
$app->get('/api/tickets', function ($request, $response) {
    if (!isset($_SESSION['user'])) {
        return $response->withStatus(401)->withJson(['error' => 'Unauthorized']);
    }
    
    $tickets = $_SESSION['tickets'];
    $status = $request->getQueryParam('status');
    $search = $request->getQueryParam('search');

    if ($status) {
        $tickets = array_filter($tickets, fn($t) => $t['status'] === $status);
    }
    if ($search) {
        $tickets = array_filter($tickets, fn($t) => 
            stripos($t['title'], $search) !== false || 
            stripos($t['description'], $search) !== false
        );
    }

    return $response->withJson($tickets);
});

$app->post('/api/tickets', function ($request, $response) {
    if (!isset($_SESSION['user'])) {
        return $response->withStatus(401)->withJson(['error' => 'Unauthorized']);
    }

    $data = json_decode($request->getBody()->getContents(), true) ?: [];
    $errors = validateTicket($data);

    if (!empty($errors)) {
        return $response->withStatus(400)->withJson(['errors' => $errors]);
    }

    $ticket = [
        'id' => count($_SESSION['tickets']) + 1,
        'title' => $data['title'],
        'description' => $data['description'] ?? '',
        'status' => $data['status'],
        'created' => date('Y-m-d H:i:s')
    ];

    $_SESSION['tickets'][] = $ticket;
    return $response->withJson($ticket);
});

$app->put('/api/tickets/{id}', function ($request, $response, $args) {
    if (!isset($_SESSION['user'])) {
        return $response->withStatus(401)->withJson(['error' => 'Unauthorized']);
    }

    $id = (int)$args['id'];
    $data = json_decode($request->getBody()->getContents(), true) ?: [];
    $errors = validateTicket($data);

    if (!empty($errors)) {
        return $response->withStatus(400)->withJson(['errors' => $errors]);
    }

    $found = false;
    foreach ($_SESSION['tickets'] as &$ticket) {
        if ($ticket['id'] === $id) {
            $ticket['title'] = $data['title'];
            $ticket['description'] = $data['description'] ?? $ticket['description'];
            $ticket['status'] = $data['status'];
            $found = true;
            break;
        }
    }

    if (!$found) {
        return $response->withStatus(404)->withJson(['error' => 'Ticket not found']);
    }

    return $response->withJson($ticket);
});

$app->delete('/api/tickets/{id}', function ($request, $response, $args) {
    if (!isset($_SESSION['user'])) {
        return $response->withStatus(401)->withJson(['error' => 'Unauthorized']);
    }

    $id = (int)$args['id'];
    $tickets = array_filter($_SESSION['tickets'], fn($t) => $t['id'] !== $id);
    
    if (count($tickets) === count($_SESSION['tickets'])) {
        return $response->withStatus(404)->withJson(['error' => 'Ticket not found']);
    }

    $_SESSION['tickets'] = array_values($tickets);
    return $response->withJson(['success' => true]);
});

// Add auth middleware to protected routes
$app->get('/dashboard', function ($request, $response) {
    $tickets = $_SESSION['tickets'] ?? [];
    $totalTickets = count($tickets);
    $openTickets = count(array_filter($tickets, fn($t) => $t['status'] === 'open'));
    $inProgressTickets = count(array_filter($tickets, fn($t) => $t['status'] === 'in_progress'));
    $closedTickets = count(array_filter($tickets, fn($t) => $t['status'] === 'closed'));
    $recentTickets = array_slice(array_reverse($tickets), 0, 5);

    return $this->get('view')->render($response, 'pages/dashboard.html.twig', [
        'totalTickets' => $totalTickets,
        'openTickets' => $openTickets,
        'inProgressTickets' => $inProgressTickets,
        'closedTickets' => $closedTickets,
        'recentTickets' => $recentTickets
    ]);
})->add(function ($request, $handler) {
    if (!isset($_SESSION['user'])) {
        return $handler->get('view')->render(
            $response->withStatus(302)->withHeader('Location', '/auth/login'),
            'pages/login.html.twig'
        );
    }
    return $handler->handle($request);
});

if (!$email || !$password) {
        $payload = ['error' => 'Email and password required'];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // Create a fake token (not secure) — enough for client-side demo
    $token = base64_encode(json_encode(['sub' => $email, 'iat' => time()]));
    $payload = ['token' => $token];
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/auth/signup', function ($request, $response, $args) {
    $body = $request->getBody()->getContents();
    $data = json_decode($body, true) ?: [];

    $name = $data['name'] ?? null;
    $email = $data['email'] ?? null;
    $password = $data['password'] ?? null;

    if (!$name || !$email || !$password) {
        $payload = ['error' => 'Name, email and password required'];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // Simulate user creation and return token
    $token = base64_encode(json_encode(['sub' => $email, 'iat' => time()]));
    $payload = ['token' => $token];
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/dashboard', function ($request, $response, $args) {
    // Read tickets from session (if any) and compute stats
    $tickets = $_SESSION['tickets'] ?? [];

    $total = count($tickets);
    $open = 0;
    $inProgress = 0;
    $closed = 0;

    // Convert createdAt strings to DateTime
    $recent = [];
    foreach ($tickets as $t) {
        $status = strtolower($t['status'] ?? 'open');
        if ($status === 'open') $open++;
        if ($status === 'in-progress' || $status === 'in_progress') $inProgress++;
        if ($status === 'closed') $closed++;

        $t['createdAt'] = isset($t['createdAt']) ? new DateTime($t['createdAt']) : new DateTime();
        $recent[] = $t;
    }

    // Show most recent 5 tickets
    usort($recent, function ($a, $b) {
        return strtotime($b['createdAt']->format(DATE_ATOM)) <=> strtotime($a['createdAt']->format(DATE_ATOM));
    });
    $recent = array_slice($recent, 0, 5);

    $data = [
        'totalTickets' => $total,
        'openTickets' => $open,
        'inProgressTickets' => $inProgress,
        'closedTickets' => $closed,
        'recentTickets' => $recent
    ];
    return $this->get('view')->render($response, 'pages/dashboard.html.twig', $data);
});

$app->get('/tickets', function ($request, $response, $args) {
    // Pass tickets from session to template (convert createdAt to DateTime)
    $tickets = $_SESSION['tickets'] ?? [];
    foreach ($tickets as &$t) {
        $t['createdAt'] = isset($t['createdAt']) ? new DateTime($t['createdAt']) : new DateTime();
    }
    $data = ['tickets' => $tickets];
    return $this->get('view')->render($response, 'pages/tickets.html.twig', $data);
});

// Simple tickets API stored in session (development only)
$app->get('/api/tickets', function ($request, $response, $args) {
    $tickets = $_SESSION['tickets'] ?? [];
    $response->getBody()->write(json_encode(array_values($tickets)));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/tickets', function ($request, $response, $args) {
    $body = $request->getBody()->getContents();
    $data = json_decode($body, true) ?: [];

    $title = $data['title'] ?? null;
    $description = $data['description'] ?? null;
    $priority = $data['priority'] ?? 'medium';
    $status = $data['status'] ?? 'open';

    if (!$title || !$description) {
        $payload = ['error' => 'Title and description required'];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $tickets = &$_SESSION['tickets'];
    if (!is_array($tickets)) $tickets = [];

    $ids = array_map(function ($t) { return (int)($t['id'] ?? 0); }, $tickets);
    $nextId = $ids ? max($ids) + 1 : 1;

    $ticket = [
        'id' => $nextId,
        'title' => $title,
        'description' => $description,
        'priority' => $priority,
        'status' => $status,
        'createdAt' => date(DATE_ATOM)
    ];

    $tickets[] = $ticket;
    $response->getBody()->write(json_encode($ticket));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});

$app->put('/api/tickets/{id}', function ($request, $response, $args) {
    $id = (int)$args['id'];
    $body = $request->getBody()->getContents();
    $data = json_decode($body, true) ?: [];

    $tickets = &$_SESSION['tickets'];
    if (!is_array($tickets)) $tickets = [];

    foreach ($tickets as &$t) {
        if ((int)$t['id'] === $id) {
            foreach (['title','description','priority','status'] as $f) {
                if (isset($data[$f])) $t[$f] = $data[$f];
            }
            $response->getBody()->write(json_encode($t));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    return $response->withStatus(404);
});

$app->delete('/api/tickets/{id}', function ($request, $response, $args) {
    $id = (int)$args['id'];
    $tickets = &$_SESSION['tickets'];
    if (!is_array($tickets)) $tickets = [];

    foreach ($tickets as $idx => $t) {
        if ((int)$t['id'] === $id) {
            array_splice($tickets, $idx, 1);
            return $response->withStatus(204);
        }
    }

    return $response->withStatus(404);
});

// Serve static assets directly when requested under /assets
$app->get('/assets/{type}/{file}', function ($request, $response, $args) {
    $file = __DIR__ . '/../assets/' . $args['type'] . '/' . $args['file'];
    if (!file_exists($file)) {
        return $response->withStatus(404);
    }

    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $map = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'svg' => 'image/svg+xml'
    ];

    $contentType = $map[$ext] ?? 'application/octet-stream';
    $response = $response->withHeader('Content-Type', $contentType);
    $response->getBody()->write(file_get_contents($file));
    return $response;
});

// Run app
$app->run();