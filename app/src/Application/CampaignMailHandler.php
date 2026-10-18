<?php

namespace app\src\Application;

use app\src\Domain\EmailCampaign\CampaignServiceInterface;
use PhpAmqpLib\Message\AMQPMessage;
use app\models\Users;
use Yii;

/**
 * Message handler for campaign emails.
 *
 * Processes incoming AMQP messages containing user IDs,
 * retrieves active user email addresses, and delegates
 * sending to the campaign service.
 */
class CampaignMailHandler
{
    /**
     * @param CampaignServiceInterface $campaignService Service responsible for sending campaign emails
     */
    public function __construct(
        private readonly CampaignServiceInterface $campaignService
    ) {
    }

    /**
     * Invoke the handler for an incoming AMQP message.
     *
     * Decodes the message body, retrieves active user emails,
     * and sends them via the campaign service. Acknowledges
     * the message on success, or negatively acknowledges it
     * on failure.
     *
     * @param AMQPMessage $msg The incoming AMQP message
     * @return void
     */
    public function __invoke(AMQPMessage $msg): void
    {
        $start = microtime(true);
        $status = 'success';
        $messageId = $msg->has('message_id') ? $msg->get('message_id') : uniqid('msg_', true);

        try {
            // Decode message body into array of user IDs
            $data = json_decode($msg->getBody(), true, 512, JSON_THROW_ON_ERROR);
            // Retrieve active user emails by IDs
            $emails = Users::getActiveEmailsByIds($data);
            // Send campaign emails
            $this->campaignService->send($emails);
            // Acknowledge successful processing
            $msg->ack();
        } catch (\Throwable $e) {
            $status = 'failed';
            // Log error and requeue message
            \Yii::error("Worker error: " . $e->getMessage(), __METHOD__);
            $msg->nack(true, true);
        }finally {
            $durationMs = round((microtime(true) - $start) * 1000, 2);

            try {
                Yii::$app->mongodb->getCollection('benchmark_metrics')->insert([
                    'worker' => 'php-yii2-worker',
                    'message_id' => $messageId,
                    'status' => $status,
                    'duration_ms' => $durationMs,
                    'timestamp' => new \MongoDB\BSON\UTCDateTime(),
                ]);
            } catch (\Throwable $ex) {
                Yii::error("Failed to save telemetry: " . $ex->getMessage(), __METHOD__);
            }
        }
    }
}
