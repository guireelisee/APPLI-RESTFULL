#!/usr/bin/env python3
"""
Kafka Producer HTTP Service
Reçoit les requêtes HTTP de Laravel et publie vers Kafka
"""
from flask import Flask, request, jsonify
from kafka import KafkaProducer
import json
import logging
import os

app = Flask(__name__)

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

KAFKA_BOOTSTRAP_SERVERS = os.getenv('KAFKA_BOOTSTRAP_SERVERS', 'kafka:9092')

def get_producer():
    return KafkaProducer(
        bootstrap_servers=[KAFKA_BOOTSTRAP_SERVERS],
        value_serializer=lambda v: json.dumps(v).encode('utf-8'),
        acks='all',
        retries=3
    )

@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'ok', 'service': 'Kafka Producer'})

@app.route('/publish', methods=['POST'])
def publish():
    """Publier un message vers Kafka"""
    try:
        data = request.json
        topic = data.get('topic')
        message = data.get('message')

        if not topic or not message:
            return jsonify({
                'success': False,
                'error': 'Missing topic or message'
            }), 400

        logger.info(f"📤 Publishing to {topic}: {message.get('action')} - {message.get('entity')}")

        producer = get_producer()
        future = producer.send(topic, message)
        result = future.get(timeout=10)

        producer.flush()
        producer.close()

        logger.info(f"✅ Published to {topic}")

        return jsonify({
            'success': True,
            'topic': topic,
            'partition': result.partition,
            'offset': result.offset
        })

    except Exception as e:
        logger.error(f"❌ Publish failed: {e}")
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

if __name__ == '__main__':
    logger.info("🚀 Kafka Producer HTTP Service starting on port 5001...")
    app.run(host='0.0.0.0', port=5001, debug=True)
