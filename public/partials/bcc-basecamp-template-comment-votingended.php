Die Abstimmung zur Erfassung eines Stimmungsbildes wurde beendet. Das Ergebnis:<br />
Abgegebene Stimmen: <?php echo $totalCount; ?><br />
<?php
foreach ($options as $key => $value) {
    echo $key . ': ' . $value . '<br />';
}
?>
<br />
<?php if ($win === true) : ?>
Damit ist die Mehrheit <strong>für eine Aufnahme</strong>. Das PLATZprojekt bekommt Zuwachs 🥳<br /> 
<br />
Wer übernimmt die Patenschaft für dieses Projekt?
<?php else : ?>
Damit ist das Projekt abgelehnt.
<?php endif; ?>
<br />
<br />
Bitte unbedingt das Projekt über den Fortschritt im Bewerbungsprozess informieren.
