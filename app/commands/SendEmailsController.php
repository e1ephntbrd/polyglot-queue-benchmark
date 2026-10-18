<?php

namespace app\commands;

use app\src\Application\UserIdPublisher;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Console controller for triggering email campaigns.
 *
 * This command publishes user IDs to the message queue
 * so that workers can process and send campaign emails:
 * - User IDs source classes provide IDs according to specific
 * criteria @see \app\src\Infrastructure\UserId\SourceInterface
 * - Storage receives, accumulates and release target IDs
 * @see \app\src\Infrastructure\UserId\StorageInterface
 */
class SendEmailsController extends Controller
{
    /** @var string Default action name */
    public $defaultAction = 'run';

    /**
     * Run the email publishing process.
     *
     * Retrieves the UserIdPublisher service from the DI container
     * and executes it to enqueue user IDs for email sending.
     *
     * @return int Exit code (0 if successful)
     *
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function actionRun(): int
    {
        /** @var UserIdPublisher $publisher */
        $publisher = \Yii::$container->get(UserIdPublisher::class);
        $publisher->execute();

        return ExitCode::OK;
    }
}
