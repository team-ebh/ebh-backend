<?php

declare(strict_types=1);

arch('globals')
    ->expect([
        'dd',
        'dump',
        'ray',
        'die',
        'var_dump',
        'sleep',
        'usleep',
        'exit',
    ])
    ->not->toBeUsed();
