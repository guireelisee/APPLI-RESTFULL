#!/usr/bin/env python3
"""
Kafka Consumer - ETL Service
Consomme les messages Kafka et synchronise vers db_target
Extract → Transform → Load
"""
import json
import os
import logging
import time
from kafka import KafkaConsumer
import psycopg2
from psycopg2.extras import RealDictCursor

# Configuration logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s'
)
logger = logging.getLogger(__name__)

# Configuration Kafka
KAFKA_BOOTSTRAP_SERVERS = os.getenv('KAFKA_BOOTSTRAP_SERVERS', 'kafka:9092')
KAFKA_GROUP_ID = os.getenv('KAFKA_GROUP_ID', 'laravel_etl_group')
KAFKA_TOPICS = ['clients-sync', 'reservations-sync']

# Configuration DB Target
DB_TARGET = {
    'host': os.getenv('DB_TARGET_HOST', 'db_target'),
    'port': int(os.getenv('DB_TARGET_PORT', 5432)),
    'database': os.getenv('DB_TARGET_DB', 'db_target'),
    'user': os.getenv('DB_TARGET_USER', 'laravel_user'),
    'password': os.getenv('DB_TARGET_PASSWORD', 'laravel_password')
}

def get_db_connection():
    """Obtenir une connexion à la base target"""
    return psycopg2.connect(**DB_TARGET)

def wait_for_kafka():
    """Attendre que Kafka soit disponible"""
    max_retries = 30
    retry_count = 0

    while retry_count < max_retries:
        try:
            consumer = KafkaConsumer(
                bootstrap_servers=[KAFKA_BOOTSTRAP_SERVERS],
                request_timeout_ms=5000
            )
            consumer.close()
            logger.info("✅ Kafka is ready!")
            return True
        except Exception as e:
            retry_count += 1
            logger.warning(f"⏳ Waiting for Kafka... ({retry_count}/{max_retries})")
            time.sleep(2)

    logger.error("❌ Kafka not available after 30 retries")
    return False

def process_client_message(message):
    """
    ETL pour les clients
    Extract → Transform → Load
    """
    try:
        # EXTRACT
        data = json.loads(message.value.decode('utf-8'))
        action = data.get('action')
        client = data.get('data', {})

        logger.info(f"📥 [EXTRACT] Client: action={action}, id={client.get('id')}")

        # TRANSFORM
        transformed_client = {
            'id': int(client['id']),
            'nom': str(client['nom']),
            'prenom': str(client['prenom']),
            'email': str(client['email']),
            'telephone': str(client['telephone']),
            'adresse': client.get('adresse', ''),
            'ville': client.get('ville', ''),
            'pays': client.get('pays', 'Burkina Faso'),
            'created_at': client['created_at'],
            'updated_at': client['updated_at']
        }

        logger.info(f"🔄 [TRANSFORM] Client {transformed_client['id']} transformed")

        # LOAD
        conn = get_db_connection()
        cur = conn.cursor()

        if action in ['created', 'updated']:
            cur.execute("""
                INSERT INTO clients
                (id, nom, prenom, email, telephone, adresse, ville, pays, created_at, updated_at)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                ON CONFLICT (id) DO UPDATE SET
                    nom = EXCLUDED.nom,
                    prenom = EXCLUDED.prenom,
                    email = EXCLUDED.email,
                    telephone = EXCLUDED.telephone,
                    adresse = EXCLUDED.adresse,
                    ville = EXCLUDED.ville,
                    pays = EXCLUDED.pays,
                    updated_at = EXCLUDED.updated_at
            """, (
                transformed_client['id'],
                transformed_client['nom'],
                transformed_client['prenom'],
                transformed_client['email'],
                transformed_client['telephone'],
                transformed_client['adresse'],
                transformed_client['ville'],
                transformed_client['pays'],
                transformed_client['created_at'],
                transformed_client['updated_at']
            ))
            logger.info(f"✅ [LOAD] Client {transformed_client['id']} upserted")

        elif action == 'deleted':
            cur.execute("DELETE FROM clients WHERE id = %s", (transformed_client['id'],))
            logger.info(f"✅ [LOAD] Client {transformed_client['id']} deleted")

        conn.commit()
        cur.close()
        conn.close()

        return True

    except Exception as e:
        logger.error(f"❌ [ERROR] Client ETL failed: {e}")
        return False

