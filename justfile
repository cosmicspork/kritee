dev:
    composer run dev

build:
    bun run build

format:
    ./vendor/bin/pint

format-check:
    ./vendor/bin/pint --test

analyse:
    ./vendor/bin/phpstan analyse --memory-limit=1G

check: format-check analyse

test:
    php artisan test

all: check test
