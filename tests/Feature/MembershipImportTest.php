<?php

use App\Models\Membership;
use App\Models\PollingUnit;
use App\Models\User;
use App\Support\MembershipImport;
use Database\Factories\MembershipFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->actingAs(User::factory()->superAdmin()->create());
});

function importRow(PollingUnit $unit, array $changes = []): array
{
    return array_replace(['name' => 'Import member', 'phone' => '+2348031234567', 'email' => ' Member@Example.COM ', 'gender' => 'female', 'age_range' => '25-34', 'has_voters_card' => 'yes', 'lga' => (string) $unit->ward->lga_id, 'ward' => (string) $unit->ward_id, 'pu' => (string) $unit->id, 'support_us' => 'yes', 'want_to_be_contacted' => 'no', 'same_address' => 'yes'], $changes);
}

function importFile(array $rows, string $format = 'csv'): UploadedFile
{
    if ($format === 'csv') {
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, MembershipImport::COLUMNS, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($stream, array_values($row), ',', '"', '');
        }
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);
    } else {
        $book = new Spreadsheet;
        foreach ([MembershipImport::COLUMNS, ...array_map('array_values', $rows)] as $index => $row) {
            foreach ($row as $column => $value) {
                $book->getActiveSheet()->setCellValueExplicit([$column + 1, $index + 1], (string) $value, DataType::TYPE_STRING);
            }
        }
        ob_start();
        (new Xlsx($book))->save('php://output');
        $contents = ob_get_clean();
        $book->disconnectWorksheets();
    }

    return UploadedFile::fake()->createWithContent('members.'.$format, $contents);
}

test('imports preview then save normalized members and sequential codes once', function (string $format) {
    $unit = PollingUnit::factory()->create(['delimitation_code' => '28/18/01/010', 'next_member_number' => 7]);
    $rows = [
        importRow($unit),
        importRow($unit, ['name' => 'Second member', 'phone' => '08031234568', 'email' => '']),
        importRow($unit, ['name' => 'No card member', 'phone' => '08031234569', 'email' => '', 'has_voters_card' => 'no', 'ward' => '', 'pu' => '', 'same_address' => '']),
    ];

    $this->post(route('membership.import.preview'), ['file' => importFile($rows, $format)])->assertRedirectToRoute('membership.import')->assertSessionHasNoErrors();
    $this->assertDatabaseCount('memberships', 0);
    $token = session('membership_import');
    $this->get(route('membership.import'))->assertSee('Confirm import of 3 members')->assertSee('08031234567')->assertSee('member@example.com');
    $this->post(route('membership.import.store'), ['token' => $token, 'records' => [['name' => 'Tampered']]])->assertSessionHas('status', '3 members imported successfully. All records were saved and new CBM IDs assigned.');

    $this->assertDatabaseCount('memberships', 3);
    $this->assertDatabaseHas('memberships', ['name' => 'Import member', 'phone' => '08031234567', 'email' => 'member@example.com', 'cbm_delimitation_code' => '28/18/01/010/007']);
    $this->assertDatabaseHas('memberships', ['name' => 'Second member', 'cbm_delimitation_code' => '28/18/01/010/008']);
    $this->assertDatabaseHas('memberships', ['name' => 'No card member', 'ward' => null, 'pu' => null, 'same_address' => null, 'cbm_delimitation_code' => null]);
    expect($unit->fresh()->next_member_number)->toBe(9);
    expect(Membership::pluck('cbm_id')->unique())->toHaveCount(3);
    $this->post(route('membership.import.store'), ['token' => $token])->assertSessionHasErrors('import');
    $this->assertDatabaseCount('memberships', 3);
})->with(['csv', 'xlsx']);

test('duplicate contacts within the file flag both rows and block the entire batch', function () {
    $unit = PollingUnit::factory()->create();
    $this->post(route('membership.import.preview'), ['file' => importFile([importRow($unit), importRow($unit, ['phone' => '0803 123 4567', 'email' => 'member@example.com'])])]);
    $this->get(route('membership.import'))->assertSee('Duplicate phone in this file (row 2).')->assertSee('Duplicate email in this file (row 3).')->assertDontSee('Confirm import of');
    $this->post(route('membership.import.store'), ['token' => session('membership_import')])->assertSessionHasErrors('import');
    $this->assertDatabaseCount('memberships', 0);
    expect($unit->fresh()->next_member_number)->toBe(1);
});

test('existing legacy contacts are flagged in preview', function () {
    $unit = PollingUnit::factory()->create();
    MembershipFactory::new()->create(['phone' => '+234 (803)1234567', 'email' => 'MEMBER@example.com']);
    $this->post(route('membership.import.preview'), ['file' => importFile([importRow($unit)])]);
    $this->get(route('membership.import'))->assertSee('This phone number is already registered')->assertSee('This email address is already registered');
    $this->assertDatabaseCount('memberships', 1);
});

