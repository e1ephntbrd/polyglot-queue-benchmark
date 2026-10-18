<?php
return [
    'class' => yii\symfonymailer\Mailer::class,
    'useFileTransport' => false,
    'viewPath' => '@app/mail',
    'htmlLayout' => '@app/mail/layouts/html.php',
    'textLayout' => '@app/mail/layouts/text.php',
    'transport' => [
        // DSN для SMTP (Mailhog): smtp://HOST:PORT
        'dsn' => 'smtp://' . (getenv('MAIL_HOST') ?: 'mailhog') . ':' . (int)(getenv('MAIL_PORT') ?: 1025),
    ],
];
