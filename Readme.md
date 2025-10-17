# Symfony Starter Kit

[![PHP Version](https://img.shields.io/badge/PHP-8.3-blue.svg)](https://www.php.net/)
[![Symfony Version](https://img.shields.io/badge/Symfony-7.1-black.svg)](https://symfony.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A modern Symfony 7.1 starter powered by **FrankenPHP**, **PostgreSQL**, and **Docker** — ready for production and local development. Designed to help developers start projects quickly while following best practices, including Hexagonal Architecture and clean code principles.

---

## 🚀 Quick Start

```bash
git clone https://github.com/jzohore/symfony-franken-starter-kit.git
cd symfony-franken-starter-kit/docker
docker-compose exec app bash
composer install
```

Access your app at **[http://localhost](http://localhost)**

---

## ⚙️ What’s Included

* 🐘 **Symfony 7.3** — latest framework version
* ⚡ **FrankenPHP** — modern Go-based PHP server
* 🐳 **Docker Compose** — PostgreSQL 16 + PHP + Caddy setup
* 📦 **Doctrine ORM** — UUIDs, timestamps, soft deletes
* 🔄 **Messenger - Redis - RabbitMq** — background jobs & async tasks
* 🔄 **Mercure** — RealTime
* 🧰 **Makefile** — 20+ handy commands for dev & ops
* 🧪 Ready for testing, CI/CD, and real deployment
* 🏗️ **Hexagonal Architecture Friendly Command**:

```php
#[AsCommand(
    name: 'app:make:domain-entity',
    description: 'Crée une entité Domain/<Nom>/<Nom>.php, son <Nom>RepositoryInterface, et src/Infrastructure/Repository/<Nom>Repository (Doctrine).',
)]
```

This command is a **developer tip** to accelerate creating clean domain entities in a Hexagonal Architecture setup:

* Automatically generates your `Domain` entity and its repository interface.
* Creates a Doctrine repository in the `Infrastructure` layer.
* Encourages separation of concerns, keeping your domain logic independent of infrastructure.
* Saves time and reduces boilerplate code when starting new entities.

> Tip: Use this command every time you need a new domain entity to maintain a clean and consistent architecture across your project.

---

## 🧩 Makefile Commands

| Command                  | Description                                        |
| ------------------------ | -------------------------------------------------- |
| `make build`             | Build Docker images                                |
| `make up`                | Start containers                                   |
| `make down`              | Stop containers                                    |
| `make restart`           | Restart containers                                 |
| `make logs`              | Show app logs                                      |
| `make logs-all`          | Show all logs                                      |
| `make shell`             | Enter app container shell                          |
| `make db-shell`          | Enter PostgreSQL shell                             |
| `make redis-shell`       | Enter Redis shell                                  |
| `make rabbitmq-shell`    | Enter RabbitMQ shell                               |
| `make composer`          | Run composer install/update                        |
| `make sf c=COMMAND`      | Symfony console shortcut                           |
| `make migrate`           | Run Doctrine migrations                            |
| `make fixtures`          | Load Doctrine fixtures                             |
| `make cache-clear`       | Clear Symfony cache                                |
| `make test`              | Run tests                                          |
| `make messenger-consume` | Consume messenger messages                         |
| `make clean`             | Clean containers, volumes, images                  |
| `make install`           | Full install (git pull + build + start + composer) |

---

## 🤝 Contributing

Pull requests are welcome!
Open an issue for bugs or ideas.
Follow PSR-12 and keep commits clean.

---

## 🪪 License

This project is open-sourced under the **MIT License**.
