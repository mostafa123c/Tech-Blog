# Blog Application API Documentation

## Base URL

```
http://localhost/api
```

## Authentication

All endpoints except `POST /register` and `POST /login` require a valid JWT token in the Authorization header:

```
Authorization: Bearer <token>
```

**Required Header for All Requests:**

```
Accept: application/json
```

---

## Authentication Endpoints

### Register User

Create a new user account.

**Endpoint:** `POST /register`

**Request Body (multipart/form-data):**

| Field    | Type   | Required | Description                             |
| -------- | ------ | -------- | --------------------------------------- |
| name     | string | Yes      | User's full name (max 255 chars)        |
| email    | string | Yes      | Valid email address (unique)            |
| password | string | Yes      | Password (min 8 chars)                  |
| image    | file   | Yes      | Profile image (jpg, jpeg, png, max 2MB) |

**Success Response (201 Created):**

```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "name": "mostafa",
    "email": "mn3m@gmail.com",
    "updated_at": "2025-12-24T18:19:50.000000Z",
    "created_at": "2025-12-24T18:19:50.000000Z",
    "id": 4
  }
}
```

**Error Response (422 Unprocessable Entity):**

```json
{
  "success": false,
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

---

### Login

Authenticate user and receive JWT token.

**Endpoint:** `POST /login`

**Request Body (form-data):**

| Field    | Type   | Required | Description         |
| -------- | ------ | -------- | ------------------- |
| email    | string | Yes      | Valid email address |
| password | string | Yes      | User's password     |

**Success Response (200 OK):**

```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "expires_in": 3600,
  "user": {
    "id": 3,
    "name": "mostafa",
    "email": "mn3m@gmail.com",
    "email_verified_at": null,
    "image": "users_images/BxkouHyhqa9nCHmtim0fn4dDQCcSWvphVlguVbZb.png",
    "created_at": "2025-12-24T18:18:57.000000Z",
    "updated_at": "2025-12-24T18:18:57.000000Z",
    "deleted_at": null
  }
}
```

**Error Response (401 Unauthorized):**

```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

**Error Response (422 Unprocessable Entity):**

```json
{
  "success": false,
  "message": "The email field must be a valid email address.",
  "errors": {
    "email": ["The email field must be a valid email address."]
  }
}
```

---

### Get Current User

Retrieve authenticated user's information.

**Endpoint:** `GET /me`

**Headers:** `Authorization: Bearer <token>`

**Success Response (200 OK):**

```json
{
  "id": 3,
  "name": "mostafa",
  "email": "mn3m@gmail.com",
  "image": "users_images/BxkouHyhqa9nCHmtim0fn4dDQCcSWvphVlguVbZb.png",
  "created_at": "2025-12-24T18:18:57.000000Z"
}
```

**Error Response (401 Unauthorized):**

```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

---

### Logout

Invalidate current JWT token.

**Endpoint:** `POST /logout`

**Headers:** `Authorization: Bearer <token>`

**Success Response (200 OK):**

```json
{
  "success": true,
  "message": "Successfully logged out"
}
```

**Error Response (401 Unauthorized):**

```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

---

## Posts Endpoints

### List All Posts

Get paginated list of non-expired posts.

**Endpoint:** `GET /posts`

**Headers:** `Authorization: Bearer <token>`

**Query Parameters:**

| Parameter | Type    | Description              |
| --------- | ------- | ------------------------ |
| page      | integer | Page number (default: 1) |

**Success Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 4,
        "title": "test mn3m",
        "body": "test bodyyyyyyy mn3m",
        "user": {
          "id": 3,
          "name": "mostafa",
          "image": "users_images/BxkouHyhqa9nCHmtim0fn4dDQCcSWvphVlguVbZb.png"
        },
        "tags": [
          { "id": 1, "name": "technology" },
          { "id": 2, "name": "node" },
          { "id": 5, "name": "php" }
        ],
        "created_at": "2025-12-24 20:23:31",
        "updated_at": "2025-12-24 20:23:31"
      }
    ],
    "from": 1,
    "to": 2,
    "per": 15,
    "total": 2,
    "current": 1,
    "next_page_url": null,
    "prev_page_url": null,
    "path": "http://127.0.0.1:8000/api/posts"
  }
}
```

---

### Get Single Post

Get details of a specific post.

**Endpoint:** `GET /posts/{id}`

**Headers:** `Authorization: Bearer <token>`

**Success Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "id": 3,
    "title": "test mn3m",
    "body": "test bodyyyyyyy mn3m",
    "user": {
      "id": 3,
      "name": "mostafa",
      "image": "users_images/BxkouHyhqa9nCHmtim0fn4dDQCcSWvphVlguVbZb.png"
    },
    "tags": [
      { "id": 1, "name": "technology" },
      { "id": 2, "name": "node" },
      { "id": 5, "name": "php" }
    ],
    "created_at": "2025-12-24 20:22:54",
    "updated_at": "2025-12-24 20:22:54"
  }
}
```

