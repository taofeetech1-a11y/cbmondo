<?php

use App\Models\User;
use Database\Factories\MembershipFactory;

beforeEach(function () {
    $this->actingAs(User::factory()->superAdmin()->create());
});

test('exports include all matching members beyond pagination', function (string $type) {
    $members = MembershipFactory::new()->count(12)->create(['lga' => '1', 'ward' => '11', 'pu' => '111', 'name' => 'Selected member']);
    $outside = MembershipFactory::new()->create(['lga' => '2']);
    MembershipFactory::new()->create(['lga' => '1', 'ward' => '12', 'pu' => '112']);
    MembershipFactory::new()->create(['lga' => '1']);

    $response = $this->get(route('membership.export', ['type' => $type, 'lga' => 1, 'ward' => 11, 'pu' => 111, 'has_voters_card' => 'yes', 'q' => 'Selected', 'page' => 2]));

    $response->assertOk()->assertDownload();
    $content = $response->streamedContent();
    if ($type === 'json') {
        $rows = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        expect(array_column($rows, 'id'))->toEqualCanonicalizing($members->modelKeys());
    } elseif ($type === 'sql') {
        expect(substr_count($content, 'INSERT INTO `memberships`'))->toBe(12);
        foreach ($members as $member) {
            expect($content)->toContain(bin2hex($member->cbm_id));
        }
        expect($content)->not->toContain(bin2hex($outside->cbm_id));
    } elseif ($type === 'csv') {
        expect(count(explode("\n", trim($content))))->toBe(13);
        foreach ($members as $member) {
            expect($content)->toContain($member->cbm_id);
        }
        expect($content)->not->toContain($outside->cbm_id);
    } else {
        $path = tempnam(sys_get_temp_dir(), 'cbm-test-');
        try {
            file_put_contents($path, $content);
            $zip = new ZipArchive;
            expect($zip->open($path))->toBeTrue();
            $sheet = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml'));
            expect(count($sheet->sheetData->row))->toBe(13);
            $xml = $sheet->asXML();
            foreach ($members as $member) {
                expect($xml)->toContain($member->cbm_id);
            }
            expect($xml)->not->toContain($outside->cbm_id);
            $zip->close();
        } finally {
            unlink($path);
        }
    }
})->with(['xlsx', 'csv', 'json', 'sql']);

test('exports and chart use each selected filter', function (array $filters, int $count) {
    MembershipFactory::new()->count(4)->sequence(
        ['lga' => '1', 'ward' => '11', 'pu' => '111', 'name' => 'Selected'],
        ['lga' => '1', 'ward' => '12', 'pu' => '112', 'name' => 'Another'],
        ['lga' => '1', 'ward' => null, 'pu' => null, 'name' => 'Selected'],
        ['lga' => '2', 'ward' => null, 'pu' => null, 'name' => 'Another'],
    )->create();

    $response = $this->get(route('membership.export', ['type' => 'json', ...$filters]));
    expect(json_decode($response->streamedContent(), true))->toHaveCount($count);
    $this->get(route('membership.index', $filters))->assertViewHas('breakdown', fn ($rows) => $rows->sum('count') === $count);
})->with([
    'all' => [[], 4],
    'lga' => [['lga' => 1], 3],
    'ward' => [['ward' => 11], 1],
    'polling unit' => [['pu' => 111], 1],
    'with cards' => [['has_voters_card' => 'yes'], 2],
    'without cards' => [['has_voters_card' => 'no'], 2],
    'search' => [['q' => 'Selected'], 2],
    'empty' => [['q' => 'Missing'], 0],
]);

test('exports protect spreadsheet cells and preserve SQL text and nulls', function () {
    $member = MembershipFactory::new()->create(['name' => '=1+1', 'phone' => '0123456789', 'email' => null]);

    $csv = $this->get(route('membership.export', ['type' => 'csv']))->streamedContent();
    expect($csv)->toContain("'=1+1");
    $sql = $this->get(route('membership.export', ['type' => 'sql']))->streamedContent();
    expect($sql)->toContain("CONVERT(X'3d312b31' USING utf8mb4)", 'NULL');
    $path = tempnam(sys_get_temp_dir(), 'cbm-test-');
    try {
        file_put_contents($path, $this->get(route('membership.export', ['type' => 'xlsx']))->streamedContent());
        $zip = new ZipArchive;
        $zip->open($path);
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        expect($xml)->toContain('0123456789', '=1+1', 't="inlineStr"')->not->toContain('<f>');
        $zip->close();
    } finally {
        unlink($path);
    }
});

test('unsupported export formats are rejected', function () {
    $this->get(route('membership.export', ['type' => 'exe']))->assertNotFound();
});
