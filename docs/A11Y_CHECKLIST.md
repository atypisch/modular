# Toegankelijkheid (a11y) – checklist

Korte checklist voor admin en public theme. Uitgebreide audit kan hierop volgen.

## Algemeen

- [ ] **Focus order:** Logische tab-volgorde (formuliervelden, knoppen, sidebar).
- [ ] **Skip link:** “Skip to main content” voor toetsenbordgebruikers (admin/public).
- [ ] **Heading hierarchy:** Eén H1 per pagina; H2 → H3 logisch.

## Formulieren

- [ ] **Labels:** Elk input heeft een `<label for="id">` of aria-label.
- [ ] **Errors:** Foutmeldingen gekoppeld aan veld (aria-describedby of aria-errormessage).
- [ ] **Required:** Verplichte velden met `required` en/of `aria-required="true"`.

## Knoppen en links

- [ ] **Tekst:** Knoppen/links hebben duidelijke tekst of `aria-label` (icon-only).
- [ ] **Toetsenbord:** Alle acties bereikbaar en uitvoerbaar via toetsenbord.

## Contrast en kleur

- [ ] **Tekst:** Contrastratio minimaal 4.5:1 (normaal), 3:1 (groot).
- [ ] **Geen kleur alleen:** Info/fout niet alleen via kleur; iconen of tekst.

## Admin-specifiek

- [ ] **Sidebar:** `aria-label` op nav; collapse-knop `aria-expanded`.
- [ ] **Tables:** `<th scope="col">`; waar nodig scope="row".
- [ ] **Modals:** Focus trap; sluiten met Escape; focus terug naar trigger.

## Public theme

- [ ] **Media:** Alt-tekst op images (admin: media alt_text veld).
- [ ] **Player/iframe:** Titel of aria-label waar relevant.

## Genomen maatregelen (audit-afronding)

- Login/register/forgot forms: labels aanwezig; CSRF hidden field niet focusable (hidden input).
- Sidebar toggle: `aria-label="Collapse sidebar"` in sidebar.php.
- Capability-mapping gedocumenteerd voor consistente permission checks.

## Vervolg

- Volledige audit met screen reader (NVDA/VoiceOver) en axe DevTools.
- Issues als nieuwe punten in SIMUZE_WP_TO_MODULAR_AUDIT.md sectie 6 opnemen.
