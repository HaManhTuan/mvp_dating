# Makefile for Laravel Dating MVP
.PHONY: help build up down restart clean install migrate seed fresh test logs shell db-shell composer npm cache clear-cache

# Default target
help: ## Show this help message
	@echo "Laravel Dating MVP - Available commands:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# Docker commands
build: ## Build all Docker containers
	docker compose build --no-cache

up: ## Start all services
	docker compose up -d

down: ## Stop all services
	docker compose down

restart: ## Restart all services
	docker compose restart

clean: ## Stop containers and remove volumes
	docker compose down -v
	docker system prune -f

logs: ## Show logs for all services
	docker compose logs -f

logs-app: ## Show logs for app service only
	docker compose logs -f app

logs-nginx: ## Show logs for nginx service only
	docker compose logs -f nginx

logs-db: ## Show logs for database service only
	docker compose logs -f dating_db

# Laravel commands
install: up ## Install dependencies and setup Laravel
	docker compose exec app composer install
	docker compose exec app npm install
	docker compose exec app cp .env.example .env || true
	docker compose exec app php artisan key:generate
	$(MAKE) migrate
	$(MAKE) cache-optimize
	
migrate: ## Run database migrations
	docker compose exec app php artisan migrate

migrate-fresh: ## Fresh migration with seeding
	docker compose exec app php artisan migrate:fresh --seed

seed: ## Run database seeders
	docker compose exec app php artisan db:seed

rollback: ## Rollback last migration
	docker compose exec app php artisan migrate:rollback

# Development commands
shell: ## Access app container shell
	docker compose exec app bash

root-shell: ## Access app container as root
	docker compose exec --user root app bash

db-shell: ## Access MySQL shell
	docker compose exec dating_db mysql -u dating -psecret dating

redis-shell: ## Access Redis CLI
	docker compose exec redis redis-cli

# Composer commands
composer-install: ## Install composer dependencies
	docker compose exec app composer install

composer-update: ## Update composer dependencies
	docker compose exec app composer update

composer-dump: ## Dump composer autoload
	docker compose exec app composer dump-autoload

# NPM commands
npm-install: ## Install npm dependencies
	docker compose exec app npm install

npm-dev: ## Run npm development build
	docker compose exec app npm run dev

npm-build: ## Run npm production build
	docker compose exec app npm run build

npm-watch: ## Watch for changes and rebuild
	docker compose exec app npm run dev -- --watch

# Laravel Artisan commands
cache-clear: ## Clear all Laravel caches
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan view:clear

cache-optimize: ## Optimize Laravel caches
	docker compose exec app php artisan config:cache
	docker compose exec app php artisan route:cache
	docker compose exec app php artisan view:cache

queue-work: ## Start queue worker
	docker compose exec app php artisan queue:work

tinker: ## Access Laravel Tinker
	docker compose exec app php artisan tinker

# Testing commands
test: ## Run PHPUnit tests
	docker compose exec app php artisan test

test-coverage: ## Run tests with coverage
	docker compose exec app php artisan test --coverage

pest: ## Run Pest tests (if using Pest)
	docker compose exec app ./vendor/bin/pest

backup-db: ## Backup database
	docker compose exec dating_db mysqldump -u dating -psecret dating > backup_$(shell date +%Y%m%d_%H%M%S).sql

restore-db: ## Restore database (usage: make restore-db FILE=backup.sql)
	docker compose exec -T dating_db mysql -u dating -psecret dating < $(FILE)

# Development helpers
fresh: clean build up install ## Fresh installation (clean + build + up + install)

reset: ## Reset application (fresh migration + clear cache)
	$(MAKE) migrate-fresh
	$(MAKE) cache-clear

status: ## Show containers status
	docker compose ps

open: ## Open application in browser
	@echo "Opening application..."
	@echo "Main app: http://localhost:1111"
	@echo "MailHog: http://localhost:8025"
	@echo "Database: localhost:3355"

# Production commands
prod-build: ## Build for production
	docker compose -f docker compose.prod.yml build

prod-deploy: ## Deploy to production
	docker compose -f docker compose.prod.yml up -d
	docker compose -f docker compose.prod.yml exec app php artisan migrate --force
	docker compose -f docker compose.prod.yml exec app php artisan config:cache
	docker compose -f docker compose.prod.yml exec app php artisan route:cache
	docker compose -f docker compose.prod.yml exec app php artisan view:cache

# Quick start for new developers
quick-start: ## Quick start for new developers
	@echo "🚀 Setting up Laravel Dating MVP..."
	$(MAKE) fresh
	@echo "✅ Setup complete!"
	@echo ""
	@echo "📱 Application URLs:"
	@echo "   Main app: http://localhost:1111"
	@echo "   MailHog: http://localhost:8025"
	@echo ""
	@echo "🗄️  Database Connection:"
	@echo "   Host: localhost"
	@echo "   Port: 3355"
	@echo "   Database: dating"
	@echo "   Username: dating"
	@echo "   Password: secret"
	@echo ""
	@echo "🎉 Happy coding!"