test('import validates values and electoral hierarchy with row specific errors', function (array $changes, string $message) {
    $unit = PollingUnit::factory()->create();
    $this->post(route('membership.import.preview'), ['file' => importFile([importRow($unit, $changes)])]);
    $this->get(route('membership.import'))->assertSee($message)->assertDontSee('Confirm import of');
    $this->post(route('membership.import.store'), ['token' => session('membership_import')])->assertSessionHasErrors('import');
    $this->assertDatabaseCount('memberships', 0);
})->with([
    [['name' => str_repeat('a', 31)], 'The name field must not be greater than 30 characters.'],
    [['phone' => '1234'], 'The phone must be a valid Nigerian number'],
    [['want_to_be_contacted' => ''], 'The want to be contacted field is required.'],
    [['gender' => 'unknown'], 'The selected gender is invalid.'],
    [['ward' => '99999'], 'The selected ward is invalid.'],
    [['pu' => '99999'], 'The selected pu is invalid.'],
    [['has_voters_card' => 'no'], 'Leave ward, pu and same_address blank'],
    [['name' => '=1+1'], 'Formulas are not supported.'],
]);

test('polling units and wards from other locations are rejected', function () {
    $unit = PollingUnit::factory()->create();
    $other = PollingUnit::factory()->create();
    $this->post(route('membership.import.preview'), ['file' => importFile([importRow($unit, ['ward' => (string) $other->ward_id])])]);
    $this->get(route('membership.import'))->assertSee('The selected ward is invalid.')->assertSee('The selected pu is invalid.');
    $this->assertDatabaseCount('memberships', 0);
});

test('confirmation rechecks a contact registered after preview without partial writes', function () {
    $unit = PollingUnit::factory()->create();
    $this->post(route('membership.import.preview'), ['file' => importFile([importRow($unit), importRow($unit, ['phone' => '08031234568', 'email' => ''])])]);
    MembershipFactory::new()->create(['phone' => '08031234568']);
    $this->post(route('membership.import.store'), ['token' => session('membership_import')])->assertSessionHasErrors('import');
    $this->assertDatabaseCount('memberships', 1);
    expect($unit->fresh()->next_member_number)->toBe(1);
});

test('expired previews cannot be confirmed', function () {
    $unit = PollingUnit::factory()->create();
    $this->post(route('membership.import.preview'), ['file' => importFile([importRow($unit)])]);
    $token = session('membership_import');
    $this->travel(21)->minutes();
    $this->post(route('membership.import.store'), ['token' => $token])->assertSessionHasErrors('import');
    $this->assertDatabaseCount('memberships', 0);
});

test('a preview token from another session cannot be confirmed', function () {
    $unit = PollingUnit::factory()->create();
    $this->post(route('membership.import.preview'), ['file' => importFile([importRow($unit)])]);
    $token = session('membership_import');
    session()->forget('membership_import');
    $this->post(route('membership.import.store'), ['token' => $token])->assertSessionHasErrors('import');
    $this->assertDatabaseCount('memberships', 0);
});

test('malformed and unsupported files are rejected before preview', function (string $name, string $contents) {
    $this->post(route('membership.import.preview'), ['file' => UploadedFile::fake()->createWithContent($name, $contents)])->assertSessionHasErrors('file');
    $this->assertDatabaseCount('memberships', 0);
})->with([
    ['members.xlsx', 'not a workbook'],
    ['members.xls', 'name,phone'],
    ['members.csv', 'name,phone'],
    ['members.csv', implode(',', MembershipImport::COLUMNS)],
    ['members.csv', implode(',', MembershipImport::COLUMNS)."\nonly,two"],
]);

test('files exceeding size and row limits are rejected', function () {
    $this->post(route('membership.import.preview'), ['file' => UploadedFile::fake()->create('members.csv', 2049, 'text/csv')])->assertSessionHasErrors('file');
    $unit = PollingUnit::factory()->create();
    $this->post(route('membership.import.preview'), ['file' => importFile(array_fill(0, 501, importRow($unit)))])->assertSessionHasErrors('file');
    $this->assertDatabaseCount('memberships', 0);
});

