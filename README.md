# Laravel ETL avec Apache Kafka - IBAM 2025

Développer une application _Restfull avec Laravel_, en utilisant _WSO2 ou apache kafka_ pour ETL _(Extract, Transform, Load)_ pour se connecter/synchroniser sur _deux bases données_.

## Table des matières

-   [Architecture](#architecture)
-   [Prérequis](#prérequis)
-   [Installation](#installation)
-   [Utilisation](#utilisation)
-   [Services disponibles](#services-disponibles)

---

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Laravel API                              │
│                  CRUD Clients & Réservations                 │
│                           ↓                                  │
│              Observers (ClientObserver, etc.)                │
│                           ↓                                  │
│                  KafkaProducerService                        │
└─────────────────────────┬───────────────────────────────────┘
                          │ HTTP POST
                          ↓
┌─────────────────────────────────────────────────────────────┐
│              Kafka Producer (Docker - Flask)                 │
│                    Port 5001 (HTTP)                          │
│                           ↓                                  │
│                 Publie vers Kafka Broker                     │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ↓
┌─────────────────────────────────────────────────────────────┐
│              Apache Kafka + Zookeeper                        │
│                                                              │
│  Topics:                                                     │
│    • clients-sync                                            │
│    • reservations-sync                                       │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ↓
┌─────────────────────────────────────────────────────────────┐
│              Kafka Consumer (Docker - Python)                │
│                      ETL Pipeline                            │
│                                                              │
│         Extract → Transform → Load                           │
│                           ↓                                  │
│               PostgreSQL Target (Port 5435)                  │
└─────────────────────────────────────────────────────────────┘
```

**Flux de données :**

1. **CREATE/UPDATE/DELETE** → Laravel API (db_source)
2. **Observer** détecte le changement
3. **KafkaProducerService** publie vers Kafka Producer (HTTP)
4. **Kafka Producer** publie le message dans Kafka
5. **Kafka Consumer** consomme le message
6. **ETL** : Extract → Transform → Load
7. **INSERT/UPDATE/DELETE** → db_target

---

## Prérequis

### Logiciels requis

-   **Docker Desktop** (avec Docker Compose)
-   **PHP 8.3+**
-   **Composer**
-   **macOS, Linux ou Windows avec WSL2**

### Ports utilisés

| Service           | Port |
| ----------------- | ---- |
| Laravel API       | 8000 |
| PostgreSQL Source | 5434 |
| PostgreSQL Target | 5435 |
| Kafka Producer    | 5001 |
| Kafka Broker      | 9093 |
| Kafka UI          | 8080 |
| Prometheus        | 9090 |
| Grafana           | 3000 |
| pgAdmin           | 5050 |

⚠️ **Assurez-vous que ces ports sont libres avant de lancer le projet.**

---

## Installation

### 1. Cloner le projet

```bash
git clone https://github.com/guireelisee/APPLI-RESTFULL.git laravel-reservation-api
cd laravel-reservation-api
```

### 2. Configuration

Copier le fichier `.env.example` et configurer :

```bash
cp .env.example .env
```

**Variables importantes dans `.env` :**

```env
# Base de données Source
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5434
DB_DATABASE=db_source
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_password

# Base de données Target
DB_CONNECTION_SECOND=pgsql
DB_HOST_SECOND=127.0.0.1
DB_PORT_SECOND=5435
DB_DATABASE_SECOND=db_target
DB_USERNAME_SECOND=laravel_user
DB_PASSWORD_SECOND=laravel_password

# Kafka Producer
KAFKA_PRODUCER_URL=http://localhost:5001
```

### 3. Lancer le script d'installation

```bash
chmod +x start.sh
./start.sh
```

Le script va :

-   ✅ Vérifier Docker
-   ✅ Démarrer tous les services Docker
-   ✅ Installer les dépendances Laravel
-   ✅ Exécuter les migrations
-   ✅ Afficher les URLs des services

### 4. Démarrer Laravel

Dans un **nouveau terminal** :

```bash
php artisan serve
```

---

## 📖 Utilisation

### Créer un client

```bash
curl -X POST http://localhost:8000/api/v1/clients \
  -H "Content-Type: application/json" \
  -d '{
    "nom": "Traoré",
    "prenom": "Amadou",
    "email": "amadou.traore@test.bf",
    "telephone": "+22670123456",
    "ville": "Ouagadougou",
    "pays": "Burkina Faso"
  }'
```

### Créer une réservation

```bash
curl -X POST http://localhost:8000/api/v1/reservations \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": 1,
    "service": "Hotel",
    "date_reservation": "2025-12-01",
    "date_debut": "2025-12-15",
    "date_fin": "2025-12-20",
    "montant": 250000,
    "statut": "en_attente"
  }'
```

### Vérifier la synchronisation

```bash
curl http://localhost:8000/api/v1/sync-stats
```

**Résultat attendu :**

```json
{
    "success": true,
    "data": {
        "clients": {
            "source": 1,
            "target": 1,
            "synced": true,
            "difference": 0
        },
        "reservations": {
            "source": 1,
            "target": 1,
            "synced": true,
            "difference": 0
        }
    }
}
```

---

## Services disponibles

### Laravel API

```
URL: http://localhost:8000
```

**Endpoints principaux :**

-   `GET /api/v1/clients` - Liste des clients
-   `POST /api/v1/clients` - Créer un client
-   `GET /api/v1/clients/{id}` - Détails d'un client
-   `PUT /api/v1/clients/{id}` - Modifier un client
-   `DELETE /api/v1/clients/{id}` - Supprimer un client
-   `GET /api/v1/sync-stats` - Statistiques de synchronisation
-   `GET /metrics` - Métriques Prometheus

### Grafana

```
URL: http://localhost:3000
Credentials: admin / admin123
```

**Dashboard disponible :**

-   Laravel ETL - Synchronisation Kafka

### Prometheus

```
URL: http://localhost:9090
```

**Métriques disponibles :**

-   `laravel_clients_source_total`
-   `laravel_clients_target_total`
-   `laravel_sync_status`

### Kafka UI

```
URL: http://localhost:8080
```

Visualisation des topics et messages Kafka.

---

## Auteurs

1. BAYALA Y. Allan Lionel
2. GUIRE Elisée A. Enoch
3. NABALOUM C. Yassine
4. PAYAO Rachidatou

---

## Licence

Projet académique - IBAM 2025

---
