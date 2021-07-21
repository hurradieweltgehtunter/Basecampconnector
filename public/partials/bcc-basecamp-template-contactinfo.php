Kontaktdaten:<br />
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