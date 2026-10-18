package main

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"log"
	"net/smtp"
	"os"
	"os/signal"
	"strings"
	"sync"
	"syscall"
	"time"

	_ "github.com/go-sql-driver/mysql"
	amqp "github.com/rabbitmq/amqp091-go"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
)

// TelemetryMetric represents the benchmark metrics schema stored in MongoDB.
type TelemetryMetric struct {
	Worker     string             `bson:"worker"`
	MessageID  string             `bson:"message_id"`
	Status     string             `bson:"status"`
	DurationMs float64            `bson:"duration_ms"`
	Timestamp  primitive.DateTime `bson:"timestamp"`
}

func main() {
	// 1. Establish connection to MySQL
	dsn := "root:root@tcp(mysql:3306)/app"
	db, err := sql.Open("mysql", dsn)
	if err != nil {
		log.Fatalf("Failed to connect to MySQL: %v", err)
	}
	defer db.Close()
	db.SetMaxOpenConns(50)
	db.SetMaxIdleConns(25)

	// 2. Establish connection to MongoDB
	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	mongoClient, err := mongo.Connect(ctx, options.Client().ApplyURI("mongodb://mongodb:27017"))
	if err != nil {
		log.Fatalf("Failed to connect to MongoDB: %v", err)
	}
	defer mongoClient.Disconnect(context.Background())
	benchCollection := mongoClient.Database("app").Collection("benchmark_metrics")

	// 3. Establish connection to RabbitMQ
	rabbitURL := "amqp://guest:guest@rabbitmq:5672/"
	conn, err := amqp.Dial(rabbitURL)
	if err != nil {
		log.Fatalf("Failed to connect to RabbitMQ: %v", err)
	}
	defer conn.Close()

	ch, err := conn.Channel()
	if err != nil {
		log.Fatalf("Failed to open RabbitMQ channel: %v", err)
	}
	defer ch.Close()

	// Set QoS prefetch limit to process messages in batches
	_ = ch.Qos(100, 0, false)

	q, err := ch.QueueDeclare(
		"email_campaign_queue", // Queue name
		true,          // Durable
		false,         // Auto-delete
		false,         // Exclusive
		false,         // No-wait
		nil,
	)
	if err != nil {
		log.Fatalf("Failed to declare queue: %v", err)
	}

	msgs, err := ch.Consume(
		q.Name,
		"go-worker",
		false, // Manual Ack/Nack enabled
		false,
		false,
		false,
		nil,
	)
	if err != nil {
		log.Fatalf("Failed to register consumer: %v", err)
	}

	log.Println(" [*] Go worker ready and listening for messages. Press CTRL+C to exit.")

	// Worker pool for concurrent message processing
	numWorkers := 10
	var wg sync.WaitGroup

	for i := 0; i < numWorkers; i++ {
		wg.Add(1)
		go func() {
			defer wg.Done()
			for msg := range msgs {
				processMessage(db, benchCollection, msg)
			}
		}()
	}

	sigChan := make(chan os.Signal, 1)
	signal.Notify(sigChan, syscall.SIGINT, syscall.SIGTERM)
	<-sigChan

	log.Println(" [x] Shutting down Go worker...")
	_ = ch.Close()
	wg.Wait()
}

// processMessage handles an incoming AMQP delivery, tracks execution duration,
// and records telemetry metrics into MongoDB.
func processMessage(db *sql.DB, mongoColl *mongo.Collection, msg amqp.Delivery) {
	start := time.Now()
	status := "success"

	messageID := msg.MessageId
	if messageID == "" {
		messageID = fmt.Sprintf("msg_%d", time.Now().UnixNano())
	}

	err := handleCampaignMail(db, msg.Body)
	if err != nil {
		status = "failed"
		log.Printf("Worker error for msg %s: %v", messageID, err)
		_ = msg.Nack(false, true) // Requeue on failure
	} else {
		_ = msg.Ack(false)
	}

	// Calculate execution duration in milliseconds
	durationMs := float64(time.Since(start).Microseconds()) / 1000.0

	metric := TelemetryMetric{
		Worker:     "go-worker",
		MessageID:  messageID,
		Status:     status,
		DurationMs: durationMs,
		Timestamp:  primitive.NewDateTimeFromTime(time.Now().UTC()),
	}

	_, mongoErr := mongoColl.InsertOne(context.Background(), metric)
	if mongoErr != nil {
		log.Printf("Failed to save telemetry to MongoDB: %v", mongoErr)
	}
}

// handleCampaignMail decodes payload, fetches active user emails, and dispatches them.
func handleCampaignMail(db *sql.DB, body []byte) error {
	var userIDs []int

	if err := json.Unmarshal(body, &userIDs); err != nil {
		var rawData interface{}
		if errWrap := json.Unmarshal(body, &rawData); errWrap != nil {
			snippet := string(body)
			if len(snippet) > 100 {
				snippet = snippet[:100] + "..."
			}
			return fmt.Errorf("unsupported body format (Snippet: %s): %w", snippet, errWrap)
		}

		switch v := rawData.(type) {
		case []interface{}:
			for _, item := range v {
				switch idVal := item.(type) {
				case float64:
					userIDs = append(userIDs, int(idVal))
				case string:
					var id int
					_, _ = fmt.Sscanf(idVal, "%d", &id)
					if id > 0 {
						userIDs = append(userIDs, id)
					}
				}
			}
		case map[string]interface{}:
			return fmt.Errorf("detected JSON object wrapper instead of flat array: %v", v)
		default:
			return fmt.Errorf("unexpected json type: %T", rawData)
		}
	}

	if len(userIDs) == 0 {
		return nil
	}

	// 2. Отримання активних email із MySQL
	emails, err := getActiveEmailsByIDs(db, userIDs)
	if err != nil {
		return fmt.Errorf("db fetch error: %w", err)
	}

	if len(emails) == 0 {
		return nil
	}

	// 3. Відправка через SMTP (MailHog)
	return sendEmailsViaSMTP(emails)
}

// getActiveEmailsByIDs queries MySQL for active user emails based on provided IDs.
func getActiveEmailsByIDs(db *sql.DB, ids []int) ([]string, error) {
	if len(ids) == 0 {
		return nil, nil
	}

	placeholders := make([]string, len(ids))
	args := make([]interface{}, len(ids))
	for i, id := range ids {
		placeholders[i] = "?"
		args[i] = id
	}

	query := fmt.Sprintf(
		"SELECT email FROM users WHERE id IN (%s) AND is_active = 1",
		strings.Join(placeholders, ","),
	)

	rows, err := db.Query(query, args...)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var emails []string
	for rows.Next() {
		var email string
		if err := rows.Scan(&email); err == nil {
			emails = append(emails, email)
		}
	}
	return emails, rows.Err()
}

// sendEmailsViaSMTP sends campaign emails sequentially to the configured SMTP server.
func sendEmailsViaSMTP(emails []string) error {
	smtpAddr := "mailhog:1025"
	from := "campaign@benchmark.local"

	for _, to := range emails {
		msg := []byte(fmt.Sprintf("To: %s\r\n"+
			"From: %s\r\n"+
			"Subject: Email Campaign Benchmark\r\n"+
			"\r\n"+
			"This is a test campaign email sent by Go Worker.\r\n", to, from))

		err := smtp.SendMail(smtpAddr, nil, from, []string{to}, msg)
		if err != nil {
			return err
		}
	}
	return nil
}
