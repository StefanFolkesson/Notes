# Notes

En enkel Notes API i ren PHP med SQLite.

## Kom igång

Krav:
- PHP 8.1+ med SQLite-stöd aktiverat

Starta API:t lokalt:

```bash
php -S localhost:8000 index.php
```

API:t finns då på `http://localhost:8000`.

## Endpoints

- `GET /notes` – lista alla anteckningar
- `GET /notes/{id}` – hämta en anteckning
- `POST /notes` – skapa en anteckning
- `PUT /notes/{id}` – uppdatera en anteckning
- `DELETE /notes/{id}` – ta bort en anteckning

## Exempel med curl

Skapa:

```bash
curl -i -X POST http://localhost:8000/notes \
  -H "Content-Type: application/json" \
  -d '{"title":"Min anteckning","content":"Detta är innehållet"}'
```

Lista:

```bash
curl -i http://localhost:8000/notes
```

Hämta en:

```bash
curl -i http://localhost:8000/notes/1
```

Uppdatera:

```bash
curl -i -X PUT http://localhost:8000/notes/1 \
  -H "Content-Type: application/json" \
  -d '{"title":"Ny titel","content":"Uppdaterat innehåll"}'
```

Ta bort:

```bash
curl -i -X DELETE http://localhost:8000/notes/1
```
