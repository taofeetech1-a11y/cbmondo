<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lga;
use App\Models\Members;
use App\Models\PollingUnit;
use App\Models\Support;
use App\Models\Volunteers;
use App\Models\Ward;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AppController extends Controller
{

    public function index()
    {

        $lgas = Lga::orderBy('name')->get();

        return view('registration-master', compact('lgas'));
    }

    public function supportPage()
    {

        $lgas = Lga::orderBy('name')->get();

        return view('support', compact('lgas'));
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


    public function ProcessSubmitionOld(Request $request)
    {

        // dd($request->all());

        // $lga_code = Lga::where('id', $request->input('lga'))->value('code');

        // $ward_code = Ward::where('id', $request->input('ward'))->value('code');
        // $pu_raw = PollingUnit::where('id', $request->input('pollingUnit'))->value('pu_number');

        // $pu_code = Str::padLeft($pu_raw, 3, '0');


        // $delimitation_code = "28/$lga_code/$ward_code/$pu_code";

        // dd($delimitation_code);



        // $pu_id = $request->input('pollingUnit');
        // $delimitation_code = PollingUnit::where('id', $pu_id)->value('delimitation_code');

        // dd($delimitation_code);


        $pu_id = $request->input('pollingUnit');
        $delimitation_code = $this->get_delimitation_code($pu_id);

        dd($delimitation_code);
    }


    public function ProcessSubmition(Request $request)
    {

        $form_type = $request->input('f_type');
        if ($form_type === 'member') {
            $ongoing = $this->processMembers($request);
            if ($ongoing) {
                // dd($ongoing->cbm_ond_id);
                $id = $ongoing->cbm_ond_id;
                return back()->with('mssg', $id);
            }
        }

        if ($form_type === 'volunteer') {
            $ongoing = $this->processVolunteer($request);
            // dd($ongoing);
            return back()->with('mssg', true);
        }

        if ($form_type === 'support') {
            $ongoing = $this->processSupport($request);
            // dd($ongoing);
            return back()->with('mssg', true);
        }
    }


    private function get_delimitation_code($pu_id)
    {

        $pollingUnit = PollingUnit::findOrFail($pu_id);
        return $pollingUnit->delimitation_code;
    }


    private function processMembers1(Request $data)
    {
        $request = $data;
        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'required',
            'email' => 'nullable|email',
            'gender' => 'required',
            'ageRange' => 'required',
            'state' => 'nullable',
            'lga' => 'required',
            'ward' => 'required',
            'pollingUnit' => 'required'
        ]);

        $delimitation_code = $this->get_delimitation_code($validated['pollingUnit']);
        $last_cbm_id = Members::latest()->value('cbm_ond_id');
        $prefix = Str::beforeLast($last_cbm_id, '/') . '/';
        $last_cbm_id = Str::afterLast($last_cbm_id, '/');
        $create_cmb_id = (int)$last_cbm_id + 1;

        $cbm_ond_id = str_pad($create_cmb_id, 3, '0', STR_PAD_LEFT);
        $cbm_ond_id = $prefix . $cbm_ond_id;

        dd($cbm_ond_id);

        $query = Members::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'gender' => $validated['gender'],
            'age_range' => $validated['ageRange'],
            'lga' => $validated['lga'],
            'ward' => $validated['ward'],
            'polling_unit' => $validated['pollingUnit'],
            'delimitation_code' => $delimitation_code,
            'cbm_ond_id' => $cbm_ond_id
        ]);

        if ($query) {
            return true;
        } else {
            return false;
        }

        // dd($validated, "delimitation_code : $delimitation_code");
        // return $data;

    }

    private function processMembers(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'required',
            'email' => 'nullable|email',
            'gender' => 'required',
            'ageRange' => 'required',
            'state' => 'nullable',
            'lga' => 'required',
            'ward' => 'required',
            'pollingUnit' => 'required',
            'support' => 'nullable',
            'contact' => 'nullable',
            'location_unit' => 'required'
        ]);

        return DB::transaction(function () use ($validated) {

            $pollingUnit = PollingUnit::where(
                'id',
                $validated['pollingUnit']
            )
                ->lockForUpdate()
                ->firstOrFail();

            $memberNumber = $pollingUnit->next_member_number;

            $cbmOndId = $pollingUnit->delimitation_code
                . '/'
                . str_pad($memberNumber, 3, '0', STR_PAD_LEFT);

            $member = Members::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'gender' => $validated['gender'],
                'age_range' => $validated['ageRange'],
                // 'state' => $validated['state'],
                'lga' => $validated['lga'],
                'ward' => $validated['ward'],
                'polling_unit' => $pollingUnit->id,
                'delimitation_code' => $pollingUnit->delimitation_code,
                'cbm_ond_id' => $cbmOndId,
                'support' => $validated['support'],
                'contact' => $validated['contact'],
                'location_unit' => $validated['location_unit'],
            ]);

            $pollingUnit->increment('next_member_number');

            return $member;
        });
    }

    private function processVolunteer(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'required',
            'email' => 'nullable|email',
            'gender' => 'required',
            'ageRange' => 'required',
            'state' => 'nullable',
            'lga' => 'required',
            'occupation' => 'required'
        ]);

        return DB::transaction(function () use ($validated) {

            $volunteer = Volunteers::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'gender' => $validated['gender'],
                'age_range' => $validated['ageRange'],
                'lga' => $validated['lga'],
                'occupation' => $validated['occupation']
            ]);

            return $volunteer;
        });
    }

    public function processSupport(Request $request)
    {

    // dd($request->all());

        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'required',
            'register_as' => 'required',
            'email' => 'nullable|email',
            'support_area' => 'required',
            'support_type' => 'required',
            'contribution_mssg' => 'required',
            'lga' => 'required',
            'contact' => 'required'
        ]);

        return DB::transaction(function () use ($validated) {

            $volunteer = Support::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'register_as' => $validated['register_as'],
                'support_area' => $validated['support_area'],
                'support_type' => $validated['support_type'],
                'contribution_mssg' => $validated['contribution_mssg'],
                'lga' => $validated['lga'],
                'contact' => $validated['contact']
            ]);

            return $volunteer;
        });
    }



}
