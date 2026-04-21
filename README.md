# TaskFlux

Simple user todo app.

## Has
- Register
- Login
- User-only todos
- Create / update / delete / complete
- Priority: low / medium / high
- Due date
- Dashboard stats: total, pending, completed, done %

## Run
1. `copy .env.example .env`
2. Set `JWT_SECRET` in `.env`
3. `php -S localhost:8000 router.php`
4. Open `http://localhost:8000`

## API
- `POST /api/auth/register`
- `POST /api/auth/login`
- `GET /api/auth/me`
- `GET /api/todos`
- `POST /api/todos`
- `PATCH /api/todos/{id}`
- `DELETE /api/todos/{id}`
