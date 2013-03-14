<?php
/*
 R�cup�ration du ratio pour les utilisateurs, utilisation de la t�che la plus ancienne.
*/

// Importation des Librairies
include("../librairies/ini.php");
include("../librairies/log.php");

// Importation des Configuration
$site_torrent_ini = parse_ini_file("../configuration/site_torrent.ini", true);

// Importation de la BDD
try {
	// Nouvel objet de base SQLite
	$bdd_handle = new PDO('sqlite:../bdd/db.sqlite');
	// Quelques options
	$bdd_handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	// Recherche utilisateur mot de passe
	$query = "SELECT * FROM taches ORDER BY timestamp_dernier_ratio";
	$requete = $bdd_handle->prepare($query);
	$requete->execute();
	$resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
	// On charge le nom d'utilisateur avec le id_membre
	$query = "SELECT utilisateur FROM membres WHERE id = ?";
	$requete = $bdd_handle->prepare($query);
	$requete->execute(array($resultat[0]['id_membre']));
	$resultat2 = $requete->fetchAll(PDO::FETCH_ASSOC);
	$utilisateur_phpratiomanager = $resultat2[0]['utilisateur'];
	
	// Si r�sultat positif on importe l'ancienne valeur du ratio
	if (!empty($resultat)) {
		addLog($utilisateur_phpratiomanager, "||| Lancement du script r�cup�ration ratio pour le site ".$resultat[0]['site']." |||", "non");
		include('../scraper/'.$site_torrent_ini[$resultat[0]['site']]['chemin scraper']);
		addLog($utilisateur_phpratiomanager, "Dernier ratio connu = ".$resultat[0]['dernier_ratio'], "non");
		// R�cup�ration du ratio
		addLog($utilisateur_phpratiomanager, "Tentative de r�cup�ration du ratio sur ".$resultat[0]['site'], "non");
		$scrape_ratio = scrape_ratio($resultat[0]['utilisateur'], $resultat[0]['mot_de_passe']);
		if ($scrape_ratio == FALSE) {
			addLog($utilisateur_phpratiomanager, "Impossible de r�cup�rer votre ratio.", "oui");
		}
		else {
			addLog($utilisateur_phpratiomanager, "Votre nouveau ratio = ".$scrape_ratio, "non");
			// On pr�pare la requ�te
			$requete = $bdd_handle->prepare('UPDATE taches SET dernier_ratio= ? ,timestamp_dernier_ratio= ? WHERE id= ?');
			// On l��x�cute.
			$maintenant = time();
			$requete->execute(array($scrape_ratio, $maintenant, $resultat[0]['id']));
			addLog($utilisateur_phpratiomanager, "||| Fin du script r�cup�ration ratio pour le site ".$resultat[0]['site']." |||", "non");
		}
	}
	else {
		echo "Aucun site de torrent configur�, fin du script.";
	}
	// On ferme la bdd
	$bdd_handle = NULL;
}
catch (Exception $e) {
	die('Erreur : '.$e->getMessage());
}
?>