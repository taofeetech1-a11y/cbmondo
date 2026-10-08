<?php

use App\Models\User;
use Database\Factories\MembershipFactory;

beforeEach(function () {
    config(['app.url' => 'https://master.cbmondo.org']);
});

test('public pages have one head and stable metadata without tracking parameters', function (string $path, string $title) {
    $response = $this->get($path.'?utm_source=test');
    $response->assertOk()->assertSee($title)->assertSee('href="https://master.cbmondo.org'.$path.'"', false)->assertHeaderMissing('X-Robots-Tag');
    expect(substr_count($response->getContent(), '<head>'))->toBe(1);
    expect(substr_count($response->getContent(), '<title>'))->toBe(1);
    expect(substr_count($response->getContent(), 'rel="canonical"'))->toBe(1);
})->with([
    ['/', 'City Boy Movement Ondo State | Membership Registration'],
    ['/support', 'Support City Boy Movement Ondo State | Partner With Us'],
    ['/about', 'About Polling Unit Connect | CBM Ondo'],
    ['/contact', 'Contact City Boy Movement Ondo State | Membership Help'],
    ['/updates', 'Updates and Registration Information | CBM Ondo'],
]);

test('homepage publishes valid organization and website structured data', function () {
    $html = $this->get('/')->getContent();
    preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $match);
    $data = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
    expect($data['@graph'][0]['name'])->toBe('City Boy Movement Ondo State');
    expect($data['@graph'][0]['url'])->toBe('https://master.cbmondo.org/');
    expect($data['@graph'][1]['@type'])->toBe('WebSite');
});

test('sitemap contains only canonical public pages and robots advertises it', function () {
    $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    $xml = simplexml_load_string($response->getContent());
    $urls = array_map(fn ($entry) => (string) $entry->loc, iterator_to_array($xml->url, false));
    expect($urls)->toBe(['https://master.cbmondo.org/', 'https://master.cbmondo.org/support', 'https://master.cbmondo.org/about', 'https://master.cbmondo.org/contact', 'https://master.cbmondo.org/updates']);
    $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: https://master.cbmondo.org/sitemap.xml')->assertDontSee('Disallow: /membership');
});

test('member records and downloadable files are excluded from indexing', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $member = MembershipFactory::new()->create();
    foreach (['/membership', '/membership/'.$member->id, '/membership/'.$member->id.'/card', '/membership/export/csv', '/membership/import'] as $path) {
        $this->get($path)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }
});

test('utility and event pages are excluded from indexing', function (string $path) {
    $this->get($path)->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
})->with(['/login', '/event', '/up']);

test('search verification is optional and safely escaped', function () {
    $this->get('/')->assertDontSee('name="google-site-verification"', false);
    config(['seo.verification' => 'verification-token']);
    $this->get('/')->assertSee('name="google-site-verification" content="verification-token"', false);
});

test('public information pages provide navigation and real contact actions', function () {
    $this->get('/about')->assertSee(route('contact.page'), false)->assertSee(route('updates.page'), false);
    $this->get('/contact')->assertSee('href="tel:+2348130930238"', false)->assertSee('href="mailto:info@cbmondo.org"', false)->assertSee('To be added.');
    $this->get('/updates')->assertSee('Registration: what you need')->assertSee('No activity reports or event announcements have been published');
    $this->get('/')->assertSee(route('about.page'), false)->assertSee(route('updates.page'), false);
});
