<x-public-page page="updates.page" eyebrow="INFORMATION DESK" title="Updates & membership information" intro="Practical information about using the CBM Ondo membership portal.">
    <article id="registration-guide" class="public-card">
        <span class="public-number">MEMBERSHIP GUIDE</span>
        <h2>Registration: what you need</h2>
        <p>Registration is available through the homepage. Start by selecting whether you have a voter card; the form will show the relevant fields.</p>
        <ol class="public-steps">
            <li><strong>Prepare your details.</strong> Have your name, Nigerian phone number, gender, age range and local government area ready. You can include an email address if you have one.</li>
            <li><strong>Select your location.</strong> If you have a voter card, choose the LGA, ward and polling unit associated with it. The available locations follow that order.</li>
            <li><strong>Review your answers.</strong> Check your contact details and preferences before submitting. Equivalent Nigerian phone formats are checked to help prevent duplicate registrations.</li>
            <li><strong>Keep your membership ID.</strong> After a successful registration, your CBM ID is generated and the page offers a membership-card download.</li>
        </ol>
        <a class="public-button" href="{{ route('new.homepage') }}#reg">Open registration</a>
    </article>
    <section class="public-card"><h2>Common questions</h2>
        <details><summary>Can I register without a voter card?</summary><p>Yes. Choose “No” when asked about a voter card. You will still select your local government area, but you will not need ward or polling-unit details.</p></details>
        <details><summary>Are a CBM ID and a membership code the same?</summary><p>The CBM ID identifies your membership. A separate location-based membership code is assigned to members registered with voter-card location details.</p></details>
        <details><summary>What if my phone number is already registered?</summary><p>Contact the team for help with the existing record instead of submitting another registration with a different number.</p><a class="public-link" href="{{ route('contact.page') }}">Contact membership support →</a></details>
    </section>
    <section class="public-note"><h2>Activities and event announcements</h2><p>No activity reports or event announcements have been published on this page yet.</p><p>For current event enquiries, <a href="{{ route('contact.page') }}">contact the team</a>.</p></section>
</x-public-page>
