<?php

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://florianlenz.com
 * @since      1.0.0
 *
 * @package    Bcc
 * @subpackage Bcc/public/partials
 */
?>

<form id="bcc" novalidate>
  <div class="container">
    <h2 class="mb-4">DU</h2>
    <div class="mb-5 inputwrap">
      <label for="format" class="form-label">Ich/wir sind</label>
      <select class="form-select" id="format" aria-label="Auswahl Unternehmensform" data-rule="mustnot:0">
        <option value="0" selected>Bitte auswählen ...</option>
        <option value="eine/mehrere Privatperson/en">eine/mehrere Privatperson/en</option>
        <option value="ein Verein">ein Verein</option>
        <option value="ein Unternehmen (GmbH, UG, etc.)">ein Unternehmen (GmbH, UG, etc.)</option>
      </select>
      <div class="error-feedback text-danger">
        Bitte wähle etwas aus
      </div>
    </div>

    <section class="mb-5">
      <p class="mb-3">
        Damit wir deine Anfrage zuverlässig bearbeiten und bei Rückfragen schnell nachfragen können können benötigen wir eurerseits jemanden als Ansprechpartner:in. Wer ist dies bei euch?
      </p>

      <div class="mb-3 inputwrap">
        <label for="name" class="form-label">Name</label>
        <input type="text" class="form-control" id="name" data-rule="required">
        <div class="error-feedback text-danger">
          Bitte gib deinen vollständigen Namen an
        </div>
      </div>

      <div class="mb-3 inputwrap">
        <label for="phone" class="form-label">Telefonnummer</label>
        <input type="text" class="form-control" id="phone" data-rule="required">
        <div class="error-feedback text-danger">
          Bitte gib deine Telefonnummer an
        </div>
      </div>

      <div class="mb-3 inputwrap">
        <label for="email" class="form-label">E-Mail-Adresse</label>
        <input type="email" class="form-control" id="email" data-rule="required|email">
        <div class="error-feedback text-danger">
          Bitte gib eine gültige E-Mail-Adresse an
        </div>
      </div>

      <p>Damit wir dir/euch Unterlagen zukommen lassen können benötigen wir eine Adresse. Diese kann später auch noch geändert werden.</p>
      <div class="mb-3 inputwrap">
        <label for="street" class="form-label">Strasse</label>
        <input type="text" class="form-control" id="street" data-rule="required">
        <div class="error-feedback text-danger">
          Bitte gib deine Strasse ein
        </div>
      </div>

      <div class="mb-3 inputwrap">
        <label for="houseno" class="form-label">Hausnummer</label>
        <input type="text" class="form-control" id="houseno" data-rule="required">
        <div class="error-feedback text-danger">
          Bitte gib deinen Hausnummer ein
        </div>
      </div>

      <div class="mb-3 inputwrap">
        <label for="zip" class="form-label">PLZ</label>
        <input type="text" class="form-control" id="zip" data-rule="required">
        <div class="error-feedback text-danger">
          Bitte gib deine Postleitzahl ein
        </div>
      </div>

      <div class="mb-3 inputwrap">
        <label for="city" class="form-label">Stadt</label>
        <input type="text" class="form-control" id="city" data-rule="required">
        <div class="error-feedback text-danger">
          Bitte gib deine Stadt ein
        </div>
      </div>
    </section>

    <section class="mb-5">
      <p class="mb-3">
        Wenn du möchtest kannst du uns hier deine Social-Media-Accounts nennen. Dies können deine/eure persönlichen oder auch die deines/eures Projekts sein. Damit bekommen wir einen beseren und persönlicheren Eindruck. Und wir folgen hin und wieder auch Personen ;)
      </p>

      <div class="mb-3">
        <label for="facebook" class="form-label">Facebook (optional)</label>
        <input type="text" class="form-control" id="facebook">
      </div>

      <div class="mb-3">
        <label for="instagram" class="form-label">Instagram (optional)</label>
        <input type="text" class="form-control" id="instagram">
      </div>

      <div class="mb-3">
        <label for="website" class="form-label">Website (optional)</label>
        <input type="text" class="form-control" id="website">
      </div>
    </section>

    <h2 class="mb-4">DEIN/EUER PROJEKT</h2>
    <section class="mb-5">
      <div class="mb-3 inputwrap">
        <p class="mb-3">Namen sind weit mehr als nur Schall und Rauch! Es hilft Menschen dein/euer Projekt besser zu merken. Deshalb ganz wichtig: Wie heißt dein/euer Projekt? (Dies kann auch erstmal nur ein temporärer Arbeitstitel sein)</p>

        <div class="mb-3">
          <label for="project_name" class="form-label" >Projektname</label>
          <input type="text" class="form-control" id="project_name" data-rule="required">
        </div>
        <div class="error-feedback text-danger">
          Bitte mach hierzu ein paar Angaben
        </div>
      </div>
      
      <div class="mb-3 word-count inputwrap">
        <p class="mb-3">Erläutere hier kurz worum es in deinem Projekt geht: Was ist deine/eure Projektidee? (max. 1000 Zeichen)
          <br />
          Ein paar Fragen als Hilfestellung:
        </p>

        <ul class="m-3">
          <li>Welches Problem löst deine/eure Idee?</li>
          <li>Was bietest du/ihr an?</li>
          <li>Welches Ziel verfolgst du/ihr damit?</li>
          <li>Was produzierst du/verarbeitest du?</li>
          <li>Wie bist du/seid ihr auf die Idee gekommen?</li>
        </ul>  

        <textarea class="form-control" id="project_1" rows="5" data-rule="required" maxlength="1500"></textarea>
        <span class="float-end label label-default count_message rounded px-2 py-1"><span>0</span> von 1500 Zeichen</span>
        <div class="error-feedback text-danger">
          Bitte mach hierzu ein paar Angaben
        </div>
      </div>

      <div class="mb-3 word-count inputwrap">
        <p class="mb-3">
          Das PLATZprojekt, als Anlaufstelle für alternative Stadtentwicklung bietet Raum für interessante Ideen, die sonst vielleicht keine Chance hätten. Warum glaubst du/ihr, dass die Stadt und unsere Gesellschaft dein/euer Projekt braucht? Warum gehört dein/euer Projekt auf das PLATZprojekt und nicht woanders hin? (max. 500 Zeichen)
        </p>

        <textarea class="form-control" id="project_2" rows="5" data-rule="required" maxlength="1500"></textarea>
        <span class="float-end label label-default count_message rounded px-2 py-1"><span>0</span> von 1500 Zeichen</span>
        <div class="error-feedback text-danger">
          Bitte mach hierzu ein paar Angaben
        </div>
      </div>

      <div class="mb-3 word-count inputwrap">
        <p class="mb-3">
          Welche Erwartungen und Wünsche hast du/habt ihr an das PLATZProjekt? Wie soll oder kann das PLATZprojekt dich/euch begleitend unterstützen? (max. 1500 Zeichen)
        </p>

        <textarea class="form-control" id="project_3" rows="5" data-rule="required" maxlength="1500"></textarea>
        <span class="float-end label label-default count_message rounded px-2 py-1"><span>0</span> von 1500 Zeichen</span>
        <div class="error-feedback text-danger">
          Bitte mach hierzu ein paar Angaben
        </div>
      </div>

      <div class="mb-3 word-count inputwrap">
        <p class="mb-3">
          Das PLATZProjekt ist nicht nur ein vielfältiger Freiraum für Freidenker aller Art sondern mittlerweile auch Lebens- und Arbeitsmittelpunkt für viele Menschen, die sich gegenseitig in allen Bereichen helfen. Wie wird sich deine Projekt in unsere Gemeinschaft einbringen? Welche Skills bringst du/bringt ihr mit? Gibt es in deinem/eurem Projekt Interaktionsmöglichkeiten für andere Vereinsmitglieder oder Gäste (Workshops, o.ä.)? (max. 1500 Wörter)
        </p>

        <textarea class="form-control" id="project_4" rows="5" data-rule="required" maxlength="1500"></textarea>
        <span class="float-end label label-default count_message rounded px-2 py-1"><span>0</span> von 1500 Zeichen</span>
        <div class="error-feedback text-danger">
          Bitte mach hierzu ein paar Angaben
        </div>
      </div>

      <div class="mb-3 word-count inputwrap">
        <p class="mb-3">
          Die meisten Projekte bei uns nutzen Schiffscontainer als Räumlichkeit. Wie soll dein/euer Projekt untergebracht werden? (max. 500 Zeichen)
        </p>

        <ul class="m-3">
          <li>Wird ein Container benötigt oder bringst du/ihr einen mit?
          <li>Gibt es spezielle Anforderungen an die Strom-, Wasser- oder Internetversorgung?</li>
          <li>Gibt es sonstige besondere Anforderungen?</li>
        </ul>

        <textarea class="form-control" id="project_5" rows="5" data-rule="required" maxlength="500"></textarea>
        <span class="float-end label label-default count_message rounded px-2 py-1"><span>0</span> von 500 Zeichen</span>
        <div class="error-feedback text-danger">
          Bitte mach hierzu ein paar Angaben
        </div>
      </div>

      <div class="mb-3 word-count inputwrap">
        <p class="mb-3">
        Abgesehen von Miete und Nebenkosten (die auch wir erheben müssen) erzeugt dein/euer Projekt evtl. noch andere Kosten. Wie hast du/ihr vor dein Projekt zu finanzieren? Benötigst du /ihr Beratung zu Finanzierung & Co.? (max. 500 Zeichen)
        </p>

        <textarea class="form-control" id="project_6" rows="5" data-rule="required" maxlength="500"></textarea>
        <span class="float-end label label-default count_message rounded px-2 py-1"><span>0</span> von 500 Zeichen</span>
        <div class="error-feedback text-danger">
          Bitte mach hierzu ein paar Angaben
        </div>
      </div>

      <div class="mb-3">
        <p class="mb-3">
          Gibt es sonst noch etwas was du uns mitteilen möchtest? (optional)
        </p>

        <textarea class="form-control" id="other" rows="5" maxlength="500"></textarea>
      </div>
    </section>

    <section class="mb-4">
      <div class="mb-3 inputwrap">
        <p class="mb-3">
          Mit dem Absenden dieses Formulars stimmst du der Übermittlung und Verarbeitung deiner Daten gemäß DSGVO §5 zu. Zudem akzeptierst du damit unsere <a target="_blank" href="https://platzprojekt.de/datenschutzerklaerung/">Datenschutzbestimmungen</a>
        </p>

        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="1" data-rule="required" id="termsAccepted">
          <label class="form-check-label" for="defaultCheck1">
            Ja, ich stimme zu.
          </label>
        </div>

        <div class="error-feedback text-danger">
          Bitte akzeptiere unsere Datenschutzbestimmungen
        </div>
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