**Error Response (404 Not Found):**

```json
{
  "success": false,
  "message": "Model not found"
}
```

---

### Create Post

Create a new blog post.

**Endpoint:** `POST /posts`

**Headers:** `Authorization: Bearer <token>`

**Request Body (form-data):**

| Field   | Type   | Required | Description                |
| ------- | ------ | -------- | -------------------------- |
| title   | string | Yes      | Post title (max 255 chars) |
| body    | string | Yes      | Post content               |
| tags[0] | string | Yes      | First tag                  |
| tags[1] | string | No       | Second tag                 |
| tags[n] | string | No       | Additional tags            |

**Success Response (201 Created):**

```json
{
  "success": true,
  "message": "Post created successfully",
  "data": {
    "id": 3,
    "title": "test mn3m",
    "body": "test bodyyyyyyy mn3m",
    "user": {
      "id": 3,
      "name": "mostafa",
      "image": "users_images/BxkouHyhqa9nCHmtim0fn4dDQCcSWvphVlguVbZb.png"
    },
    "tags": [
      { "id": 1, "name": "technology" },
      { "id": 2, "name": "node" },
      { "id": 5, "name": "php" }
    ],
    "created_at": "2025-12-24 20:22:54",
    "updated_at": "2025-12-24 20:22:54"
  }
}
```

**Error Response (422 Unprocessable Entity):**

```json
{
  "success": false,
  "message": "The title field is required. (and 1 more error)",
  "errors": {
    "title": ["The title field is required."],
    "body": ["The body field is required."]
  }
}
```

---

### Update Post

Update an existing post (author only).

**Endpoint:** `PUT /posts/{id}`

**Headers:** `Authorization: Bearer <token>`

**Request Body (x-www-form-urlencoded):**

| Field   | Type   | Required | Description                |
| ------- | ------ | -------- | -------------------------- |
| title   | string | No       | Post title (max 255 chars) |
| body    | string | No       | Post content               |
| tags[0] | string | No       | First tag                  |
| tags[1] | string | No       | Second tag                 |

**Success Response (200 OK):**

```json
{
  "success": true,
  "message": "Post updated successfully",
  "data": {
    "id": 4,
    "title": "test updated",
    "body": "jsfbhscvbhdscvbhdvbhd  vh",
    "user": {
      "id": 3,
      "name": "mostafa",
      "image": "users_images/BxkouHyhqa9nCHmtim0fn4dDQCcSWvphVlguVbZb.png"
    },
    "tags": [
      { "id": 3, "name": "updated" },
      { "id": 4, "name": "node updated" }
    ],
    "created_at": "2025-12-24 20:23:31",
    "updated_at": "2025-12-24 20:25:01"
  }
}
```

**Error Response (404 Not Found):**

```json
{
  "success": false,
  "message": "Model not found"
}
```

**Error Response (400 Bad Request - Unauthorized Action):**

```json
{
  "success": false,
  "message": "This action is unauthorized."
}
```

---

### Delete Post

Delete a post (author only).

**Endpoint:** `DELETE /posts/{id}`

**Headers:** `Authorization: Bearer <token>`

**Success Response (200 OK):**

```json
{
  "success": true,
  "message": "Post deleted successfully"
}
```

**Error Response (404 Not Found):**

```json
{
  "success": false,
  "message": "Model not found"
}
```

**Error Response (400 Bad Request - Unauthorized Action):**

```json
{
  "success": false,
  "message": "This action is unauthorized."
}
```

---

## Comments Endpoints

### List Post Comments

Get paginated comments for a post.

**Endpoint:** `GET /posts/{postId}/comments`

**Headers:** `Authorization: Bearer <token>`

**Query Parameters:**