test('templates and location references are downloadable and the page is reachable', function () {
    $unit = PollingUnit::factory()->create();
    $this->get(route('membership.import'))->assertSee('Preview import')->assertSee('Location reference');
    $this->get(route('membership.import.template', 'csv'))->assertDownload('cbm-import-template.csv')->assertStreamedContent(implode(',', MembershipImport::COLUMNS)."\n");
    $excel = $this->get(route('membership.import.template', 'xlsx'))->assertDownload('cbm-import-template.xlsx')->streamedContent();
    expect(substr($excel, 0, 2))->toBe('PK');
    $locations = $this->get(route('membership.import.locations'))->assertDownload('cbm-location-reference.csv')->streamedContent();
    expect($locations)->toContain($unit->name)->toContain($unit->ward->name)->toContain($unit->ward->lga->name);
    $this->get(route('membership.import.template', 'php'))->assertNotFound();
});

test('a genuine Excel formula is previewed as an error without evaluation', function () {
    $unit = PollingUnit::factory()->create();
    $book = new Spreadsheet;
    $sheet = $book->getActiveSheet();
    $sheet->fromArray([MembershipImport::COLUMNS, array_values(importRow($unit))], null, 'A1');
    $sheet->setCellValue('A2', '=1+1');
    ob_start();
    $writer = new Xlsx($book);
    $writer->setPreCalculateFormulas(false);
    $writer->save('php://output');
    $contents = ob_get_clean();
    $book->disconnectWorksheets();

    $this->post(route('membership.import.preview'), ['file' => UploadedFile::fake()->createWithContent('formula.xlsx', $contents)]);
    $this->get(route('membership.import'))->assertSee('Formulas are not supported.')->assertSee('=1+1');
    $this->assertDatabaseCount('memberships', 0);
});

test('spreadsheet markup is escaped in the preview', function () {
    $unit = PollingUnit::factory()->create();
    $this->post(route('membership.import.preview'), ['file' => importFile([importRow($unit, ['name' => '<script>alert(1)</script>'])])]);
    $this->get(route('membership.import'))->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
});

test('Excel templates preserve text formatting for phone numbers', function () {
    $contents = $this->get(route('membership.import.template', 'xlsx'))->streamedContent();
    $file = UploadedFile::fake()->createWithContent('template.xlsx', $contents);
    $book = (new PhpOffice\PhpSpreadsheet\Reader\Xlsx)->load($file->getPathname());
    try {
        expect($book->getActiveSheet()->getStyle('B2')->getNumberFormat()->getFormatCode())->toBe('@');
        expect($book->getActiveSheet()->getCell('L1')->getValue())->toBe('same_address');
    } finally {
        $book->disconnectWorksheets();
    }
});

test('a new upload invalidates the previous preview token', function () {
    $unit = PollingUnit::factory()->create();
    $this->post(route('membership.import.preview'), ['file' => importFile([importRow($unit)])]);
    $previous = session('membership_import');
    $this->post(route('membership.import.preview'), ['file' => importFile([importRow($unit, ['phone' => '08031234568'])])]);
    $this->post(route('membership.import.store'), ['token' => $previous])->assertSessionHasErrors('import');
    $this->assertDatabaseCount('memberships', 0);
});

test('Excel files with extra worksheets or oversized expanded contents are rejected', function (string $kind) {
    $book = new Spreadsheet;
    $book->getActiveSheet()->fromArray(MembershipImport::COLUMNS, null, 'A1');
    if ($kind === 'sheets') {
        $book->createSheet()->setCellValue('A1', 'Unexpected records');
    }
    ob_start();
    (new Xlsx($book))->save('php://output');
    $contents = ob_get_clean();
    $book->disconnectWorksheets();
    if ($kind === 'expanded') {
        $path = tempnam(sys_get_temp_dir(), 'import-test-');
        file_put_contents($path, $contents);
        $zip = new ZipArchive;
        $zip->open($path);
        $zip->addFromString('large.txt', str_repeat('x', 21 * 1024 * 1024));
        $zip->close();
        $contents = file_get_contents($path);
        unlink($path);
    }
    $file = UploadedFile::fake()->createWithContent('members.xlsx', $contents);

    $this->post(route('membership.import.preview'), ['file' => $file])->assertSessionHasErrors('file');
    $this->assertDatabaseCount('memberships', 0);
})->with(['sheets', 'expanded']);

test('an import already being saved cannot be submitted concurrently', function () {
    $unit = PollingUnit::factory()->create();
    $this->post(route('membership.import.preview'), ['file' => importFile([importRow($unit)])]);
    $token = session('membership_import');
    $lock = Cache::lock('membership-import-lock:'.$token, 300);
    $lock->get();
    try {
        $this->post(route('membership.import.store'), ['token' => $token])->assertSessionHasErrors(['import' => 'This import is already being saved. Please wait.']);
        $this->assertDatabaseCount('memberships', 0);
    } finally {
        $lock->release();
    }
});
