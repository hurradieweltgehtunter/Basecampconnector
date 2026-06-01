<?php
/**
 * Basecamp message body for a new project application.
 *
 * Rendered inside Plugin_Public_Ajax::submit_project() via output buffering.
 * Reads the cleaned, unslashed $data array (NOT raw $_POST) so user quotes
 * arrive intact; each field is run through esc_html() so the HTML rich-text
 * body Basecamp stores is safe and renders real quotes instead of \" or &quot;.
 *
 * @var array<string,string> $data     Sanitised form fields.
 * @var DateTime             $deadline Vote deadline.
 * @var array<string,mixed>  $pollData Strawpoll data (url, id).
 */

if ( ! isset( $data ) || ! is_array( $data ) ) {
	$data = array();
}
$field = static function ( string $key ) use ( $data ): string {
	return esc_html( (string) ( $data[ $key ] ?? '' ) );
};
?>
Hi!
Soeben hat sich ein neues Projekt bei uns beworben :) Was hälst du davon?<br />
<br />
Name des Projekts: <?php echo $field( 'project_name' ); ?><br />
Wir sind: <?php echo $field( 'format' ); ?><br />
<br />
<h1>Ansprechpartner</h1>
Name: <?php echo $field( 'name' ); ?><br />
Strasse: <?php echo $field( 'street' ); ?> <?php echo $field( 'houseno' ); ?><br />
Ort: <?php echo $field( 'zip' ); ?> <?php echo $field( 'city' ); ?><br />
<br />
<h1>Kontakt</h1>
Email: <?php echo $field( 'email' ); ?><br />
Telefon: <?php echo $field( 'phone' ); ?><br />
<?php if ( trim( (string) ( $data['website'] ?? '' ) ) !== '' ) : ?>
    Website: <?php echo $field( 'website' ); ?><br />
<?php endif; ?>
<?php if ( trim( (string) ( $data['facebook'] ?? '' ) ) !== '' ) : ?>
    Facebook: <?php echo $field( 'facebook' ); ?><br />
<?php endif; ?>
<?php if ( trim( (string) ( $data['instagram'] ?? '' ) ) !== '' ) : ?>
    Instagram: <?php echo $field( 'instagram' ); ?><br />
<?php endif; ?>
<br />
<h1>Projekt</h1>
Die ausführlichen Fragen, die den Bewerber:innen gestellt wurden findest du hier: <?php echo isset( $_POST['location'] ) ? esc_url( wp_unslash( $_POST['location'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing ?><br />
<br />
<strong>Erläutere hier kurz worum es in deinem Projekt geht: Was ist deine/eure Projektidee?</strong><br />
<?php echo $field( 'project_1' ); ?>
<br />
<br />
<strong>Warum glaubst du/ihr, dass die Stadt und unsere Gesellschaft dein/euer Projekt braucht? Warum gehört dein/euer Projekt auf das PLATZprojekt und nicht woanders hin?</strong><br />
<?php echo $field( 'project_2' ); ?>
<br />
<br />
<strong>Welche Erwartungen und Wünsche hast du/habt ihr an das PLATZProjekt? Wie soll oder kann das PLATZprojekt dich/euch begleitend unterstützen?</strong><br />
<?php echo $field( 'project_3' ); ?>
<br />
<br />
<strong>Wie wird sich deine Projekt in unsere Gemeinschaft einbringen?</strong><br />
<?php echo $field( 'project_4' ); ?>
<br />
<br />
<strong>Wie soll dein/euer Projekt untergebracht werden?</strong><br />
<?php echo $field( 'project_5' ); ?>
<br />
<br />
<strong>Wie hast du/ihr vor dein Projekt zu finanzieren?</strong><br />
<?php echo $field( 'project_6' ); ?>
<br />
<br />
<?php if ( trim( (string) ( $data['other'] ?? '' ) ) !== '' ) : ?>
<strong>Gibt es sonst noch etwas was du uns mitteilen möchtest?</strong><br />
	<?php echo $field( 'other' ); ?>
<?php endif; ?>
<br />
<br />
<h1>Stimmungsbild</h1>
Bitte stimme kurz ab ob du der Meinung bist, dass dieses Projekt auf dem PLATZprojekt eine Zukunft hat oder nicht.<br />
Die Abstimmung endet am <?php echo esc_html( $deadline->format( 'd.m.Y H:i' ) ); ?>. <br />
<a href="<?php echo esc_url( (string) ( $pollData['url'] ?? '' ) ); ?>">zur Abstimmung</a>
<br />
<br />
Herzallerliebste Grüße<br />
Eure Website
