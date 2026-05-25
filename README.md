# Basecamp Connector

WordPress-Plugin für [platzprojekt.de](https://platzprojekt.de). Verbindet die Vereinsverwaltung [easyVerein](https://easyverein.com) und den Projektraum auf [Basecamp 3](https://basecamp.com).

Drei Funktionen, die unabhängig voneinander laufen:

1. **easyVerein → Basecamp User-Sync** (täglich via WP-Cron, oder manuell aus dem Settings-Menü). Neue Mitglieder mit gültiger Mitgliedsnummer werden ins Haupt-Projekt (+ optionale Zusatz-Projekte) eingeladen und bekommen einen Welcome-Kommentar.
2. **„Platz buchen"-Formular** (`[BasecampForm]` Shortcode). Submit erzeugt einen Basecamp-Message-Thread, eine Strawpoll-Abstimmung, eine Campfire-Line und eine To-do-Liste „Stimmungsbild".
3. **Strawpoll-Webhook** (`POST /wp-json/bcc/v1/webhook/`). Wenn eine Abstimmung endet, wird das Ergebnis als Kommentar an die ursprüngliche Message angehängt und das initiale To-do geschlossen.

## Anforderungen

- PHP 7.4 oder neuer (getestet bis 8.4)
- WordPress 6.0+
- MariaDB 10.4 / MySQL 5.7+
- Composer
- Basecamp 3 Account mit OAuth-App (https://launchpad.37signals.com/integrations)
- easyVerein API-Token (v3.0)

## Installation

```bash
cd wp-content/plugins/
git clone https://github.com/hurradieweltgehtunter/Basecampconnector.git
cd Basecampconnector
composer install --no-dev --optimize-autoloader
```

Plugin im WordPress-Backend aktivieren → Menüpunkt „Basecamp Connector" → Settings ausfüllen → unten auf „Authenticate app now" klicken (OAuth-Flow gegen Basecamp).

### Notwendige Settings

| Option | Beispielwert |
|---|---|
| `bcc_b3_account_id` | `3680781` (Account-ID aus Basecamp-URL) |
| `bcc_b3_user_agent` | `Mein Verein Connector (kontakt@mein-verein.de)` |
| `bcc_b3_client_id` / `bcc_b3_client_secret` | aus der Basecamp-OAuth-App |
| `bcc_ev_api_url` | `https://easyverein.com/api/v3.0/` |
| `bcc_ev_api_key` | Bearer-Token von easyVerein |
| `bcc_ev_project_id` | Basecamp-Projekt-ID, in das neue Mitglieder kommen |
| `bcc_ev_welcome_text_message_id` | Basecamp-Message-ID, unter der Welcome-Kommentare hängen |
| `bcc_ev_project_id_additional` | Komma-separierte Zusatz-Projekt-IDs (optional) |
| `bcc_admin_email` | Zusätzliche CC-Adresse für Fehler-Benachrichtigungen (optional) |

## Cron

Das Plugin registriert den Cron-Hook nicht selbst. Den Action-Hook `easy_verein_basecamp_sync` z. B. mit dem Plugin [WP Crontrol](https://wordpress.org/plugins/wp-crontrol/) auf einen täglichen Zeitpunkt legen. Ein WP-Transient-basierter Mutex verhindert parallele Läufe (z. B. wenn manueller + Cron-Trigger kollidieren).

## Logs

Pro Aktion entsteht eine eigene Logdatei mit Typ-Suffix:

| Datei-Suffix | Ausgelöst durch |
|---|---|
| `_sync.log` | EV-Sync (Cron + manueller Button) |
| `_submit.log` | „Platz buchen" Form-Submit via AJAX |
| `_webhook.log` | Eingehender Strawpoll-Deadline-Webhook |
| `_oauth.log` | Basecamp OAuth-Callback im Admin |

Vollständiges Schema: `YYYY-MM-DD_HHMMSS_<type>.log` (UTC, ohne Spaces/Doppelpunkte → FTP- und Windows-safe). Files älter als **90 Tage** werden beim nächsten Sync-Start automatisch gelöscht; `error.log` ist davon ausgenommen.

Uncaught Exceptions landen zusätzlich in `log/error.log`.

## Fehler-Benachrichtigungen

Wenn ein Sync, Webhook, OAuth-Callback oder Form-Submit eine nicht gefangene Exception wirft, geht eine strukturierte E-Mail an:

- **To:** WordPress Site-Admin (`get_option('admin_email')`)
- **CC:** der Wert von `bcc_admin_email` (wenn gesetzt)
- **CC (legacy):** über Filter `bcc_exception_notification_email` zusätzlich anhängbar

Der Mail-Body enthält Origin (`sync`/`submit`/`webhook`/`oauth`/`uncaught`), Site-URL, Plugin-Version, Zeitstempel, Fehlermeldung, Pfad zur Logdatei und ggf. Stack-Trace.

## Bekannte Quirks

- **Strawpoll DELETE** liefert seit Mitte 2026 `403 Not Authenticated`, wenn nur per `X-API-KEY` aufgerufen. Polls werden nicht mehr automatisch aus dem Strawpoll-Account gelöscht (laufen aber automatisch ab). Plugin loggt das als `warning` und macht keinen Alarm — Cleanup hat keinen funktionalen Effekt.
- **easyVerein ordering** akzeptiert nur camelCase-Quellfeldnamen (`-joinDate`), nicht snake_case (Response-Body bleibt snake_case). Siehe Kommentar in `EasyvereinClient::getNewestMembers()`.

## Migration von 1.x → 2.x

Plugin nutzte vormals den abandonierten `arturf/basecamp-api`-Wrapper auf Basis von `kriswallsmith/buzz` (HTTP-Client von 2018). Beides ist raus, ersetzt durch einen schlanken eigenen Basecamp-3-Client auf Guzzle 7. Die alten Schema-Annahmen für easyVerein (camelCase) sind entfernt; das Plugin liest v3-Schema (`membership_number`, `email_or_user_name`, `join_date`, `contact_details`, …) und rotiert Bearer-Tokens korrekt (Header `token_refresh_needed: True` und ein- bzw. zwei­seitige Token-Invalidierung).

`bcc_options.value` wird beim ersten Activate auf `LONGTEXT` migriert (vorher `VARCHAR(1000)` → konnte längere Bearer-Tokens abschneiden).

### Nach Deploy auf eine Bestandsinstallation

1. `composer install --no-dev` im Plugin-Verzeichnis.
2. Plugin im WP-Backend einmal deaktivieren + aktivieren — Activator migriert die DB-Spalte.
3. In den Settings die `EasyVerein API URL` auf `https://easyverein.com/api/v3.0/` umstellen (vorher zeigte sie auf das alte `hexa.easyverein.com/api/latest/`, das laut Anbieter abgekündigt ist).
4. Prüfen, ob `ev_bc_sync_last_new` in `<prefix>bcc_options` gesetzt ist UND auf einen Member zeigt, der noch in den **25 jüngsten** von easyVerein vorkommt. Wenn nicht (Sync-Window überschritten oder Wert leer): den Pointer manuell auf das aktuell jüngste Mitglied setzen. Das Plugin **weigert sich zu syncen** wenn der Pointer fehlt oder das Window übersteigt, um keine alten Mitglieder massenhaft erneut zu grant'en.

### Pointer-Format

Ab v2.x speichert das Plugin den Pointer in `<prefix>bcc_options.value` für `identifier='ev_bc_sync_last_new'` als JSON mit folgenden Feldern (alle optional außer `id` oder `email_or_user_name`):

```json
{
  "id": 5965971,
  "membership_number": "663",
  "first_name": "Elias",
  "family_name": "Hoffmann",
  "private_email": "hoffmann.elias5@yahoo.com",
  "join_date": "2026-04-10T00:00:00+02:00",
  "email_or_user_name": "hoffmann.elias5@yahoo.com",
  "synced_at": "2026-04-11T01:01:02+02:00"
}
```

- **`id` ist invariant** (easyVerein-PK) und wird beim Match bevorzugt — wenn ein Member seine Email im easyVerein ändert, bricht der Sync nicht.
- Alte 1.x-Pointer ohne `id` werden via `email_or_user_name`-Fallback unterstützt. Beim nächsten erfolgreichen Sync schreibt das Plugin das neue Format inkl. `id`.

### Member-Filter (was wird gesynct, was nicht)

| Bedingung | Verhalten |
|---|---|
| `membership_number` leer/null | skip — Vollmitglieds-Indikator fehlt (Application, Pseudo-Account, Funktionsmail) |
| `resignation_date` gesetzt und ≤ heute | skip — ausgetreten, kein Basecamp-Zugang mehr |
| Identifiziert als letzter gesyncter (per `id`, fallback `email_or_user_name`) | break — kein erneuter Grant |
| Bereits in Basecamp-Projekt drin | grant ist no-op (`granted=[]`), Welcome-Comment wird **nicht** gepostet |

### Hinweis zur easyVerein-Sortierung

`ordering=-joinDate` in der `/member`-API ist **kein Tippfehler**: die API erwartet bei Sortier-Feldern den ursprünglichen Django-Quellfeldnamen (camelCase), liefert im Response-Body aber snake_case. Snake-case-Sortierfelder werden zwar 200 zurückgegeben, aber stillschweigend ignoriert (Default-Sortierung ist asc by id). `joinDate` ist als Sortierfeld gewählt, weil es erst gesetzt wird, wenn ein Mitglied voll bezahlt — `id` würde Applications einschließen, die später erst Vollmitglied werden.

## Lizenz

GPL-2.0+.
