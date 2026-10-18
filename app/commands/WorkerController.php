<?php
namespace app\commands;

use app\src\Application\CampaignMailHandler;
use app\src\Infrastructure\Queue\Worker;
use app\src\Shared\Config\AppSettings;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Console controller for running the email campaign worker.
 *
 * This command listens on the configured message queue and
 * delegates incoming messages to the @see \app\src\Application\CampaignMailHandler
 * NOTE: Command has own dedicated container to run in the background (@see <make main-worker-restart>)
 * NOTE: To increase consuming (@see <make scale-worker>)
 */
class WorkerController extends Controller
{
    /** @var string Default action name */
    public $defaultAction = 'listen';

    /**
     * Start listening to the campaign queue.
     *
     * Retrieves the queue name from AppSettings, creates
     * Worker and CampaignMailHandler instances via the Yii
     * dependency injection container, and begins listening
     * for incoming messages.
     *
     * @return int Exit code (0 if successful)
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function actionListen(): int
    {
        // Get queue name from configuration
        $queue = AppSettings::get('campaign.queue');

        /** @var Worker $worker */
        $worker = \Yii::createObject(Worker::class);
        /** @var CampaignMailHandler $handler */
        $handler = \Yii::createObject(CampaignMailHandler::class);
        // Log worker startup
        \Yii::info("Worker listening on queue={$queue}", __METHOD__);

        // Start listening for messages
        $worker->listen($queue, $handler);

        return ExitCode::OK;
    }
}
