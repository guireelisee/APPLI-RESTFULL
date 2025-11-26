#!/bin/bash

echo "🚀 Démarrage des services Docker..."
echo ""

# Démarrer Docker
docker-compose up -d

# Attendre PostgreSQL
echo "⏳ Attente de PostgreSQL..."
sleep 10

# Vérifier l'état
echo ""
echo "📊 État des services:"
docker-compose ps

echo ""
echo "✅ Services Docker démarrés!"
echo ""
echo "📋 Services disponibles:"
echo "   - PostgreSQL Source:  localhost:5432"
echo "   - PostgreSQL Target:  localhost:5433"
echo "   - Kafka:              localhost:9093"
echo "   - Kafka UI:           http://localhost:8080"
echo "   - Prometheus:         http://localhost:9090"
echo "   - Grafana:            http://localhost:3000 (admin/admin123)"
echo ""
echo "🔧 Prochaine étape:"
echo "   1. Générer la clé: php artisan key:generate"
echo "   2. Migrer: php artisan migrate"
echo "   3. Lancer Laravel: php artisan serve"
echo ""
