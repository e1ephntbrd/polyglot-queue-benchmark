import asyncio
import json
import time
import aio_pika
import aiomysql
from motor.motor_asyncio import AsyncIOMotorClient
import aiosmtplib
from email.message import EmailMessage
from datetime import datetime, timezone

# Connection config
MYSQL_CONFIG = {
    "host": "mysql",
    "port": 3306,
    "user": "root",
    "password": "root",
    "db": "app"
}
MONGO_URI = "mongodb://mongodb:27017"
RABBIT_URL = "amqp://guest:guest@rabbitmq:5672/"
MAILHOG_HOST = "mailhog"
MAILHog_PORT = 1025

async def get_active_emails(pool, user_ids):
    if not user_ids:
        return []

    async with pool.acquire() as conn:
        async with conn.cursor() as cursor:
            format_strings = ','.join(['%s'] * len(user_ids))
            query = f"SELECT email FROM users WHERE id IN ({format_strings}) AND is_active = 1"
            await cursor.execute(query, user_ids)
            result = await cursor.fetchall()
            return [row[0] for row in result]

async def send_emails(emails):
    for email_addr in emails:
        message = EmailMessage()
        message["From"] = "campaign@benchmark.local"
        message["To"] = email_addr
        message["Subject"] = "Email Campaign Benchmark (Python)"
        message.set_content("This is a test campaign email sent by Python Worker.")

        await aiosmtplib.send(
            message,
            hostname=MAILHOG_HOST,
            port=MAILHog_PORT,
        )

async def process_message(message: aio_pika.IncomingMessage, mysql_pool, mongo_collection):
    start_time = time.perf_counter()
    status = "success"
    message_id = message.message_id or f"msg_{time.time_ns()}"

    try:
        body = json.loads(message.body.decode())

        if isinstance(body, list):
            user_ids = [int(i) for i in body]
        elif isinstance(body, dict) and "ids" in body:
            user_ids = [int(i) for i in body["ids"]]
        else:
            user_ids = []

        if user_ids:
            emails = await get_active_emails(mysql_pool, user_ids)
            if emails:
                await send_emails(emails)

        await message.ack()
    except Exception as e:
        status = "failed"
        print(f"Worker error for msg {message_id}: {e}")
        await message.nack(requeue=True)

    # telemetry
    duration_ms = (time.perf_counter() - start_time) * 1000.0
    metric = {
        "worker": "python-worker",
        "message_id": message_id,
        "status": status,
        "duration_ms": duration_ms,
        "timestamp": datetime.now(timezone.utc)
    }
    await mongo_collection.insert_one(metric)

async def main():
    # Init
    mysql_pool = await aiomysql.create_pool(**MYSQL_CONFIG)
    mongo_client = AsyncIOMotorClient(MONGO_URI)
    mongo_collection = mongo_client.app.benchmark_metrics

    #  Connect to RabbitMQ
    connection = await aio_pika.connect_robust(RABBIT_URL)
    channel = await connection.channel()
    await channel.set_qos(prefetch_count=100)

    queue = await channel.declare_queue("email_campaign_queue", durable=True)

    print(" [*] Python worker ready and listening for messages. To exit press CTRL+C")

    async with queue.iterator() as queue_iter:
        async for message in queue_iter:
            # asyncio task of msg processing
            asyncio.create_task(process_message(message, mysql_pool, mongo_collection))

    await mysql_pool.ensure_closed()

if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        print(" [x] Shutting down Python worker...")
