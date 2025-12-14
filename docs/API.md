# Modular CMS - Complete API Documentation

## Base URL
```
/modular/admin/api/index.php
```

## Authentication

All protected endpoints require authentication via JWT token.

### Get Token
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

Response:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "username": "user"
  }
}
```

### Use Token
Include in header:
```
Authorization: Bearer YOUR_TOKEN
```

Or use cookie:
```
auth_token=YOUR_TOKEN
```

## Endpoints

### Authentication

#### POST /api/v1/auth/login
Login and get JWT token.

#### POST /api/v1/auth/register
Register new user.

#### POST /api/v1/auth/forgot
Request password reset.

#### POST /api/v1/auth/reset
Reset password with token.

### Post Types

#### GET /api/v1/post-types
List all post types.

#### GET /api/v1/post-types/{id}
Get post type by ID.

#### POST /api/v1/post-types
Create new post type.
```json
{
  "name": "News",
  "slug": "news",
  "description": "News articles",
  "public": true,
  "show_in_menu": true
}
```

#### PUT /api/v1/post-types/{id}
Update post type.

#### DELETE /api/v1/post-types/{id}
Delete post type.

### Posts

#### GET /api/v1/posts?post_type_id={id}
List posts (optionally filtered by post type).

#### GET /api/v1/posts/{id}
Get post by ID.

#### POST /api/v1/posts
Create new post.
```json
{
  "post_type_id": 1,
  "title": "My Post",
  "slug": "my-post",
  "status": "published",
  "component_data": [...]
}
```

#### PUT /api/v1/posts/{id}
Update post.

#### DELETE /api/v1/posts/{id}
Delete post.

### Forms

#### GET /api/v1/forms
List all forms.

#### GET /api/v1/forms/{id}
Get form by ID.

#### POST /api/v1/forms
Create new form.

#### PUT /api/v1/forms/{id}
Update form.

#### DELETE /api/v1/forms/{id}
Delete form.

#### POST /api/v1/forms/{id}/submit
Submit form (public endpoint).
```json
{
  "field_name": "value",
  "email": "user@example.com"
}
```

### Media

#### GET /api/v1/media
List all media files.

#### GET /api/v1/media/{id}
Get media by ID.

#### POST /api/v1/media/upload
Upload media file (multipart/form-data).
- file: File to upload
- title: Media title
- alt_text: Alt text

#### DELETE /api/v1/media/{id}
Delete media.

### Search

#### GET /api/v1/search?q={query}&post_type={type}&limit={limit}
Search content.

#### GET /api/v1/search/autocomplete?q={query}&type={posts|media}
Get autocomplete suggestions.

### Actions

#### GET /api/v1/actions
List all actions.

#### GET /api/v1/actions/{id}
Get action by ID.

#### POST /api/v1/actions
Create new action.
```json
{
  "name": "Send Email on Form Submit",
  "trigger_type": "form_submit",
  "trigger_config": {},
  "action_type": "send_email",
  "action_config": {
    "to": "admin@example.com",
    "subject": "New Form Submission",
    "body": "Form submitted: {form_data}"
  },
  "enabled": true,
  "priority": 10
}
```

#### PUT /api/v1/actions/{id}
Update action.

#### DELETE /api/v1/actions/{id}
Delete action.

## Error Responses

All errors follow this format:
```json
{
  "error": "Error message",
  "code": 400
}
```

Common status codes:
- 200: Success
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 409: Conflict
- 429: Rate Limit Exceeded
- 500: Server Error

## Rate Limiting

- General API: 100 requests per hour per IP
- Auth endpoints: 5 requests per 15 minutes per IP
- Form submissions: 10 per hour per IP

Rate limit headers:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1633024800
```

## OpenAPI Specification

Full OpenAPI 3.0 specification available at:
`/docs/openapi.json`
