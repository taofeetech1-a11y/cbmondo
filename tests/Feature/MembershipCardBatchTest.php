<?php

use App\View\MembershipCard;
use Database\Factories\MembershipFactory;

test('bulk ZIP contains every matching card across pagination', function () {
    $members = MembershipFactory::new()->count(12)->create(['lga' => '1', 'ward' => null, 'created_at' => '2026-09-25 12:00:00']);
    MembershipFactory::new()->create(['lga' => '2']);
    MembershipFactory::new()->create(['lga' => '1', 'created_at' => '2026-08-01 12:00:00']);

    $response = $this->post(route('membership.cards.download', ['lga' => 1, 'has_voters_card' => 'no', 'period' => 'custom', 'date_from' => '2026-09-25', 'date_to' => '2026-09-25', 'page' => 2, 'per_page' => 10]), ['scope' => 'filtered', 'format' => 'zip']);

    $response->assertOk()->assertDownload()->assertHeader('Content-Type', 'application/zip');
    $path = tempnam(sys_get_temp_dir(), 'card-test-');
    try {
        file_put_contents($path, $response->streamedContent());
        $zip = new ZipArchive;
        expect($zip->open($path))->toBeTrue();
        expect($zip->numFiles)->toBe(12);
        foreach ($members as $member) {
            $png = $zip->getFromName('CBM-ID-'.$member->id.'.png');
            expect($png)->toBe(app(MembershipCard::class)->render($member));
        }
        $zip->close();
    } finally {
        unlink($path);
    }
});

test('selected ZIP contains only selected members', function () {
    $members = MembershipFactory::new()->count(3)->create();
    $response = $this->post(route('membership.cards.download'), ['scope' => 'selected', 'format' => 'zip', 'ids' => [$members[1]->id]]);
    $path = tempnam(sys_get_temp_dir(), 'card-test-');
    try {
        file_put_contents($path, $response->streamedContent());
        $zip = new ZipArchive;
        $zip->open($path);
        expect($zip->numFiles)->toBe(1);
        expect($zip->getNameIndex(0))->toBe('CBM-ID-'.$members[1]->id.'.png');
        $zip->close();
    } finally {
        unlink($path);
    }
});

test('PDF sheets keep eight cards per A4 page', function () {
    MembershipFactory::new()->count(9)->create();
    $response = $this->post(route('membership.cards.download'), ['scope' => 'filtered', 'format' => 'pdf']);
    $response->assertOk()->assertDownload()->assertHeader('Content-Type', 'application/pdf');
    $pdf = $response->streamedContent();
    expect($pdf)->toStartWith('%PDF-')->toContain('/Count 2', '/MediaBox [0 0 595.28 841.89]');
});

test('bulk cards reject empty invalid and out of scope selections', function (array $payload, string $error) {
    $member = MembershipFactory::new()->create(['lga' => '2']);
    $this->postJson(route('membership.cards.download', ['lga' => 1]), $payload)->assertUnprocessable()->assertJsonValidationErrors($error);
})->with([
    [['scope' => 'selected', 'format' => 'zip'], 'ids'],
    [['scope' => 'selected', 'format' => 'zip', 'ids' => [99999]], 'ids'],
    [['scope' => 'selected', 'format' => 'zip', 'ids' => [1, 1]], 'ids.0'],
    [['scope' => 'selected', 'format' => 'zip', 'ids' => [1]], 'ids'],
    [['scope' => 'filtered', 'format' => 'zip'], 'scope'],
    [['scope' => 'filtered', 'format' => 'exe'], 'format'],
    [['scope' => 'bad', 'format' => 'zip'], 'scope'],
]);

test('oversized PDF requests fail before rendering', function () {
    MembershipFactory::new()->count(201)->create();
    $this->postJson(route('membership.cards.download'), ['scope' => 'filtered', 'format' => 'pdf'])->assertUnprocessable()->assertJsonValidationErrors('format');
});

test('oversized manual selections are rejected before generating cards', function () {
    $this->postJson(route('membership.cards.download'), ['scope' => 'selected', 'format' => 'zip', 'ids' => range(1, 501)])
        ->assertUnprocessable()->assertJsonValidationErrors('ids');
});
