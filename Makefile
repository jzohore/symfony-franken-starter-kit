.PHONY: tests
tests: bdd-update tests-domain tests-application tests-infrastructure tests-functional
	@echo "Running all tests 🚀"
	#php bin/phpunit --testdox tests/

bdd-update:
	@echo "Mise à jour de la BDD"
	php bin/console d:d:d --force --if-exists --env=test
	php bin/console d:d:c --env=test
	php bin/console d:s:u --force --env=test
	php bin/console d:f:l --no-interaction --env=test

# Tests Domain uniquement
tests-domain:
	@echo "Running Domain tests 🧠"
	./bin/phpunit --testdox tests/Domain

tests-application:
	@echo "Running Application tests 🎯"
	./bin/phpunit --testdox tests/Application

# Tests Fonctionnels (HTTP, LiveComponent)
tests-functional:
	@echo "Running Functional tests 🧪"
	./bin/phpunit --testdox tests/Functional

tests-infrastructure:
	@echo "Running Infrastructure tests 🏗️"
	./bin/phpunit --testdox tests/Infrastructure

#entity:
#	php bin/console make:entity --with-uuid

create-bdd:
	php bin/console d:d:d --force
	php bin/console d:d:d --force --if-exists --connection=dev
	php bin/console d:d:c
	php bin/console d:d:c --connection=dev
	php bin/console d:s:u --force --em=default
	php bin/console d:s:u --force --em=dev

migration:
	php bin/console make:migration --formatted

fixtures:
	php bin/console make:fixtures

factory:
	php bin/console make:factory

migrate:
	php bin/console d:m:m --em=default
	php bin/console d:m:m --em=dev

controller:
	php bin/console make:controller

form:
	php bin/console make:form

command:
	php bin/console make:command


user:
	php bin/console app:user:bootstrap

entity:
	php bin/console app:make:domain-entity

colima:
	colima start

mailhog:
	docker run -d -p 1025:1025 -p 8025:8025 mailhog/mailhog

testpanther:
	PANTHER_NO_HEADLESS=1 symfony php vendor/bin/simple-phpunit

script:
	php bin/console delete-reset-password
	php bin/console delete-onboarding-users
	php bin/console link-order
	php bin/console check-order-status

diff:
	php bin/console doctrine:migrations:diff

faqs:
	php bin/console app:seed:faqs-drsmiracle --status=PUBLISHED

live:
	php bin/console app:make:live-list-component

compliance:
	php bin/console app:seed:compliance-drsmiracle --status=PUBLISHED

carriers:
	php bin/console app:seed:carriers

module:
	php bin/console app:make:application-module

integration:
	php bin/console app:integration:set

serve:
	symfony serve

tailwind-b:
	php bin/console tailwind:build --watch

set_maintenance_ip_to_expired:
	php bin/console app:set_maintenance_ip_to_expired

lint: ## Vérifie la qualité du code (lecture seule)
	@echo "🔍 PHP CS Fixer (dry-run)..."
	vendor/bin/php-cs-fixer fix --dry-run --diff --verbose
	@echo "\n🔍 PHPStan (level 9)..."
	vendor/bin/phpstan analyse --memory-limit=1G
	@echo "\n🔍 PHP Code Sniffer..."
	vendor/bin/phpcs

fix: ## Corrige automatiquement le code
	@echo "🔧 PHP CS Fixer..."
	vendor/bin/php-cs-fixer fix --verbose
	@echo "\n🔧 Rector..."
	vendor/bin/rector process
	@echo "\n🔧 PHP Code Beautifier..."
	vendor/bin/phpcbf

test: ## Lance les tests
	@echo "🧪 PHPUnit..."
	php bin/phpunit --testdox

test-coverage: ## Tests avec couverture de code
	@echo "🧪 PHPUnit avec coverage..."
	XDEBUG_MODE=coverage php bin/phpunit --coverage-html var/coverage
	@echo "📊 Rapport : var/coverage/index.html"

quality: lint test ## Vérifie tout (lint + tests)
	@echo "\n✅ Code propre et testé !"

ci: ## Pipeline CI complet
	composer validate --strict
	composer install --no-interaction --prefer-dist --optimize-autoloader
	make quality

security: ## Vérifie les vulnérabilités
	composer audit
	symfony security:check

metrics: ## Génère les métriques de code
	@echo "📊 Génération des métriques..."
	vendor/bin/phpstan analyse --error-format=table > var/phpstan-report.txt
	vendor/bin/phpcs --report=summary > var/phpcs-report.txt
	@echo "✅ Rapports générés dans var/"

clean: ## Nettoie les caches
	rm -rf var/cache/* var/log/* var/coverage/*
	php bin/console cache:clear

pre-commit: fix lint ## Hook pre-commit
	@echo "✅ Pre-commit checks passed!"
