#!/bin/bash

# Couleurs pour le terminal
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Vérifier que Docker est installé
if ! command -v docker &> /dev/null; then
    echo -e "${RED} Docker n'est pas installé. Veuillez l'installer d'abord.${NC}"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED} Docker Compose n'est pas installé. Veuillez l'installer d'abord.${NC}"
    exit 1
fi

echo -e "${GREEN} Docker et Docker Compose détectés${NC}"
echo ""

# Étape 1 : Vérifier les fichiers de configuration
echo -e "${YELLOW} Vérification des fichiers de configuration...${NC}"

if [ ! -f ".env" ]; then
    echo -e "${RED} Fichier .env manquant${NC}"
    exit 1
fi

if [ ! -f "docker-compose.yml" ]; then
    echo -e "${RED} Fichier docker-compose.yml manquant${NC}"
    exit 1
fi

echo -e "${GREEN} Fichiers de configuration OK${NC}"
echo ""

# Étape 2 : Arrêter les conteneurs existants (si nécessaire)
echo -e "${YELLOW} Nettoyage des conteneurs existants...${NC}"
docker-compose down 2>/dev/null
echo ""

# Étape 3 : Lancer les services Docker
echo -e "${YELLOW} Démarrage des services Docker...${NC}"
echo -e "${BLUE}   - PostgreSQL Source (port 5432)${NC}"
echo -e "${BLUE}   - PostgreSQL Target (port 5433)${NC}"
echo -e "${BLUE}   - Zookeeper${NC}"
echo -e "${BLUE}   - Kafka${NC}"
echo -e "${BLUE}   - Kafka Producer${NC}"
echo -e "${BLUE}   - Kafka Consumer (ETL)${NC}"
echo -e "${BLUE}   - Kafka UI (port 8080)${NC}"
echo -e "${BLUE}   - Prometheus (port 9090)${NC}"
echo -e "${BLUE}   - Grafana (port 3000)${NC}"
echo -e "${BLUE}   - pgAdmin (port 5050)${NC}"
echo ""

docker-compose up -d --build

if [ $? -ne 0 ]; then
    echo -e "${RED} Erreur lors du démarrage des services Docker${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN} Services Docker démarrés${NC}"
echo ""

# Étape 4 : Attendre que les services soient prêts
echo -e "${YELLOW} Attente du démarrage des services (30 secondes)...${NC}"
sleep 30
echo ""

# Étape 5 : Vérifier l'état des services
echo -e "${YELLOW} Vérification de l'état des services...${NC}"
docker-compose ps
echo ""

# Étape 6 : Installer les dépendances Laravel (si vendor n'existe pas)
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW} Installation des dépendances Laravel...${NC}"
    composer install
    echo ""
fi

# Étape 7 : Exécuter les migrations
echo -e "${YELLOW}  Exécution des migrations...${NC}"

echo -e "${BLUE}   Database source (port 5434)...${NC}"
php artisan migrate --database=pgsql --force

echo -e "${BLUE}   Database target (port 5435)...${NC}"
php artisan migrate --database=pgsql_second --force

echo ""

# Étape 8 : Lancer le serveur Laravel
echo -e "${YELLOW} Démarrage du serveur Laravel...${NC}"
echo -e "${BLUE}   Le serveur va démarrer sur http://localhost:8000${NC}"
echo ""

# Vérifier si le port 8000 est libre
if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null ; then
    echo -e "${RED}  Le port 8000 est déjà utilisé${NC}"
    echo -e "${YELLOW}   Arrêtez le processus existant ou utilisez un autre port${NC}"
    echo ""
fi

echo -e "${GREEN}════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN} INSTALLATION TERMINÉE !${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE} Services disponibles :${NC}"
echo ""
echo -e "   ${GREEN} Laravel API:${NC}         http://localhost:8000"
echo -e "   ${GREEN} Prometheus:${NC}          http://localhost:9090"
echo -e "   ${GREEN} Grafana:${NC}             http://localhost:3000 ${YELLOW}(admin/admin123)${NC}"
echo -e "   ${GREEN} Kafka UI:${NC}            http://localhost:8080"
echo -e "   ${GREEN}  pgAdmin:${NC}             http://localhost:5050 ${YELLOW}(admin@admin.com/admin123)${NC}"
echo ""
echo -e "${BLUE}  Bases de données :${NC}"
echo ""
echo -e "   ${GREEN}PostgreSQL Source:${NC}  localhost:5434 ${YELLOW}(laravel_user/laravel_password)${NC}"
echo -e "   ${GREEN}PostgreSQL Target:${NC}  localhost:5435 ${YELLOW}(laravel_user/laravel_password)${NC}"
echo ""
echo -e "${BLUE} Commandes utiles :${NC}"
echo ""
echo -e "   ${YELLOW}# Voir les logs Docker${NC}"
echo -e "   docker-compose logs -f"
echo ""
echo -e "   ${YELLOW}# Voir les logs du consumer Kafka${NC}"
echo -e "   docker-compose logs -f kafka_consumer"
echo ""
echo -e "   ${YELLOW}# Tester la synchronisation${NC}"
echo -e "   curl http://localhost:8000/api/v1/sync-stats"
echo ""
echo -e "${BLUE} Pour démarrer Laravel, exécutez dans un nouveau terminal :${NC}"
echo -e "   ${YELLOW}php artisan serve${NC}"
echo ""
echo -e "${GREEN}════════════════════════════════════════════════════════════${NC}"
