<?php

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('serves the welcome page when central domains are not configured', function () {
    config(['tenancy.central_domains' => null]);

    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('href="'.url('/register').'"', false);
});
