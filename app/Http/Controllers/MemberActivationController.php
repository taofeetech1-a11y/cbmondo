<?php

namespace App\Http\Controllers;

use App\Models\MemberAccount;
use App\Models\Membership;
use App\Support\MembershipContact;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MemberActivationController extends Controller
{
    public function flags(): View
    {
        $flags = DB::table('membership_duplicate_flags as flags')
            ->join('member_accounts as accounts', 'accounts.id', '=', 'flags.member_account_id')
            ->join('memberships as selected', 'selected.id', '=', 'accounts.membership_id')
            ->join('memberships as candidate', 'candidate.id', '=', 'flags.membership_id')
            ->select(['flags.*', 'selected.name as selected_name', 'selected.id as selected_id', 'candidate.name as candidate_name', 'candidate.cbm_id as candidate_reference'])
            ->orderByDesc('flags.id')->paginate(30);

        return view('member.duplicate-flags', compact('flags'));
    }

    public function create(Request $request): View
    {
        $state = $request->session()->get('member_activation');
        if ($state && $state['expires_at'] <= now()->timestamp) {
            $request->session()->forget('member_activation');
            $state = null;
        }

        return view('member.activate', ['step' => $state['step'] ?? 'phone', 'candidates' => ($state['step'] ?? null) === 'choose' ? $this->candidates($state['name']) : collect()]);
    }

    public function restart(Request $request): RedirectResponse
    {
        $request->session()->forget('member_activation');

        return redirect()->route('member.activate');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $request->session()->forget('member_activation');
        $data = $request->validate(['phone' => ['required', 'string', 'max:40']]);
        $phone = MembershipContact::phone($data['phone']);
        if (! preg_match('/^0[789][01][0-9]{8}$/', $phone)) {
            throw ValidationException::withMessages(['phone' => 'Enter a valid Nigerian mobile phone number.']);
        }
        $members = Membership::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', '') IN (?, ?, ?)", MembershipContact::phoneVariants($phone))->limit(2)->get();
        if ($members->count() !== 1 || MemberAccount::where('phone', $phone)->orWhereIn('membership_id', $members->modelKeys())->exists()) {
            throw ValidationException::withMessages(['phone' => 'Unable to activate this membership. If you already have a password, sign in; otherwise contact support.']);
        }
        $request->session()->regenerate();
        $request->session()->put('member_activation', ['step' => 'name', 'phone' => $phone, 'source_id' => $members->first()->id, 'expires_at' => now()->addMinutes(15)->timestamp]);

        return redirect()->route('member.activate');
    }

    public function confirmName(Request $request): RedirectResponse
    {
        $state = $this->state($request, 'name');
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $name = $this->normalizeName($data['name']);
        $member = Membership::find($state['source_id']);
        if (! $member || $name !== $this->normalizeName($member->name)) {
            throw ValidationException::withMessages(['name' => 'The name does not match this membership. Check the spelling or contact support.']);
        }
        $state['name'] = $name;
        $candidates = $this->candidates($name);
        $state['step'] = $candidates->count() > 1 ? 'choose' : 'password';
        $state['selected_id'] = $member->id;
        $state['selection_skipped'] = false;
        $request->session()->put('member_activation', $state);

        return redirect()->route('member.activate');
    }

    public function choose(Request $request): RedirectResponse
    {
        $state = $this->state($request, 'choose');
        $data = $request->validate(['membership_id' => ['required', 'string', 'max:30']]);
        $skipped = $data['membership_id'] === 'skip';
        $selectedId = $skipped ? $state['source_id'] : $data['membership_id'];
        if (! $this->candidates($state['name'])->contains('id', $selectedId)) {
            throw ValidationException::withMessages(['membership_id' => 'Choose one of the listed profiles or skip.']);
        }
        $state['selected_id'] = (int) $selectedId;
        $state['selection_skipped'] = $skipped;
        $state['step'] = 'password';
        $request->session()->put('member_activation', $state);

        return redirect()->route('member.activate');
    }

    public function store(Request $request): RedirectResponse
    {
        $state = $this->state($request, 'password');
        $data = $request->validate(['password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()]]);
        try {
            DB::transaction(function () use ($state, $data): void {
                $members = Membership::whereIn('id', [$state['source_id'], $state['selected_id']])->orderBy('id')->lockForUpdate()->get();
                $source = $members->firstWhere('id', $state['source_id']);
                $selected = $members->firstWhere('id', $state['selected_id']);
                if (! $source || ! $selected || MembershipContact::phone($source->phone) !== $state['phone'] || $this->normalizeName($source->name) !== $state['name'] || $this->normalizeName($selected->name) !== $state['name'] || MemberAccount::where('phone', $state['phone'])->orWhereIn('membership_id', $members->modelKeys())->exists()) {
                    throw ValidationException::withMessages(['password' => 'This membership has changed or is already activated. Start again or sign in.']);
                }
                $account = new MemberAccount;
                $account->forceFill(['membership_id' => $selected->id, 'phone' => $state['phone'], 'password' => $data['password']])->save();
                $flags = $this->candidates($state['name'])->reject(fn (Membership $candidate): bool => $candidate->id === $selected->id)->map(fn (Membership $candidate): array => [
                    'member_account_id' => $account->id, 'membership_id' => $candidate->id, 'selection_skipped' => $state['selection_skipped'], 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
                ])->all();
                if ($flags !== []) {
                    DB::table('membership_duplicate_flags')->insert($flags);
                }
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['password' => 'This membership is already activated. Please sign in.']);
        }
        $request->session()->forget('member_activation');
        $request->session()->regenerate();

        return redirect()->route('member.login')->with('status', 'Your password is ready. Sign in with your phone number and password.');
    }

    /** @return array{step: string, phone: string, source_id: int, expires_at: int, name?: string, selected_id?: int, selection_skipped?: bool} */
    private function state(Request $request, string $step): array
    {
        $state = $request->session()->get('member_activation');
        if (! $state || $state['expires_at'] <= now()->timestamp || $state['step'] !== $step) {
            $request->session()->forget('member_activation');
            throw ValidationException::withMessages(['phone' => 'Start activation again. Your session expired or a required step was missed.']);
        }

        return $state;
    }

    private function normalizeName(string $name): string
    {
        return Str::lower(Str::squish($name));
    }

    /** @return Collection<int, Membership> */
    private function candidates(string $name): Collection
    {
        return Membership::select(['id', 'name', 'phone', 'cbm_id'])->whereRaw("LOWER(TRIM(REPLACE(REPLACE(name, '  ', ' '), '  ', ' '))) = ?", [$name])->orderBy('id')->get();
    }
}
