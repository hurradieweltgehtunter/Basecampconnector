Hi!
Soeben hat sich ein neues Projekt bei uns beworben :) Was hälst du davon?<br />
<br />
Name des Projekts: <?php echo $_POST['data']['project_name']; ?><br />
Wir sind: <?php echo $_POST['data']['format']; ?><br />
<br />
<h1>Ansprechpartner</h1>
Name: <?php echo $_POST['data']['name']; ?><br />
Strasse: <?php echo $_POST['data']['street']; ?> <?php echo $_POST['data']['houseno']; ?><br />
Ort: <?php echo $_POST['data']['zip']; ?> <?php echo $_POST['data']['city']; ?><br />
<br />
<h1>Kontakt</h1>
Email: <?php echo $_POST['data']['email']; ?><br />
Telefon: <?php echo $_POST['data']['phone']; ?><br />
<?php if (trim($_POST['data']['website']) !== ''): ?>
    Website: <?php echo $_POST['data']['website']; ?><br />
<?php endif; ?>
<?php if (trim($_POST['data']['facebook']) !== ''): ?>
    Facebook: <?php echo $_POST['data']['facebook']; ?><br />
<?php endif; ?>
<?php if (trim($_POST['data']['instagram']) !== ''): ?>
    Instagram: <?php echo $_POST['data']['instagram']; ?><br />
<?php endif; ?>
<br />
<h1>Projekt</h1>
Die ausführlichen Fragen, die den Bewerber:innen gestellt wurden findest du hier: <?php echo $_POST['location']; ?><br />
<br />
<strong>Erläutere hier kurz worum es in deinem Projekt geht: Was ist deine/eure Projektidee?</strong><br />
<?php echo $_POST['data']['project_1']; ?>
<br />
<br />
<strong>Warum glaubst du/ihr, dass die Stadt und unsere Gesellschaft dein/euer Projekt braucht? Warum gehört dein/euer Projekt auf das PLATZprojekt und nicht woanders hin?</strong><br />
<?php echo $_POST['data']['project_2']; ?>
<br />
<br />
<strong>Welche Erwartungen und Wünsche hast du/habt ihr an das PLATZProjekt? Wie soll oder kann das PLATZprojekt dich/euch begleitend unterstützen?</strong><br />
<?php echo $_POST['data']['project_3']; ?>
<br />
<br />
<strong>Wie wird sich deine Projekt in unsere Gemeinschaft einbringen?</strong><br />
<?php echo $_POST['data']['project_4']; ?>
<br />
<br />
<strong>Wie soll dein/euer Projekt untergebracht werden?</strong><br />
<?php echo $_POST['data']['project_5']; ?>
<br />
<br />
<strong>Wie hast du/ihr vor dein Projekt zu finanzieren?</strong><br />
<?php echo $_POST['data']['project_6']; ?>
<br />
<br />
<?php if (trim($_POST['data']['other']) !== ''): ?>
<strong>Gibt es sonst noch etwas was du uns mitteilen möchtest?</strong><br />
<?php echo $_POST['data']['other']; ?>
<?php endif; ?>
<br />
<br />
<h1>Stimmungsbild</h1>
Bitte stimme kurz ab ob du der Meinung bist, dass dieses Projekt auf dem PLATZprojekt eine Zukunft hat oder nicht.<br />
Die Abstimmung endet am <?php echo $deadline->format('d.m.Y H:i'); ?>. <br />
<a href="<?php echo $pollData['url']; ?>">zur Abstimmung</a>
<br />
<br />
Herzallerliebste Grüße<br />
Eure Website