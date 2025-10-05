<?php

declare(strict_types=1);

use function Pest\Laravel\get;

test('the application returns a login page', function () {
    get('/', [
        'HOST' => config('app.domains.admin')
    ])->assertRedirect('/login');
});

it('redirects homepage to the admin panel.', function () {
    adminPanelLogin();

    // Act && Assert
    get('/', [
        'HOST' => config('app.domains.admin')
    ])
        ->assertOk();
});
