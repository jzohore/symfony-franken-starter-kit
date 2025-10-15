.PHONY: build

build:
	@echo "Build Docker images"
	docker-compose build --no-cache
up: ## Start containers
	@echo "Start containers"
	docker-compose up -d

down: ## Stop containers
	docker-compose down

restart: down up ## Restart containers

logs: ## Show logs
	docker-compose logs -f app

logs-all: ## Show all logs
	docker-compose logs -f

shell: ## Enter app container shell
	docker-compose exec app sh

db-shell: ## Enter PostgreSQL shell
	docker-compose exec postgres psql -U launchkit -d launchkit

redis-shell: ## Enter Redis shell
	docker-compose exec redis redis-cli -a launchkit

rabbitmq-shell: ## Enter RabbitMQ shell
	docker-compose exec rabbitmq rabbitmqctl status

composer: ## Run composer install
	docker-compose exec app composer install
	docker-compose exec app composer update

sf: ## Symfony console shortcut (usage: make sf c=cache:clear)
	docker-compose exec app php bin/console $(c)

migrate: ## Run migrations
	docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction

fixtures: ## Load fixtures
	docker-compose exec app php bin/console doctrine:fixtures:load --no-interaction

cache-clear: ## Clear cache
	docker-compose exec app php bin/console cache:clear

test: ## Run tests
	docker-compose exec app php bin/phpunit

messenger-consume: ## Consume messenger messages
	docker-compose exec app php bin/console messenger:consume async -vv

clean: ## Clean everything (containers, volumes, images)
	docker-compose down -v --remove-orphans
	docker system prune -af

install: build up composer ## Full install (build + start + composer)
	@echo "🚀 Installation complete!"
	@echo "📍 App: http://localhost"
	@echo "📍 Adminer: http://localhost:8080"
	@echo "📍 RabbitMQ: http://localhost:15672 (user: launchkit, pass: launchkit)"
	@echo "📍 MailHog: http://localhost:8025"