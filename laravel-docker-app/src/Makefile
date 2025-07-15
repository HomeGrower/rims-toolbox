# Makefile für einfache Befehle

# Cache leeren und neu bauen
fresh:
	@echo "🧹 Clearing all caches..."
	@docker-compose exec app php artisan optimize:clear
	@docker-compose exec app rm -rf public/build/*
	@docker-compose exec app rm -rf bootstrap/ssr/*
	@docker-compose exec app rm -rf node_modules/.vite
	@echo "🔄 Restarting Vite..."
	@docker-compose restart node
	@echo "✅ Done! Wait 5 seconds and reload your browser."

# Nur Vite neu starten
vite-restart:
	@docker-compose restart node
	@echo "✅ Vite restarted!"

# Build für Production
build:
	@docker-compose exec app npm run build

# Laravel Cache leeren
clear:
	@docker-compose exec app php artisan optimize:clear

# Logs anzeigen
logs:
	@docker-compose logs -f node

# Status prüfen
status:
	@docker-compose ps

# Hilfe anzeigen
help:
	@echo "Verfügbare Befehle:"
	@echo "  make fresh       - Alle Caches leeren und Vite neu starten"
	@echo "  make vite-restart - Nur Vite neu starten"
	@echo "  make build       - Production Build erstellen"
	@echo "  make clear       - Laravel Caches leeren"
	@echo "  make logs        - Vite Logs anzeigen"
	@echo "  make status      - Container Status anzeigen"