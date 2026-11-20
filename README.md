# PAQT Case

Mijn implementatie van de PAQT case, met [technisch ontwerp](docs/TO_WMO_Vervoer_Utrecht_v4.pdf) bijgevoegd.

## Installatie

### Docker / Sail

```sh
cp .env.example .env
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD:/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install --ignore-platform-reqs
./vendor/bin/sail up -d --build
./vendor/bin/sail composer check-platform-reqs
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm ci
./vendor/bin/sail npm run build
./vendor/bin/sail artisan wmo:export-api
```

## Demo

Het callcenter moet alle inwoners van Utrecht kunnen vinden, ook zonder Wmo-beschikking. De demo gebruikt fictieve inwoners.

Alle demoaccounts gebruiken `DEMO_PASSWORD` uit `.env`. De standaardwaarde is `test`.

| Account | Toegang |
| --- | --- |
| `callcenter@example.test` | Inwoners zoeken en ritten boeken |
| `taxi-a@example.test` | De ritten van Demo Taxi A bekijken |
| `taxi-b@example.test` | De ritten van Demo Taxi B bekijken |
| `gemeente@example.test` | Aantallen en budgetten voor de hele gemeente bekijken |

## API

| Endpoint | Functie |
| --- | --- |
| `GET /api/v1/residents` | Inwoners zoeken en ophalen. |
| `POST /api/v1/rides` | Rit boeken en de afstand direct afboeken. |
| `GET /api/v1/rides` | Toegewezen ritten van het ingelogde taxibedrijf ophalen. |

De Vue-app gebruikt dezelfde API als externe clients. Voor dit project heb ik Forfity met Sanctum toegevoegd voor API Tokens en de Postman- en Bruno-collecties zijn beschikbaar via **API access**. [API-documentatie](http://localhost/docs/api).

## Jaarbudget

Zolang een beschikking actief is, gaat elk jaar op de dag van de ingangsdatum een nieuw budget in. Bij een ingangsdatum van 29 februari begint het budget in een niet-schrikkeljaar op 28 februari. De scheduler voert `wmo:renew-budgets` elk uur uit en haalt gemiste verlengingen in. Eerdere periodes blijven bewaard.

Een rit en de afboeking worden in één databasetransactie opgeslagen. De ritdatum bepaalt het budget. Bij een rit in een periode waarvoor nog geen budget bestaat, geeft de API HTTP 409.

```sh
./vendor/bin/sail artisan schedule:list
./vendor/bin/sail artisan wmo:renew-budgets
```

## Testen

De PHP-tests gebruiken een aparte PostgreSQL-database (`testing`).

```sh
./vendor/bin/sail artisan test
./vendor/bin/sail pint --test
./vendor/bin/sail npx eslint .
./vendor/bin/sail npm run typecheck
./vendor/bin/sail npm run build
```

Playwright test de draaiende demo.

```sh
./vendor/bin/sail exec -u root laravel.test npx playwright install --with-deps chromium
./vendor/bin/sail npm run test:e2e
```

De Vite-ontwikkelserver start met `./vendor/bin/sail npm run dev -- --host 0.0.0.0`.
