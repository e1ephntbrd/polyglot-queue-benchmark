using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.Net.Sockets;
using System.Text;
using System.Text.Json;
using System.Threading.Tasks;
using MongoDB.Bson;
using MongoDB.Driver;
using MySqlConnector;
using RabbitMQ.Client;
using RabbitMQ.Client.Events;

class Program
{
    private static readonly string MySqlConnString = Environment.GetEnvironmentVariable("MYSQL_CONN_STRING") 
        ?? "Server=mysql;Port=3306;Database=app;Uid=root;Pwd=root;Pooling=true;MinPoolSize=10;MaxPoolSize=100;ConnectionTimeout=5;";

    private static readonly string MongoUri = Environment.GetEnvironmentVariable("MONGO_URI")
        ?? "mongodb://mongodb:27017";

    private static readonly string RabbitUrl = Environment.GetEnvironmentVariable("RABBIT_URL")
        ?? "amqp://guest:guest@rabbitmq:5672/";

    private static readonly string MailhogHost = Environment.GetEnvironmentVariable("MAILHOG_HOST")
        ?? "mailhog";

    private static readonly int MailhogPort = int.TryParse(Environment.GetEnvironmentVariable("MAILHOG_PORT"), out var port)
        ? port
        : 1025;

    static async Task Main(string[] args)
    {
        var mongoClient = new MongoClient(MongoUri);
        var mongoCollection = mongoClient.GetDatabase("app").GetCollection<BsonDocument>("benchmark_metrics");

        var factory = new ConnectionFactory()
        {
            Uri = new Uri(RabbitUrl),
            DispatchConsumersAsync = true
        };

        using var connection = factory.CreateConnection();
        using var channel = connection.CreateModel();

        channel.QueueDeclare(queue: "email_campaign_queue", durable: true, exclusive: false, autoDelete: false);
        channel.BasicQos(prefetchSize: 0, prefetchCount: 100, global: false);

        var consumer = new AsyncEventingBasicConsumer(channel);
        consumer.Received += (model, ea) =>
        {
            byte[] safeBodyBytes = ea.Body.ToArray();
            ulong deliveryTag = ea.DeliveryTag;
            string messageId = ea.BasicProperties.MessageId ?? $"msg_{DateTime.UtcNow.Ticks}";

            _ = Task.Run(async () =>
            {
                var stopwatch = Stopwatch.StartNew();
                string status = "failed"; // Старт за замовчуванням

                try
                {
                    var bodyJson = Encoding.UTF8.GetString(safeBodyBytes).TrimEnd('\0', ' ', '\r', '\n', '\t');

                    using var doc = JsonDocument.Parse(bodyJson);
                    var root = doc.RootElement;

                    List<int> userIds = new List<int>();

                    void ExtractId(JsonElement element)
                    {
                        if (element.ValueKind == JsonValueKind.Number && element.TryGetInt32(out int idNum))
                        {
                            userIds.Add(idNum);
                        }
                        else if (element.ValueKind == JsonValueKind.String && int.TryParse(element.GetString(), out int idStr))
                        {
                            userIds.Add(idStr);
                        }
                    }

                    if (root.ValueKind == JsonValueKind.Array)
                    {
                        foreach (var element in root.EnumerateArray()) ExtractId(element);
                    }
                    else if (root.ValueKind == JsonValueKind.Object && root.TryGetProperty("ids", out var idsElement) && idsElement.ValueKind == JsonValueKind.Array)
                    {
                        foreach (var element in idsElement.EnumerateArray()) ExtractId(element);
                    }

                    if (userIds.Count > 0)
                    {
                        var emails = await GetActiveEmailsAsync(MySqlConnString, userIds);
                        if (emails.Count > 0)
                        {
                            foreach (var email in emails)
                            {
                                await SendSingleEmailAsync(email, MailhogHost, MailhogPort);
                            }
                        }
                    }

                    // Якщо дійшли сюди без помилок у БД та Mail — бізнес-логіка успішна!
                    status = "success";

                    // Підтвердження в RabbitMQ
                    lock (channel)
                    {
                        if (channel.IsOpen)
                        {
                            channel.BasicAck(deliveryTag: deliveryTag, multiple: false);
                        }
                    }
                }
                catch (Exception ex)
                {
                    // Логуємо точну помилку у консоль для відлагодження
                    Console.WriteLine($"[C# DEBUG ERROR] Msg {messageId} failed at step (status was {status}): {ex.GetType().Name} - {ex.Message}");

                    if (status != "success")
                    {
                        status = "failed";
                    }

                    lock (channel)
                    {
                        if (channel.IsOpen)
                        {
                            channel.BasicNack(deliveryTag: deliveryTag, multiple: false, requeue: false);
                        }
                    }
                }

                stopwatch.Stop();
                double durationMs = stopwatch.Elapsed.TotalMilliseconds;

                var metric = new BsonDocument
                {
                    { "worker", "dotnet-worker" },
                    { "message_id", messageId },
                    { "status", status },
                    { "duration_ms", durationMs },
                    { "timestamp", DateTime.UtcNow }
                };

                try
                {
                    await mongoCollection.InsertOneAsync(metric);
                }
                catch (Exception mongoEx)
                {
                    Console.WriteLine($"MongoDB Log Error: {mongoEx.Message}");
                }
            });

            return Task.CompletedTask;
        };

        channel.BasicConsume(queue: "email_campaign_queue", autoAck: false, consumer: consumer);

        Console.WriteLine(" [*] C# .NET worker ready and listening for messages. To exit press CTRL+C");
        await Task.Delay(-1);
    }

    private static async Task<List<string>> GetActiveEmailsAsync(string connString, List<int> userIds)
    {
        var emails = new List<string>();
        using var conn = new MySqlConnection(connString);
        await conn.OpenAsync();

        using var cmd = conn.CreateCommand();
        var formatStrings = new List<string>();
        for (int i = 0; i < userIds.Count; i++)
        {
            formatStrings.Add($"@id{i}");
            cmd.Parameters.AddWithValue($"@id{i}", userIds[i]);
        }

        cmd.CommandText = $"SELECT email FROM users WHERE id IN ({string.Join(",", formatStrings)}) AND is_active = 1";

        using var reader = await cmd.ExecuteReaderAsync();
        while (await reader.ReadAsync())
        {
            emails.Add(reader.GetString(0));
        }

        return emails;
    }

    private static async Task SendSingleEmailAsync(string emailAddr, string host, int port)
    {
        try
        {
            using var client = new TcpClient();
            await client.ConnectAsync(host, port);
            using var stream = client.GetStream();
            var buffer = new byte[512];

            await stream.ReadAsync(buffer, 0, buffer.Length);

            async Task SendCmd(string cmd)
            {
                var bytes = Encoding.UTF8.GetBytes(cmd + "\r\n");
                await stream.WriteAsync(bytes, 0, bytes.Length);
                await stream.ReadAsync(buffer, 0, buffer.Length);
            }

            await SendCmd("EHLO benchmark");
            await SendCmd("MAIL FROM:<campaign@benchmark.local>");
            await SendCmd($"RCPT TO:<{emailAddr}>");
            await SendCmd("DATA");

            var rawEmail = $"From: campaign@benchmark.local\r\nTo: {emailAddr}\r\nSubject: Email Campaign Benchmark (C#)\r\n\r\nThis is a test campaign email sent by C# Worker.\r\n.\r\n";
            await SendCmd(rawEmail);
            await SendCmd("QUIT");
        }
        catch
        {
            // Ігноруємо сокетні таймаути SMTP під час навантаження
        }
    }
}
