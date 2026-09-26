<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterMembershipRequest;
use App\Models\Lga;
use App\Models\Membership;
use App\Models\PollingUnit;
use App\Models\Ward;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {

        $lgas = Lga::orderBy('name')->get();

        return view('new-homepage', compact('lgas'));
    }

    public function wards(Lga $lga)
    {

        $wards = $lga->wards()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json($wards);
    }

    public function pollingUnit(Ward $ward)
    {

        $pollingUnits = $ward->pollingUnits()
            ->orderBy('name')
            ->get(['id', 'name', 'delimitation_code']);

        return response()->json($pollingUnits);
    }

    public function cbmRegister(RegisterMembershipRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated) {

            $pollingUnit = PollingUnit::where(
                'id',
                $validated['polling_unit']
            )
                ->lockForUpdate()
                ->firstOrFail();

            $memberNumber = $pollingUnit->next_member_number;

            $cbmOndId = $pollingUnit->delimitation_code
                .'/'
                .str_pad($memberNumber, 3, '0', STR_PAD_LEFT);

            $member = Membership::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'gender' => $validated['gender'],
                'age_range' => $validated['ageRange'],
                'lga' => $validated['lga'],
                'ward' => $validated['ward'],
                'pu' => $pollingUnit->id,
                'delimitation_code' => $pollingUnit->delimitation_code,
                'cbm_delimitation_code' => $cbmOndId,
                'support_us' => $validated['support'],
                'want_to_be_contacted' => $validated['contact_consent'],
                'same_address' => $validated['same_address'],

            ]);

            $pollingUnit->increment('next_member_number');
            $fact = [
                'name' => $member->name,
                'cbm_id' => $member->cbm_id,
                'mem_id' => $member->cbm_delimitation_code,
            ];

            return back()->with('data', $fact);
        });
    }

    public function noCardRegister(RegisterMembershipRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated) {

            $member = Membership::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'gender' => $validated['gender'],
                'age_range' => $validated['ageRange'],
                'lga' => $validated['lga'],
                'ward' => null,
                'pu' => null,
                'delimitation_code' => null,
                'cbm_delimitation_code' => null,
                'support_us' => $validated['support'],
                'want_to_be_contacted' => $validated['contact_consent'],
                'same_address' => null,

            ]);

            $fact = [
                'name' => $member->name,
                'cbm_id' => $member->cbm_id,
            ];

            return back()->with('dataTwo', $fact);
        });
    }
}
