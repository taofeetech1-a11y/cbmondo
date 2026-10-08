<?php

test('public staff registration screen is unavailable', function () {
    $this->get('/register')->assertNotFound();
});

test('public staff registration cannot create accounts or grant roles', function () {
    $this->post('/register', ['name' => 'Test', 'email' => 'test@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'role' => 'super_admin'])->assertNotFound();
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});
