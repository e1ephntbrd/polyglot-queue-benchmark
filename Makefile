.PHONY: up down build logs bash seed clean send migrate ci composer-install config-show config-set go-init go-deps go-build go-worker-listen python-worker-listen php-fiber-worker-listen dotnet-worker-listen report

help:
	@printf "\033[33mUsage:\033[0m\n  make [target] [arg=\"val\"...]\n\n\033[33mTargets:\033[0m\n"
	@grep -E '^[-a-zA-Z0-9_\.\/]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[32m%-15s\033[0m %s\n", $$1, $$2}'

up: ## Up local containers.
	docker compose up -d --build

down: ## Down local containers.
	docker compose down -v

build: ## Build docker containers.
	docker compose build

logs: ## Retrieve last logs.
	docker compose logs -f --tail=200

bash: ## Open bash inside PHP app container.
	docker compose exec php-app bash

composer-install: ## Composer install dependencies inside app.
	docker compose run --rm php-app composer install

migrate: ## Runs database migrations.
	docker compose exec php-app php yii migrate --interactive=0

seed: ## Fill up db with test data (usage: make seed).
	#docker compose exec php-app php yii seed/run 250000 12
	docker compose exec php-app php -d memory_limit=1G yii seed/run 250000 12

clean: ## Clean db.
	docker compose exec php-app php yii clean/run

send: ## Runs email sending (publish tasks to queue).
	docker compose exec php-app php yii send-emails/run

config-show: ## Shows db configurations.
	docker compose exec php-app php yii config/show

config-set: ## Example: make config-set path="campaign.batchSize" value="2000"
	docker compose exec php-app php yii config/set "$(path)" "$(value)"

worker-listen: ## Run PHP worker in foreground to consume queue and record telemetry.
	docker compose exec php-app php yii worker/listen

go-init: ## Init go module (if not)
	docker compose exec go-worker go mod init email-worker

go-deps: ## Fetch all deps for proper test
	docker compose exec go-worker go get github.com/rabbitmq/amqp091-go
	docker compose exec go-worker go get go.mongodb.org/mongo-driver/mongo@v1.17.10
	docker compose exec go-worker go get github.com/go-sql-driver/mysql@v1.8.1
	docker compose exec go-worker go mod tidy

go-build: ## Build go worker as binary
	docker compose exec go-worker go build -o /app/email-worker main.go

go-worker-listen: ## Listen queue
	docker compose exec go-worker /app/email-worker

python-worker-listen: ## Listen queue with async python3+
	docker compose exec python-bench python main.py

php-fiber-worker-listen: ## Listen queue with php fiber
	docker compose run --rm worker-php-fiber composer install --no-dev --ignore-platform-req=ext-mongodb
	docker compose run --rm worker-php-fiber php worker-fiber.php

dotnet-worker-listen: ## Listen queue with C# fiber
	docker compose run --rm worker-dotnet dotnet run --project Worker.csproj

report: ## Build simple report
	docker compose exec python-bench python generate_report.py