def process_reservation_message(message):
    """
    ETL pour les réservations
    Extract → Transform → Load
    """
    try:
        # EXTRACT
        data = json.loads(message.value.decode('utf-8'))
        action = data.get('action')
        reservation = data.get('data', {})

        logger.info(f"📥 [EXTRACT] Reservation: action={action}, id={reservation.get('id')}")

        # TRANSFORM
        transformed_reservation = {
            'id': int(reservation['id']),
            'client_id': int(reservation['client_id']),
            'numero_reservation': str(reservation['numero_reservation']),
            'service': str(reservation['service']),
            'date_reservation': reservation['date_reservation'],
            'date_debut': reservation['date_debut'],
            'date_fin': reservation.get('date_fin'),
            'montant': float(reservation['montant']),
            'statut': str(reservation['statut']),
            'commentaire': reservation.get('commentaire', ''),
            'created_at': reservation['created_at'],
            'updated_at': reservation['updated_at']
        }

        logger.info(f"🔄 [TRANSFORM] Reservation {transformed_reservation['id']} transformed")

        # LOAD
        conn = get_db_connection()
        cur = conn.cursor()

        if action in ['created', 'updated']:
            cur.execute("""
                INSERT INTO reservations
                (id, client_id, numero_reservation, service, date_reservation,
                 date_debut, date_fin, montant, statut, commentaire, created_at, updated_at)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                ON CONFLICT (id) DO UPDATE SET
                    client_id = EXCLUDED.client_id,
                    service = EXCLUDED.service,
                    date_reservation = EXCLUDED.date_reservation,
                    date_debut = EXCLUDED.date_debut,
                    date_fin = EXCLUDED.date_fin,
                    montant = EXCLUDED.montant,
                    statut = EXCLUDED.statut,
                    commentaire = EXCLUDED.commentaire,
                    updated_at = EXCLUDED.updated_at
            """, (
                transformed_reservation['id'],
                transformed_reservation['client_id'],
                transformed_reservation['numero_reservation'],
                transformed_reservation['service'],
                transformed_reservation['date_reservation'],
                transformed_reservation['date_debut'],
                transformed_reservation['date_fin'],
                transformed_reservation['montant'],
                transformed_reservation['statut'],
                transformed_reservation['commentaire'],
                transformed_reservation['created_at'],
                transformed_reservation['updated_at']
            ))
            logger.info(f"✅ [LOAD] Reservation {transformed_reservation['id']} upserted")

        elif action == 'deleted':
            cur.execute("DELETE FROM reservations WHERE id = %s", (transformed_reservation['id'],))
            logger.info(f"✅ [LOAD] Reservation {transformed_reservation['id']} deleted")

        conn.commit()
        cur.close()
        conn.close()

        return True

    except Exception as e:
        logger.error(f"❌ [ERROR] Reservation ETL failed: {e}")
        return False

def main():
    """Boucle principale du consumer"""
    logger.info("=" * 70)
    logger.info("🚀 Kafka Consumer ETL Service Starting...")
    logger.info("=" * 70)
    logger.info(f"📍 Kafka Brokers: {KAFKA_BOOTSTRAP_SERVERS}")
    logger.info(f"📂 Topics: {', '.join(KAFKA_TOPICS)}")
    logger.info(f"👥 Consumer Group: {KAFKA_GROUP_ID}")
    logger.info(f"🗄️  Target DB: {DB_TARGET['host']}:{DB_TARGET['port']}/{DB_TARGET['database']}")
    logger.info("=" * 70)

    # Attendre Kafka
    if not wait_for_kafka():
        logger.error("Cannot start consumer without Kafka")
        return

    # Créer le consumer
    consumer = KafkaConsumer(
        *KAFKA_TOPICS,
        bootstrap_servers=[KAFKA_BOOTSTRAP_SERVERS],
        group_id=KAFKA_GROUP_ID,
        auto_offset_reset='earliest',
        enable_auto_commit=True,
        value_deserializer=lambda m: m
    )

    logger.info("✅ Kafka Consumer ready! Waiting for messages...")
    logger.info("")

    # Consommer les messages
    try:
        for message in consumer:
            topic = message.topic

            if topic == 'clients-sync':
                process_client_message(message)
            elif topic == 'reservations-sync':
                process_reservation_message(message)

    except KeyboardInterrupt:
        logger.info("🛑 Consumer stopped by user")
    except Exception as e:
        logger.error(f"❌ Consumer error: {e}")
    finally:
        consumer.close()
        logger.info("👋 Consumer closed")

if __name__ == '__main__':
    main()
