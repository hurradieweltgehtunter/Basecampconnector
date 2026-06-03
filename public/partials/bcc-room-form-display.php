<?php
/**
 * Public-facing markup for the room-request form ([BasecampRoomForm]).
 *
 * Field ids map 1:1 to the keys read in Bcc_Request_Ajax::formConfig('room')
 * and rendered in partials/bcc-basecamp-template-room.php.
 *
 * @package    Bcc
 * @subpackage Bcc/public/partials
 */
?>

<form class="bcc-form" data-bcc-type="room" novalidate>
    <div class="container">
    <h2 class="mb-4">IHR / DU</h2>

    <div class="mb-3 inputwrap">
        <label for="format" class="form-label">Ich / wir sind</label>
        <select class="form-select" id="format" data-rule="mustnot:0">
            <option value="0" selected>Bitte auswählen ...</option>
            <option value="eine / mehrere Privatperson/en">eine / mehrere Privatperson/en</option>
            <option value="ein Verein">ein Verein</option>
            <option value="ein Unternehmen">ein Unternehmen</option>
            <option value="Sonstiges">Sonstiges</option>
        </select>
        <div class="error-feedback text-danger">Bitte wähle etwas aus</div>
    </div>

    <div class="mb-4">
        <label for="format_other" class="form-label">Falls Sonstiges: bitte kurz beschreiben (optional)</label>
        <input type="text" class="form-control" id="format_other">
    </div>

    <div class="mb-5 inputwrap">
        <label for="member" class="form-label">Mitglied?</label>
        <select class="form-select" id="member" data-rule="mustnot:0">
            <option value="0" selected>Bitte auswählen ...</option>
            <option value="Mitglied beim PLATZprojekt e. V.">Ich bin Mitglied beim PLATZprojekt e. V.</option>
            <option value="kein Mitglied vom PLATZprojekt e. V.">kein Mitglied vom PLATZprojekt e. V.</option>
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
            <label for="position" class="form-label">Position</label>
            <input type="text" class="form-control" id="position" data-rule="required">
            <div class="error-feedback text-danger">Bitte gib deine Position an</div>
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
    </section>

    <h2 class="mb-4">DIE NUTZUNG</h2>
    <section class="mb-5">
        <div class="mb-3 inputwrap">
            <label for="usage_type" class="form-label">Für welche Art von Nutzung suchst du einen Raum?</label>
            <input type="text" class="form-control" id="usage_type" data-rule="required">
            <div class="error-feedback text-danger">Bitte mach hierzu eine Angabe</div>
        </div>

        <div class="mb-3 word-count inputwrap">
            <label for="motivation" class="form-label">Warum suchst du / sucht ihr konkret auf dem PLATZprojekt einen Raum?</label>
            <textarea class="form-control" id="motivation" rows="5" data-rule="required" maxlength="1500"></textarea>
            <span class="float-end label label-default count_message rounded px-2 py-1"><span>0</span> von 1500 Zeichen</span>
            <div class="error-feedback text-danger">Bitte mach hierzu ein paar Angaben</div>
        </div>

        <div class="mb-3 word-count inputwrap">
            <label for="room_requirements" class="form-label">Welchen Raum würdest du gern nutzen bzw. welche Anforderung hast du an den Raum (Größe, Beschaffenheit, etc.)?</label>
            <textarea class="form-control" id="room_requirements" rows="5" data-rule="required" maxlength="1500"></textarea>
            <span class="float-end label label-default count_message rounded px-2 py-1"><span>0</span> von 1500 Zeichen</span>
            <div class="error-feedback text-danger">Bitte mach hierzu ein paar Angaben</div>
        </div>

        <div class="mb-3 word-count inputwrap">
            <label for="usage_description" class="form-label">Ausführliche Beschreibung der Nutzung</label>
            <textarea class="form-control" id="usage_description" rows="5" data-rule="required" maxlength="1500"></textarea>
            <span class="float-end label label-default count_message rounded px-2 py-1"><span>0</span> von 1500 Zeichen</span>
            <div class="error-feedback text-danger">Bitte beschreibe die Nutzung</div>
        </div>

        <div class="mb-3">
            <label for="equipment" class="form-label">Welche Ausstattung oder Materialien benötigst du? (optional)</label>
            <textarea class="form-control" id="equipment" rows="3" maxlength="1000"></textarea>
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
            <label for="people_count" class="form-label">Personenanzahl</label>
            <input type="text" class="form-control" id="people_count" data-rule="required">
            <div class="error-feedback text-danger">Bitte gib eine Personenanzahl an</div>
        </div>

        <div class="mb-3">
            <label for="supervision" class="form-label">Wünschst du dir eine Betreuung? (optional)</label>
            <select class="form-select" id="supervision">
                <option value="" selected>Bitte auswählen ...</option>
                <option value="Ja">Ja</option>
                <option value="Nein">Nein</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="additional_info" class="form-label">Weitere Infos oder Fragen (optional)</label>
            <textarea class="form-control" id="additional_info" rows="4" maxlength="1500"></textarea>
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
