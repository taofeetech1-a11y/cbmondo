<?php

use App\Models\CoordinatorAgent;
use App\Models\Voter;

it('requires a coordinator session to edit or update voters', function () {
    $voter = Voter::factory()->create();
    $this->get(route('voters.edit', $voter))->assertRedirect(route('login'));
    $this->patch(route('voters.update', $voter), ['full_name' => 'Unauthorized change'])->assertRedirect(route('login'));
    expect($voter->fresh()->full_name)->toBe($voter->full_name);
});

it('shows an edit link and phone number on each voter card', function () {
    $voter = Voter::factory()->create(['phone' => '08012345678']);
    $this->actingAs(CoordinatorAgent::factory()->create(), 'coordinator')
        ->get(route('voters.index'))->assertOk()
        ->assertSee(route('voters.edit', $voter))->assertSee('08012345678');
    $this->get(route('voters.edit', $voter))->assertOk()->assertSee($voter->full_name)->assertSee('08012345678');
});

it('updates only the phone number even when read-only attributes are submitted', function () {
    $voter = Voter::factory()->create(['photo_path' => 'voters/original.jpg']);
    $original = $voter->getAttributes();
    $data = [
        'full_name' => 'Updated Voter', 'surname' => 'Voter', 'other_names' => 'Updated',
        'vin' => $voter->vin, 'gender' => 'Male', 'age' => 42, 'occupation' => 'Teacher',
        'phone' => '+2348012345678', 'photo_path' => 'changed.jpg', 'polling_unit_id' => 999999,
    ];

    $this->actingAs(CoordinatorAgent::factory()->create(), 'coordinator')
        ->patch(route('voters.update', $voter), $data)->assertSessionHasNoErrors()
        ->assertRedirect(route('voters.edit', $voter))->assertSessionHas('status', 'Voter details updated.');

    $this->assertDatabaseHas('voters', [
        ...$original, 'phone' => '+2348012345678', 'updated_at' => $voter->fresh()->getRawOriginal('updated_at'),
    ]);
});

it('rejects clearing the required phone number', function () {
    $voter = Voter::factory()->create(['phone' => '08012345678']);
    $data = $voter->only(['full_name', 'surname', 'vin', 'gender', 'age']);
    $this->actingAs(CoordinatorAgent::factory()->create(), 'coordinator')
        ->patch(route('voters.update', $voter), [...$data, 'phone' => ''])->assertSessionHasErrors('phone');
    expect($voter->fresh()->phone)->toBe('08012345678');
});

it('rejects invalid voter details without saving changes', function (string $field, mixed $value) {
    $voter = Voter::factory()->create(['phone' => '08012345678']);
    $data = $voter->only(['full_name', 'surname', 'vin', 'gender', 'age', 'phone']);
    $this->actingAs(CoordinatorAgent::factory()->create(), 'coordinator')
        ->patch(route('voters.update', $voter), [...$data, $field => $value])
        ->assertSessionHasErrors($field);
    expect($voter->fresh()->only(array_keys($data)))->toBe($data);
})->with([
    'missing phone' => ['phone', null],
    'invalid phone' => ['phone', 'not a phone'],
    'oversized phone' => ['phone', str_repeat('1', 31)],
]);

it('ignores a submitted VIN already assigned to another voter', function () {
    $voter = Voter::factory()->create();
    $other = Voter::factory()->create();
    $data = $voter->only(['full_name', 'surname', 'gender', 'age']);
    $this->actingAs(CoordinatorAgent::factory()->create(), 'coordinator')
        ->patch(route('voters.update', $voter), [...$data, 'vin' => $other->vin, 'phone' => '08012345678'])->assertSessionHasNoErrors();
    expect($voter->fresh()->vin)->toBe($voter->vin);
});

it('returns 404 for a missing voter and escapes voter names in the edit form', function () {
    $voter = Voter::factory()->create(['full_name' => '<script>alert(1)</script>']);
    $this->actingAs(CoordinatorAgent::factory()->create(), 'coordinator')
        ->get(route('voters.edit', 999999))->assertNotFound();
    $this->get(route('voters.edit', $voter))->assertOk()->assertSee($voter->full_name)->assertDontSee($voter->full_name, false);
});
