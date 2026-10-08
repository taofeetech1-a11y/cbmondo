<?php

use App\Models\User;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Database\Factories\MembershipFactory;

beforeEach(function () {
    $this->actingAs(User::factory()->superAdmin()->create());
});

test('member cards are downloadable PNGs with fixed dimensions', function (?string $code) {
    $member = MembershipFactory::new()->create(['name' => 'A VERY LONG MEMBERSHIP NAME', 'cbm_delimitation_code' => $code]);

    $response = $this->get(route('membership.card', ['membership' => $member, 'download' => 1]));

    $response->assertOk()->assertHeader('Content-Type', 'image/png')
        ->assertHeader('Content-Disposition', 'attachment; filename="CBM-ID-'.$member->id.'.png"');
    $dimensions = getimagesizefromstring($response->getContent());
    expect([$dimensions[0], $dimensions[1]])->toBe([1200, 704]);
})->with(['with electoral ID' => ['28/18/01/010/001'], 'without electoral ID' => [null]]);

test('card preview and mobile download contain identical pixels', function () {
    $member = MembershipFactory::new()->create();
    $preview = $this->get(route('membership.card', $member));

    $download = $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X)')->get(route('membership.card', ['membership' => $member, 'download' => 1]));

    expect($download->getContent())->toBe($preview->getContent());
});

test('card reflects the selected member and current saved details', function () {
    $member = MembershipFactory::new()->create(['name' => 'First member']);
    $original = $this->get(route('membership.card', $member))->getContent();
    $member->update(['name' => 'Updated member']);

    $updated = $this->get(route('membership.card', $member));

    $updated->assertOk();
    expect($updated->getContent())->not->toBe($original);
});

test('missing member cards return not found', function () {
    $this->get(route('membership.card', 999999))->assertNotFound();
});

test('downloaded card contains the member CBM ID QR on its right side', function () {
    $member = MembershipFactory::new()->create(['name' => 'A VERY LONG MEMBERSHIP NAME', 'cbm_delimitation_code' => '28/18/01/010/001']);
    $member->update(['cbm_id' => 'CBM-ON-7r2nzm']);

    $response = $this->get(route('membership.card', ['membership' => $member, 'download' => 1]));

    $response->assertOk();
    $image = imagecreatefromstring($response->getContent());
    $expected = Encoder::encode('CBM-ON-7r2nzm', ErrorCorrectionLevel::M(), 'UTF-8')->getMatrix();
    $moduleSize = 232 / ($expected->getWidth() + 8);
    $actualModules = [];
    $expectedModules = [];
    for ($row = -4; $row < $expected->getHeight() + 4; $row++) {
        for ($column = -4; $column < $expected->getWidth() + 4; $column++) {
            $x = (int) (906 + ($column + 4.5) * $moduleSize);
            $y = (int) (222 + ($row + 4.5) * $moduleSize);
            $actualModules[] = imagecolorat($image, $x, $y) & 0xFFFFFF;
            $inside = $row >= 0 && $column >= 0 && $row < $expected->getHeight() && $column < $expected->getWidth();
            $expectedModules[] = $inside && $expected->get($column, $row) === 1 ? 0x000000 : 0xFFFFFF;
        }
    }
    expect($actualModules)->toBe($expectedModules);
    imagedestroy($image);
});
