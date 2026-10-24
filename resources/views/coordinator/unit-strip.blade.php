<section class="canvass-location" aria-label="Your polling unit">
    <div class="canvass-location-top"><x-canvass-icon name="pin"/><strong>Ward {{ $unit->ward->code }} · PU {{ str_pad((string) $unit->pu_number, 3, '0', STR_PAD_LEFT) }}</strong><span>{{ number_format($total) }} voters</span></div>
    <h2>{{ $unit->name }}</h2><p>{{ $unit->ward->lga->name }} · {{ $unit->ward->name }} · {{ $unit->delimitation_code }}</p>
</section>