| Parameter | Type    | Description              |
| --------- | ------- | ------------------------ |
| page      | integer | Page number (default: 1) |

**Success Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 5,
        "body": "test",
        "user": {
          "id": 3,
          "name": "mostafa",
          "image": "users_images/BxkouHyhqa9nCHmtim0fn4dDQCcSWvphVlguVbZb.png"
        },
        "created_at": "2025-12-24 20:26:12"
      }
    ],
    "from": 1,
    "to": 2,
    "per": 10,
    "total": 2,
    "current": 1,
    "next_page_url": null,
    "prev_page_url": null,
    "path": "http://127.0.0.1:8000/api/posts/3/comments"
  }
}
```

**Error Response (404 Not Found):**

```json
{
  "success": false,
  "message": "Model not found"
}
```

---

### Create Comment

Add a comment to a post.

**Endpoint:** `POST /posts/{postId}/comments`

**Headers:** `Authorization: Bearer <token>`

**Request Body (form-data):**

| Field | Type   | Required | Description     |
| ----- | ------ | -------- | --------------- |
| body  | string | Yes      | Comment content |

**Success Response (201 Created):**

```json
{
  "success": true,
  "message": "Comment added successfully",
  "data": {
    "id": 5,
    "body": "test",
    "user": {
      "id": 3,
      "name": "mostafa",
      "image": "users_images/BxkouHyhqa9nCHmtim0fn4dDQCcSWvphVlguVbZb.png"
    },
    "created_at": "2025-12-24 20:26:12"
  }
}
```

**Error Response (422 Unprocessable Entity):**

```json
{
  "success": false,
  "message": "The body field is required.",
  "errors": {
    "body": ["The body field is required."]
  }
}
```

**Error Response (404 Not Found):**

```json
{
  "success": false,
  "message": "Model not found"
}
```

---

### Update Comment

Update a comment (author only).

**Endpoint:** `PUT /comments/{id}`

**Headers:** `Authorization: Bearer <token>`

**Request Body (x-www-form-urlencoded):**

| Field | Type   | Required | Description             |
| ----- | ------ | -------- | ----------------------- |
| body  | string | Yes      | Updated comment content |

**Success Response (200 OK):**

```json
{
  "success": true,
  "message": "Comment updated successfully",
  "data": {
    "id": 4,
    "body": "first comment updated",
    "user": {
      "id": 3,
      "name": "mostafa",
      "image": "users_images/BxkouHyhqa9nCHmtim0fn4dDQCcSWvphVlguVbZb.png"
    },
    "created_at": "2025-12-24 20:26:09"
  }
}
```

**Error Response (404 Not Found):**

```json
{
  "success": false,
  "message": "Model not found"
}
```

**Error Response (400 Bad Request - Unauthorized Action):**

```json
{
  "success": false,
  "message": "This action is unauthorized."
}
```

---

### Delete Comment

Delete a comment (author only).

**Endpoint:** `DELETE /comments/{id}`

**Headers:** `Authorization: Bearer <token>`

**Success Response (200 OK):**

```json
{
  "success": true,
  "message": "Comment deleted successfully"
}
```

**Error Response (404 Not Found):**

```json
{
  "success": false,
  "message": "Model not found"
}
```

**Error Response (400 Bad Request - Unauthorized Action):**

```json
{
  "success": false,
  "message": "This action is unauthorized."
}
```

---

## Error Responses

### Common Error Formats

**401 Unauthorized:**

```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

**400 Bad Request (Unauthorized Action):**

```json
{
  "success": false,
  "message": "This action is unauthorized."
}
```

**404 Not Found:**

```json
{
  "success": false,
  "message": "Model not found"
}
```

**422 Validation Error:**

```json
{
  "success": false,
  "message": "The field is required.",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

---

## Notes

1. **Post Expiration:** All posts automatically expire after 24 hours. Expired posts are soft-deleted and not returned in list queries.
2. **Tags:** Tags are created automatically if they don't exist. Tag names are normalized to lowercase.
3. **Image Storage:** User profile images are stored and accessible via the path returned in the response (e.g., `users_images/filename.png`).
4. **JWT Token Expiration:** Tokens expire after 60 minutes (3600 seconds). Re-login to get a new token.
5. **Authorization:** Only the author of a post or comment can update or delete it. Attempting to modify another user's content will return a 400 error.
