<?php
/**
 * Basecamp message body for an event-booking request.
 *
 * Rendered inside Bcc_Request_Ajax::submit_request() via output buffering.
 * Reads the cleaned $data array; every field goes through esc_html() so the
 * rich-text body Basecamp stores is safe.
 *
 * @var array<string,string> $data Sanitised form fields.
 */

if ( ! isset( $data ) || ! is_array( $data ) ) {
	$data = array();
}
$field = static function ( string $key ) use ( $data ): string {
	return esc_html( (string) ( $data[ $key ] ?? '' ) );
};
$has = static function ( string $key ) use ( $data ): bool {
	return trim( (string) ( $data[ $key ] ?? '' ) ) !== '';
};
?>
Hi!<br />
Es ist eine neue <strong>Veranstaltungsanfrage</strong> eingegangen.<br />
<br />
<h1>Ansprechperson</h1>
Name: <?php echo $field( 'name' ); ?><br />
Wir sind: <?php echo $field( 'format' ); ?><br />
Mitgliedschaft: <?php echo $field( 'member' ); ?><br />
E-Mail: <?php echo $field( 'email' ); ?><br />
Telefon: <?php echo $field( 'phone' ); ?><br />
<?php if ( $has( 'social' ) ) : ?>
Social / Links: <?php echo $field( 'social' ); ?><br />
<?php endif; ?>
<br />
<h1>Veranstaltung</h1>
Name der Veranstaltung: <?php echo $field( 'event_name' ); ?><br />
Art der Veranstaltung: <?php echo $field( 'event_type' ); ?><br />
Gewünschtes Datum: <?php echo $field( 'date' ); ?><br />
Uhrzeit: <?php echo $field( 'time' ); ?><br />
Eintritt: <?php echo $field( 'entry' ); ?><br />
Veranstaltungsgröße / erwartete Besucher*innen: <?php echo $field( 'size' ); ?><br />
<br />
<strong>Ausführliche Beschreibung der Veranstaltung</strong><br />
<?php echo $field( 'event_description' ); ?>
<br />
<br />
<?php if ( $has( 'music' ) ) : ?>
<strong>Soll Musik gespielt werden? Wenn ja, welche?</strong><br />
<?php echo $field( 'music' ); ?>
<br />
<br />
<?php endif; ?>
<strong>Warum möchtest du / möchtet ihr auf dem PLATZprojekt veranstalten?</strong><br />
<?php echo $field( 'motivation' ); ?>
<br />
<br />
<h1>Bestätigte Pflichthinweise</h1>
Die anfragende Person hat bestätigt, dass ihr / ihnen bewusst ist:<br />
&#10003; Veranstalter*innen-Verantwortung wird getragen<br />
&#10003; private Veranstaltungen verboten, Veranstaltung ist öffentlich<br />
&#10003; selbst um helfende Hände kümmern (Theke, Auf-/Abbau, Einlass)<br />
&#10003; eigenständige Reinigung nach der Veranstaltung<br />
&#10003; Zustimmung zu Datenverarbeitung gemäß DSGVO §5 und Datenschutzbestimmungen<br />
<br />
Herzallerliebste Grüße<br />
Eure Website
