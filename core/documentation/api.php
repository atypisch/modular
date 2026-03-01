<?php
return [
    'title' => 'REST API',
    'body' => <<<'MD'
The REST API is available at `BASE_URL/admin/api/`. All endpoints return JSON.

**Auth:** Use `Authorization: Bearer <token>` or session cookie for admin.

**Endpoints (examples):**
- `GET /admin/api/posts` – list posts (query: post_type, per_page, page)
- `GET /admin/api/posts/{id}` – single post
- `POST /admin/api/posts` – create (auth required)
- `PUT /admin/api/posts/{id}` – update (auth required)
- `DELETE /admin/api/posts/{id}` – delete (auth required)
- `GET /admin/api/post-types` – list post types
- `GET /admin/api/media` – list media
- `POST /admin/api/auth/login` – login (returns token)
- `GET /admin/api/search?q=` – search

**Response example:** `{"data": [...], "meta": {"total": 10}}`
MD
,
];
