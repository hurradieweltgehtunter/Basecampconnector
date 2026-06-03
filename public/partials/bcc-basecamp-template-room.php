<?php
/**
 * Basecamp message body for a room request.
 *
 * Rendered inside Bcc_Request_Ajax::submit_request() via output buffering.
 * Reads the cleaned $data array; every field goes through esc_html().
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
Es ist eine neue <strong>Raumanfrage</strong> eingegangen.<br />
<br />
<h1>Ansprechperson</h1>
Name: <?php echo $field( 'name' ); ?><br />
Position: <?php echo $field( 'position' ); ?><br />
Wir sind: <?php echo $field( 'format' ); ?><?php echo $has( 'format_other' ) ? ' (' . $field( 'format_other' ) . ')' : ''; ?><br />
Mitgliedschaft: <?php echo $field( 'member' ); ?><br />
E-Mail: <?php echo $field( 'email' ); ?><br />
Telefon: <?php echo $field( 'phone' ); ?><br />
<br />
<h1>Nutzung</h1>
Art der Nutzung: <?php echo $field( 'usage_type' ); ?><br />
Gewünschtes Datum: <?php echo $field( 'date' ); ?><br />
Uhrzeit: <?php echo $field( 'time' ); ?><br />
Personenanzahl: <?php echo $field( 'people_count' ); ?><br />
<?php if ( $has( 'supervision' ) ) : ?>
Betreuung gewünscht: <?php echo $field( 'supervision' ); ?><br />
<?php endif; ?>
<br />
<strong>Warum konkret auf dem PLATZprojekt einen Raum?</strong><br />
<?php echo $field( 'motivation' ); ?>
<br />
<br />
<strong>Gewünschter Raum / Anforderungen (Größe, Beschaffenheit, etc.)</strong><br />
<?php echo $field( 'room_requirements' ); ?>
<br />
<br />
<strong>Ausführliche Beschreibung der Nutzung</strong><br />
<?php echo $field( 'usage_description' ); ?>
<br />
<br />
<?php if ( $has( 'equipment' ) ) : ?>
<strong>Benötigte Ausstattung / Materialien</strong><br />
<?php echo $field( 'equipment' ); ?>
<br />
<br />
<?php endif; ?>
<?php if ( $has( 'additional_info' ) ) : ?>
<strong>Weitere Infos oder Fragen</strong><br />
<?php echo $field( 'additional_info' ); ?>
<br />
<br />
<?php endif; ?>
<h1>Zustimmung</h1>
&#10003; Zustimmung zu Datenverarbeitung gemäß DSGVO §5 und Datenschutzbestimmungen<br />
<br />
Herzallerliebste Grüße<br />
Eure Website
