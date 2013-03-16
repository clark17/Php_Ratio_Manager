<?php
/*
Script d'installation pour php ratio manager
- Cr�er les fichiers .htaccess
*/

// Fonction installation
function installation() {
	$contenu_htaccess = "";
	// Ajout .htaccess pour Free.fr
	if  (substr($_SERVER["SERVER_NAME"], -7) == "free.fr") { 
		$chemin_htaccess = ".htaccess";
		$contenu_htaccess = "php 1"; // Active php 5 chez l'h�bergeur Free.fr
	}
	// Installation sur un autre syst�me (mot de passe chiffr�)
	$chemin_htaccess = ".htaccess";
	$contenu_htaccess .= "Options all -Indexes\n
						<filesMatch \".(htaccess|ini|log|sqlite|php)$\">\n
    						order deny,allow\n
							deny from all\n
						</filesMatch>\n
						<filesMatch \"(index.php|recuperation_ratio.php|generer_ratio.php)\">\n
    						order allow,deny\n
							allow from all\n
						</filesMatch>";
	// On utilise fopen-fputs-fclose car chez free.fr uniquement php4 tant que .htaccess ne contient pas php 1 
	$fichier = fopen($chemin_htaccess, "w+");
	fputs($fichier, $contenu_htaccess);
	fclose($fichier);
	
	// Cr�ation de la bdd et inscription de l'administrateur
	try {
		$bdd_handle = new PDO('sqlite:bdd/db.sqlite');
		$bdd_handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		// Cr�ation des tables
		$query = "CREATE TABLE membres (id INTEGER PRIMARY KEY, utilisateur TEXT, mot_de_passe TEXT, email TEXT, statut TEXT)";
		$requete = $bdd_handle->prepare($query);
		$requete->execute();
		$query = "CREATE TABLE taches (id INTEGER PRIMARY KEY, id_membre TEXT, site TEXT, utilisateur TEXT, mot_de_passe TEXT, dernier_ratio NUMERIC, ratio_minimum NUMERIC, user_agent TEXT, type_connection TEXT, timestamp_dernier_ratio NUMERIC, timestamp_dernier_upload NUMERIC)";
		$requete = $bdd_handle->prepare($query);
		$requete->execute();
		// Insertion de l'administrateur
		$query = "INSERT INTO membres (utilisateur, mot_de_passe, email, statut) VALUES ( ?, ?, ?, ? )";
		$requete = $bdd_handle->prepare($query);
		$requete->execute(array($_POST['identifiant'], $_POST['mot_de_passe'], $_POST['email'], 'administrateur'));

		// On ferme la bdd
		$bdd_handle = NULL;
	
	} catch (Exception $e) {
		die('Erreur : '.$e->getMessage());
	}
	
	// Cr�ation du r�pertoire utilisateur
	mkdir("utilisateurs/".$_POST['identifiant']);
	
	// Fin d'installation
	echo "Installation termin�e.<br>!!! ATTENTION !!! N'oubliez pas d'�ffacer le fichier installation.php pour d'�videntes raisons de s�curit�.<br><a href='index.php'>Ouvrir Php Ratio Manager</a>";
}

// Liste les commandes disponibles
if (!empty($_POST)) {
	if ($_POST['action'] == "installation") { installation(); }
	else { echo "Mauvaise commande POST.<br><a href='installation.php'>Retour</a>"; }
 }
else {
	echo '
	<form action="installation.php" method="post">
		<p>
			Informations sur l\'administrateur du site:<br />
			<label for="identifiant">Identifiant:</label>
			<input type="text" name="identifiant" id="identifiant" title="identifiant" size="15" maxlength="50"><br>
			<label for="mot_de_passe">Mot de passe:</label>
			<input type="password" name="mot de passe" id="mot_de_passe" title="Mot de passe" size="15" maxlength="50"><br>
			<label for="email">E-mail:</label>
			<input type="text" name="email" id="email" title="E-mail" size="15" maxlength="50"><br>
			<input type="hidden" name="action" value="installation">
			<input type="submit" value="Appliquer" />
		</p>
	</form>
';
}
?>