<h1 align="center">Laravel Fuudik</h1>

<p align="center">
Fuudik is a simple food delivery application. It is a Laravel application with a VueJS frontend.
<br>
<br>

## Installation

1. Clone the repository
2. Run `composer install`
3. Create a copy of your .env file `cp .env.example .env`
4. Run `./vendor/bin/sail up -d
5. Run `./vendor/bin/sail php artisan migrate:refresh --seed`
6. Run `./vendor/bin/sail php artisan queue:listen`
7. Run `./vendor/bin/sail php artisan test`

## Roadmap
- Add more tests
- Extract the queries in loop to a bulk
- Replaces the exist validation to improve performance of queries
- Event Sourcing architecture for keeping the history of the stock changes
- It depends on the business, but I think that the stock operation could be a job to be executed in background like amazon's payment process
