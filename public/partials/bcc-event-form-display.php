<?php
/**
 * Public-facing markup for the event-booking request form ([BasecampEventForm]).
 *
 * Field ids map 1:1 to the keys read in Bcc_Request_Ajax::formConfig('event')
 * and rendered in partials/bcc-basecamp-template-event.php.
 *
 * @package    Bcc
 * @subpackage Bcc/public/partials
 */
?>

<form class="bcc-form" data-bcc-type="event" novalidate>
    <div class="container">
    <h2 class="mb-4">IHR / DU</h2>

    <div class="mb-4 inputwrap">
        <label for="format" class="form-label">Ich bin / wir sind</label>
        <select class="form-select" id="format" data-rule="mustnot:0">
            <option value="0" selected>Bitte auswählen ...</option>
            <option value="eine / mehrere Privatperson/en">eine / mehrere Privatperson/en</option>
            <option value="ein Verein">ein Verein</option>
            <option value="ein Unternehmen">ein Unternehmen</option>
        </select>
        <div class="error-feedback text-danger">Bitte wähle etwas aus</div>
    </div>

    <div class="mb-5 inputwrap">
        <label for="member" class="form-label">Mitglied?</label>
        <select class="form-select" id="member" data-rule="mustnot:0">
            <option value="0" selected>Bitte auswählen ...</option>
            <option value="Mitglied beim PLATZprojekt e. V.">Ich bin Mitglied beim PLATZprojekt e. V.</option>
            <option value="noch kein Mitglied vom PLATZprojekt e. V.">noch kein Mitglied vom PLATZprojekt e. V.</option>
        </select>
        <div class="error-feedback text-danger">Bitte wähle etwas aus</div>
    </div>

    <section class="mb-5">
        <p class="mb-3">Damit wir deine Anfrage bearbeiten und bei Rückfragen schnell nachfragen können, brauchen wir eine Ansprechperson.</p>

        <div class="mb-3 inputwrap">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" data-rule="required">
            <div class="error-feedback text-danger">Bitte gib deinen vollständigen Namen an</div>
        </div>

        <div class="mb-3 inputwrap">
            <label for="phone" class="form-label">Telefonnummer</label>
            <input type="text" class="form-control" id="phone" data-rule="required">
            <div class="error-feedback text-danger">Bitte gib deine Telefonnummer an</div>
        </div>

        <div class="mb-3 inputwrap">
            <label for="email" class="form-label">E-Mail-Adresse</label>
            <input type="email" class="form-control" id="email" data-rule="required|email">
            <div class="error-feedback text-danger">Bitte gib eine gültige E-Mail-Adresse an</div>
        </div>

        <div class="mb-3">
            <label for="social" class="form-label">Link zu Insta, Soundcloud, Website o. Ä. (optional)</label>
            <input type="text" class="form-control" id="social">
        </div>
    </section>

    <h2 class="mb-4">DIE VERANSTALTUNG</h2>
    <section class="mb-5">
        <div class="mb-3 inputwrap">
            <label for="event_name" class="form-label">Name der Veranstaltung</label>
            <input type="text" class="form-control" id="event_name" data-rule="required">
            <div class="error-feedback text-danger">Bitte gib einen Namen an</div>
        </div>

        <div class="mb-3 inputwrap">
            <label for="event_type" class="form-label">Art der Veranstaltung</label>
            <select class="form-select" id="event_type" data-rule="mustnot:0">
                <option value="0" selected>Bitte auswählen ...</option>
                <option value="gemütlicher Barabend">gemütlicher Barabend</option>
                <option value="Party">Party</option>
                <option value="Konzert">Konzert</option>
                <option value="Performance">Performance</option>
                <option value="Ausstellung">Ausstellung</option>
                <option value="Open Air">Open Air</option>
                <option value="sonstiges">sonstiges</option>
            </select>
            <div class="error-feedback text-danger">Bitte wähle etwas aus</div>
        </div>

        <div class="mb-3 word-count inputwrap">
            <label for="event_description" class="form-label">Ausführliche Beschreibung der Veranstaltung</label>
            <textarea class="form-control" id="event_description" rows="5" data-rule="required" maxlength="1500"></textarea>
            <span class="float-end label label-default count_message rounded px-2 py-1"><span>0</span> von 1500 Zeichen</span>
            <div class="error-feedback text-danger">Bitte beschreibe deine Veranstaltung</div>
        </div>

        <div class="mb-3">
            <label for="music" class="form-label">Soll Musik gespielt werden? Wenn ja, welche? (gern Soundcloud-Link o. Ä.) (optional)</label>
            <textarea class="form-control" id="music" rows="3" maxlength="1000"></textarea>
        </div>

        <div class="mb-3 word-count inputwrap">
            <label for="motivation" class="form-label">Warum möchtest du / möchtet ihr auf dem PLATZprojekt veranstalten?</label>
            <textarea class="form-control" id="motivation" rows="5" data-rule="required" maxlength="1500"></textarea>
            <span class="float-end label label-default count_message rounded px-2 py-1"><span>0</span> von 1500 Zeichen</span>
            <div class="error-feedback text-danger">Bitte mach hierzu ein paar Angaben</div>
        </div>

        <div class="mb-3 inputwrap">
            <label for="date" class="form-label">Gewünschtes Datum</label>
            <input type="date" class="form-control" id="date" data-rule="required">
            <div class="error-feedback text-danger">Bitte gib ein Datum an</div>
        </div>

        <div class="mb-3 inputwrap">
            <label for="time" class="form-label">Uhrzeit (Anfang und Ende)</label>
            <input type="text" class="form-control" id="time" placeholder="z. B. 18:00 – 23:00" data-rule="required">
            <div class="error-feedback text-danger">Bitte gib eine Uhrzeit an</div>
        </div>

        <div class="mb-3 inputwrap">
            <label for="entry" class="form-label">Eintritt</label>
            <select class="form-select" id="entry" data-rule="mustnot:0">
                <option value="0" selected>Bitte auswählen ...</option>
                <option value="Ja, mit Slidingscale">Ja, mit Slidingscale</option>
                <option value="Nein">Nein</option>
            </select>
            <div class="error-feedback text-danger">Bitte wähle etwas aus</div>
        </div>

        <div class="mb-3 inputwrap">
            <label for="size" class="form-label">Veranstaltungsgröße / erwartete Zahl der Besucher*innen</label>
            <input type="text" class="form-control" id="size" data-rule="required">
            <div class="error-feedback text-danger">Bitte mach hierzu eine Angabe</div>
        </div>
    </section>

    <section class="mb-5">
        <p class="mb-3">Mir / uns ist bewusst, dass (alles Pflichtangaben):</p>

        <div class="form-check mb-2 inputwrap">
            <input class="form-check-input" type="checkbox" value="1" data-rule="required" id="aware_responsibility">
            <label class="form-check-label" for="aware_responsibility">ich / wir bei der Veranstaltung die Veranstalter*innen sind und somit die Verantwortung tragen</label>
            <div class="error-feedback text-danger">Bitte bestätige diesen Punkt</div>
        </div>

        <div class="form-check mb-2 inputwrap">
            <input class="form-check-input" type="checkbox" value="1" data-rule="required" id="aware_public">
            <label class="form-check-label" for="aware_public">private Veranstaltungen auf dem PLATZprojekt verboten sind und die Veranstaltung öffentlich ist</label>
            <div class="error-feedback text-danger">Bitte bestätige diesen Punkt</div>
        </div>

        <div class="form-check mb-2 inputwrap">
            <input class="form-check-input" type="checkbox" value="1" data-rule="required" id="aware_helpers">
            <label class="form-check-label" for="aware_helpers">ich / wir uns um helfende Hände (z. B. Thekenschichten, Auf-/Abbau, Einlass) kümmern müssen</label>
            <div class="error-feedback text-danger">Bitte bestätige diesen Punkt</div>
        </div>

        <div class="form-check mb-2 inputwrap">
            <input class="form-check-input" type="checkbox" value="1" data-rule="required" id="aware_cleanup">
            <label class="form-check-label" for="aware_cleanup">ich / wir nach der Veranstaltung eigenständig für die Reinigung des Veranstaltungsorts verantwortlich sind</label>
            <div class="error-feedback text-danger">Bitte bestätige diesen Punkt</div>
        </div>
    </section>

    <section class="mb-4">
        <div class="mb-3 inputwrap">
            <p class="mb-3">Mit dem Absenden dieses Formulars stimmst du der Übermittlung und Verarbeitung deiner Daten gemäß DSGVO §5 zu. Zudem akzeptierst du damit unsere <a target="_blank" href="https://platzprojekt.de/datenschutzerklaerung/">Datenschutzbestimmungen</a>.</p>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" data-rule="required" id="termsAccepted">
                <label class="form-check-label" for="termsAccepted">Ja, ich stimme zu.</label>
            </div>
            <div class="error-feedback text-danger">Bitte akzeptiere unsere Datenschutzbestimmungen</div>
        </div>
    </section>

    <div class="alert alert-danger mb-3 general-error-feedback d-none">
        <p>Whoops, da passt was nicht. Bitte scrolle nach oben und überprüfe deine Eingaben.</p>
    </div>
    <div class="alert alert-danger mb-3 general-error-ajax d-none">
        <p>Leider konnte deine Anfrage nicht abgeschlossen werden. Bitte versuche es später noch einmal oder kontaktiere uns per E-Mail.</p>
    </div>
    <div class="alert alert-success mb-3 success-feedback d-none">
        <p>Deine Anfrage wurde erfolgreich übermittelt. Danke dafür! Wir melden uns bei dir!</p>
    </div>

    <button type="submit" class="btn btn-primary g-recaptcha">Absenden <span class="spinner-border spinner-border-sm ml-3" role="status" aria-hidden="true"></span></button>
    </div>
</form>
