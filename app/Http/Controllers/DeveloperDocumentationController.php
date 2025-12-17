<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DeveloperDocumentationController extends Controller
{
    /**
     * Display the developer documentation page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // API Endpoints data
        $endpoints = [
            [
                'method' => 'GET',
                'url' => '/api/v1/users',
                'description' => 'Retrieve a list of all users with pagination support.',
                'params' => [
                    ['name' => 'page', 'description' => 'Page number (default: 1)'],
                    ['name' => 'limit', 'description' => 'Results per page (max: 100)']
                ],
                'response' => '{"users":[...], "total": 250, "page": 1, "pages": 25}'
            ],
            [
                'method' => 'POST',
                'url' => '/api/v1/users',
                'description' => 'Create a new user account.',
                'params' => [],
                'response' => '{"name": "John Doe", "email": "john@example.com", "role": "developer"}'
            ],
            [
                'method' => 'PUT',
                'url' => '/api/v1/users/{id}',
                'description' => 'Update user information by ID.',
                'params' => [],
                'response' => '{"id": 1, "name": "John Doe", "email": "john@example.com"}'
            ],
            [
                'method' => 'DELETE',
                'url' => '/api/v1/users/{id}',
                'description' => 'Delete a user account by ID.',
                'params' => [],
                'response' => '{"message": "User deleted successfully"}'
            ]
        ];

        // Response codes
        $responseCodes = [
            ['code' => '200', 'description' => 'Request successful', 'example' => 'Data returned successfully'],
            ['code' => '201', 'description' => 'Resource created', 'example' => 'New user created'],
            ['code' => '400', 'description' => 'Bad request', 'example' => 'Invalid parameters'],
            ['code' => '401', 'description' => 'Unauthorized', 'example' => 'Invalid API key'],
            ['code' => '404', 'description' => 'Not found', 'example' => 'Resource doesn\'t exist'],
            ['code' => '429', 'description' => 'Rate limited', 'example' => 'Too many requests']
        ];

        // SDK Documentation
        $sdks = [
            [
                'name' => 'JavaScript SDK',
                'icon' => 'fab fa-js-square',
                'color' => 'warning',
                'description' => 'Node.js & Browser support',
                'features' => [
                    'Promise-based API',
                    'TypeScript definitions',
                    'Retry mechanism'
                ]
            ],
            [
                'name' => 'Python SDK',
                'icon' => 'fab fa-python',
                'color' => 'info',
                'description' => 'Python 3.6+ support',
                'features' => [
                    'Async/await support',
                    'Pydantic models',
                    'Django integration'
                ]
            ]
        ];

        return view('developer.documentation', compact('endpoints', 'responseCodes', 'sdks'));
    }
}