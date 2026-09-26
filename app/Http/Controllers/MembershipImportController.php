<?php

namespace App\Http\Controllers;

use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\Ward;
use App\Support\MembershipImport;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MembershipImportController extends Controller
{
    public function index(Request $request): View
    {
        $token = $request->session()->get('membership_import');
        $preview = $token ? Cache::get('membership-import:'.$token) : null;

        return view('dashboard.members.import', [
            'preview' => $preview,
            'token' => $token,
            'columns' => MembershipImport::COLUMNS,
        ]);
    }

    public function preview(Request $request, MembershipImport $import): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:2048', 'extensions:csv,xlsx', 'mimes:csv,txt,xlsx']]);
        $records = $import->read($request->file('file'));
        $rowErrors = $import->errors($records);
        $previous = $request->session()->get('membership_import');
        if ($previous) {
            Cache::forget('membership-import:'.$previous);
        }
        $token = Str::random(64);
        Cache::put('membership-import:'.$token, [
            'records' => $records,
            'errors' => $rowErrors,
            'filename' => $request->file('file')->getClientOriginalName(),
            'expires' => now()->addMinutes(20)->format('H:i'),
        ], now()->addMinutes(20));
        $request->session()->put('membership_import', $token);

        return to_route('membership.import');
    }

    public function store(Request $request, MembershipImport $import): RedirectResponse
    {
        $request->validate(['token' => ['required', 'string', 'size:64']]);
        $token = $request->input('token');
        if (! hash_equals((string) $request->session()->get('membership_import'), $token)) {
            throw ValidationException::withMessages(['import' => 'This preview is no longer available. Upload the file again.']);
        }
        $lock = Cache::lock('membership-import-lock:'.$token, 300);
        if (! $lock->get()) {
            throw ValidationException::withMessages(['import' => 'This import is already being saved. Please wait.']);
        }
        try {
            $preview = Cache::get('membership-import:'.$token);
            if (! $preview) {
                throw ValidationException::withMessages(['import' => 'The preview has expired or was already imported. Upload the file again.']);
            }
            try {
                $count = $import->save($preview['records']);
            } catch (UniqueConstraintViolationException $exception) {
                throw ValidationException::withMessages(['import' => 'A contact was registered while this import was being saved. Nothing was imported. Upload the file again to check duplicates.']);
            }
            Cache::forget('membership-import:'.$token);
            $request->session()->forget('membership_import');

            return to_route('membership.import')->with('status', $count.' members imported successfully. All records were saved and new CBM IDs assigned.');
        } finally {
            $lock->release();
        }
    }

    public function template(string $format): StreamedResponse
    {
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);

        return response()->streamDownload(function () use ($format) {
            if ($format === 'csv') {
                $stream = fopen('php://output', 'w');
                fputcsv($stream, MembershipImport::COLUMNS, ',', '"', '');
                fclose($stream);

                return;
            }
            $book = new Spreadsheet;
            try {
                $sheet = $book->getActiveSheet();
                $sheet->setTitle('Members');
                foreach (MembershipImport::COLUMNS as $index => $column) {
                    $sheet->setCellValueExplicit([$index + 1, 1], $column, DataType::TYPE_STRING);
                    $sheet->getColumnDimensionByColumn($index + 1)->setWidth(24);
                }
                $sheet->getStyle('A1:L1')->getFont()->setBold(true);
                $sheet->getStyle('A2:L501')->getNumberFormat()->setFormatCode('@');
                $sheet->freezePane('A2');
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, 'cbm-import-template.'.$format, ['Content-Type' => $format === 'csv' ? 'text/csv; charset=UTF-8' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function locations(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['type', 'lga', 'lga_name', 'ward', 'ward_name', 'pu', 'pu_name'], ',', '"', '');
            $lgas = Lga::orderBy('id')->get()->keyBy('id');
            $wards = Ward::orderBy('id')->get()->keyBy('id');
            $write = function (array $row) use ($stream): void {
                fputcsv($stream, array_map(fn ($value) => preg_match('/^[=+\-@\t\r]/', (string) $value) ? "'".$value : $value, $row), ',', '"', '');
            };
            foreach ($lgas as $lga) {
                $write(['lga', $lga->id, $lga->name, '', '', '', '']);
            }
            foreach ($wards as $ward) {
                $write(['ward', $ward->lga_id, $lgas[$ward->lga_id]->name ?? '', $ward->id, $ward->name, '', '']);
            }
            foreach (PollingUnit::orderBy('id')->lazy(500) as $unit) {
                $ward = $wards[$unit->ward_id] ?? null;
                $write(['pu', $ward?->lga_id, $lgas[$ward?->lga_id]->name ?? '', $unit->ward_id, $ward?->name, $unit->id, $unit->name]);
            }
            fclose($stream);
        }, 'cbm-location-reference.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
