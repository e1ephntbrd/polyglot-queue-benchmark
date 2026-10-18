<?php
return [
    'class' => \app\components\RabbitMqComponent::class,
    'host' => getenv('RABBIT_HOST') ?: 'rabbitmq',
    'port' => (int)(getenv('RABBIT_PORT') ?: 5672),
    'user' => getenv('RABBIT_USER') ?: 'guest',
    'pass' => getenv('RABBIT_PASS') ?: 'guest',
];
