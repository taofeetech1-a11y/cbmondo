<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** @param string|null $guard */
    public function actingAs(Authenticatable $user, $guard = null): static
    {
        $user->refresh();
        $this->withSession([
            'account_version_'.($guard ?? 'web') => $user->session_version,
            'password_hash_'.($guard ?? 'web') => $user->getAuthPassword(),
        ]);

        return parent::actingAs($user, $guard);
    }
}
