<?php
require __DIR__ . '/../vendor/autoload.php';

$openapi = [
    'openapi' => '3.0.0',
    'info' => [
        'title' => 'Osoyoos Event Ticketing API',
        'description' => 'API for managing events and tickets in Osoyoos.',
        'version' => '1.0.0',
    ],
    'servers' => [
        [
            'url' => 'http://osoyoos-events.localhost',
            'description' => 'Local development server',
        ],
    ],
    'paths' => [
        '/events' => [
            'get' => [
                'summary' => 'List all events',
                'description' => 'Retrieves a list of all events, with optional pagination and filtering.',
                'tags' => ['Events'],
                'parameters' => [
                    [
                        'name' => 'page',
                        'in' => 'query',
                        'description' => 'Page number for pagination',
                        'schema' => ['type' => 'integer', 'default' => 1]
                    ],
                    [
                        'name' => 'limit',
                        'in' => 'query',
                        'description' => 'Number of items per page',
                        'schema' => ['type' => 'integer', 'default' => 10]
                    ],
                    [
                        'name' => 'search',
                        'in' => 'query',
                        'description' => 'Search term for filtering events',
                        'schema' => ['type' => 'string']
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Successful operation',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'data' => [
                                            'type' => 'array',
                                            'items' => ['$ref' => '#/components/schemas/Event'],
                                        ],
                                        'meta' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'current_page' => ['type' => 'integer'],
                                                'total_pages' => ['type' => 'integer'],
                                                'total_items' => ['type' => 'integer'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'post' => [
                'summary' => 'Create a new event',
                'description' => 'Creates a new event with the provided details.',
                'tags' => ['Events'],
                'security' => [['bearerAuth' => []]],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/EventInput'],
                        ],
                    ],
                ],
                'responses' => [
                    '201' => [
                        'description' => 'Event created successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/Event'],
                            ],
                        ],
                    ],
                    '400' => [
                        'description' => 'Invalid input',
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/Error'],
                            ],
                        ],
                    ],
                    '401' => [
                        'description' => 'Unauthorized',
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/Error'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        '/events/{id}' => [
            'get' => [
                'summary' => 'Get a specific event',
                'description' => 'Retrieves the details of a specific event.',
                'tags' => ['Events'],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Successful operation',
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/Event'],
                            ],
                        ],
                    ],
                    '404' => [
                        'description' => 'Event not found',
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/Error'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'components' => [
        'schemas' => [
            'Event' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'title' => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                    'date' => ['type' => 'string', 'format' => 'date-time'],
                    'location' => ['type' => 'string'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'EventInput' => [
                'type' => 'object',
                'required' => ['title', 'date', 'location'],
                'properties' => [
                    'title' => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                    'date' => ['type' => 'string', 'format' => 'date-time'],
                    'location' => ['type' => 'string'],
                ],
            ],
            'Error' => [
                'type' => 'object',
                'properties' => [
                    'message' => ['type' => 'string'],
                ],
            ],
        ],
        'securitySchemes' => [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
            ],
        ],
    ],
];

header('Content-Type: application/json');
echo json_encode($openapi, JSON_PRETTY_PRINT);
