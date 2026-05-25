<?php

declare(strict_types=1);

require_once __DIR__ . '/src/database.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = trim($uriPath, '/');
$segments = $path === '' ? [] : explode('/', $path);

if ($segments === []) {
    respond(200, ['message' => 'Notes API']);
}

if ($segments[0] !== 'notes') {
    respond(404, ['error' => 'Route not found']);
}

$noteId = $segments[1] ?? null;
if (isset($segments[2])) {
    respond(404, ['error' => 'Route not found']);
}

if ($noteId !== null && !ctype_digit($noteId)) {
    respond(404, ['error' => 'Route not found']);
}

$db = getDatabase();

switch ($method) {
    case 'GET':
        if ($noteId === null) {
            $stmt = $db->query('SELECT id, title, content, created_at, updated_at FROM notes ORDER BY id ASC');
            respond(200, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        $stmt = $db->prepare('SELECT id, title, content, created_at, updated_at FROM notes WHERE id = :id');
        $stmt->execute(['id' => (int) $noteId]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($note === false) {
            respond(404, ['error' => 'Note not found']);
        }

        respond(200, $note);
        break;

    case 'POST':
        if ($noteId !== null) {
            respond(404, ['error' => 'Route not found']);
        }

        $body = parseJsonBody();
        validateNotePayload($body);

        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare(
            'INSERT INTO notes (title, content, created_at, updated_at) VALUES (:title, :content, :created_at, :updated_at)'
        );
        $stmt->execute([
            'title' => trim($body['title']),
            'content' => trim($body['content']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $id = (int) $db->lastInsertId();
        $createdNote = findNoteById($db, $id);

        http_response_code(201);
        header('Location: /notes/' . $id);
        echo json_encode($createdNote, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;

    case 'PUT':
        if ($noteId === null) {
            respond(404, ['error' => 'Route not found']);
        }

        if (findNoteById($db, (int) $noteId) === null) {
            respond(404, ['error' => 'Note not found']);
        }

        $body = parseJsonBody();
        validateNotePayload($body);

        $stmt = $db->prepare('UPDATE notes SET title = :title, content = :content, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'id' => (int) $noteId,
            'title' => trim($body['title']),
            'content' => trim($body['content']),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $updatedNote = findNoteById($db, (int) $noteId);
        respond(200, $updatedNote);
        break;

    case 'DELETE':
        if ($noteId === null) {
            respond(404, ['error' => 'Route not found']);
        }

        if (findNoteById($db, (int) $noteId) === null) {
            respond(404, ['error' => 'Note not found']);
        }

        $stmt = $db->prepare('DELETE FROM notes WHERE id = :id');
        $stmt->execute(['id' => (int) $noteId]);

        http_response_code(204);
        exit;

    default:
        header('Allow: GET, POST, PUT, DELETE');
        respond(405, ['error' => 'Method not allowed']);
}

function parseJsonBody(): array
{
    $rawBody = file_get_contents('php://input');
    if ($rawBody === false || trim($rawBody) === '') {
        respond(400, ['error' => 'Request body must be valid JSON']);
    }

    $data = json_decode($rawBody, true);
    if (!is_array($data)) {
        respond(400, ['error' => 'Request body must be valid JSON']);
    }

    return $data;
}

function validateNotePayload(array $payload): void
{
    $title = $payload['title'] ?? null;
    $content = $payload['content'] ?? null;

    if (!is_string($title) || trim($title) === '') {
        respond(422, ['error' => 'Field "title" is required']);
    }

    if (!is_string($content) || trim($content) === '') {
        respond(422, ['error' => 'Field "content" is required']);
    }
}

function findNoteById(PDO $db, int $id): ?array
{
    $stmt = $db->prepare('SELECT id, title, content, created_at, updated_at FROM notes WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $note = $stmt->fetch(PDO::FETCH_ASSOC);

    return $note === false ? null : $note;
}

function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
