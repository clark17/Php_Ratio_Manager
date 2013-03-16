<?php
function liste_site_torrent () 
{
	$site_torrent_ini = parse_ini_file("configuration/site_torrent.ini", true);
	foreach($site_torrent_ini as $clef => $element) {
		echo $clef;
		echo "<option value=\"$clef\">$clef</option>";
	}
}

// Liste les client torrent possible
function liste_client_torrent()
{
	$client_ini = parse_ini_file("configuration/client.ini", true);
	echo $client_ini;
	foreach($client_ini as $clef => $element) {
		echo $clef;
		echo "<option value=\"$clef\">$clef</option>";
	}
}

// Liste les type de connection
function liste_type_connection()
{
	$type_connection_ini = parse_ini_file("configuration/type_connection.ini", true);
	foreach($type_connection_ini as $clef => $element) {
		echo $clef;
		echo "<option value=\"$clef\">$clef</option>";
		}
}

// Fonction liste des trackers d�j� configur�s
function liste_taches() {
	// Connexion � la BDD
	try {
		// Nouvel objet de base SQLite
		$bdd_handle = new PDO('sqlite:bdd/db.sqlite');
		// Quelques options
		$bdd_handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		// Recherche utilisateur mot de passe
		$query = "SELECT * FROM taches WHERE id_membre=?";
		$requete = $bdd_handle->prepare($query);
		$requete->execute(array($_SESSION['id']));
		// On change la r�ponse SQL en r�ponse PHP.
		$resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
		// Si r�sultat positif on importe l'adresse mail et l'id du membre
		if (!empty($resultat)) {
			echo "Voici la liste de vos t�ches:<br/>";
			$site_torrent_ini = parse_ini_file('configuration/site_torrent.ini', true);
			echo '<table border="1">';
			echo "<tr><td>Logo</td></td><td>Site</td><td>utilisateur</td><td>mot de passe</td><td>Ratio actuel</td><td>Ratio minimum</td><td>Client</td><td>Type connection</td><td>Derni�re Mise � jour</td><td>Supprimer</td></tr>";
			foreach ($resultat as $clef => $element) {
				$id_tache = $element['id'];
				$site_torrent = $element['site'];
				$logo_site_torrent = $site_torrent_ini[$site_torrent]['logo'];
				$identifiant = $element['utilisateur'];
				$mot_de_passe = $element['mot_de_passe'];
				$dernier_ratio = $element['dernier_ratio'];
				$ratio_minimum = $element['ratio_minimum'];
				$user_agent = $element['user_agent'];
				$type_connection = $element['type_connection'];
				$timestamp_dernier_upload = date('d/m/Y H:i:s', $element['timestamp_dernier_upload']);
				echo "<tr>";
				echo '<td><img src="scraper/'.$logo_site_torrent.'" alt="Php Ratio Manager"></td>'."<td>$site_torrent</td><td>$identifiant</td><td>$mot_de_passe</td><td>$dernier_ratio</td>";
				echo "<td>$ratio_minimum</td><td>$user_agent</td><td>$type_connection</td><td>$timestamp_dernier_upload</td><td><a href='index.php?page=taches&action=supprimer_tache&id_tache=$id_tache&site_torrent=$site_torrent'>X</a></td>";
				echo "</tr>";
			}
			echo "</table>";
		}
		else {
			echo "Actuellement vous n'avez configur� aucune t�che, vous pouvez en ajouter une � l'aide du menu T�ches / Ajouter.";
		}
		// On ferme la bdd
		$bdd_handle = NULL;
	
	} catch (Exception $e) {
		die('Erreur : '.$e->getMessage());
	}
}

// Fonction pour ajouter une tache
function ajouter_tache() {
	include ('themes/ajouter_tache.html');
}

// Fonction pour ajouter une tache et informer de sa cr�ation
function tache_ajoutee() {
	// Importation des Librairies
	include("librairies/ini.php");
	include("librairies/torrent.php");
	
	// Importation des Configuration
	$site_torrent_ini = parse_ini_file("configuration/site_torrent.ini", true);
	
	// Test pour v�rifier si une tache pour le site de torrent est d�j� pr�sente
	echo 'utilisateurs/'.$_SESSION['utilisateur'].'/'.$_POST['site_torrent'].'.ini';
	if (is_readable('utilisateurs/'.$_SESSION['utilisateur'].'/'.$_POST['site_torrent'].'.ini')) {
		echo "<br>Impossible d'ajouter une tache pour ".$_POST['site_torrent']." Une seul t�che par site de torrent autoris�e, supprim� d'abord l'ancienne.";
	}
	else {
		// Test de r�cup�ration du ratio
		include('scraper/'.$site_torrent_ini[$_POST['site_torrent']]['chemin scraper']);
		$scrape_ratio = scrape_ratio($_POST['utilisateur'], $_POST['mot_de_passe']);
		if ($scrape_ratio == FALSE) {
			echo 'Impossible de communiquer avec ';
			echo $_POST['site_torrent'];
		}
		else {
			echo "Communication avec ".$_POST['site_torrent']." -> OK";
			// T�l�chargement d'un torrent
			$telechargement_torrent = telechargement_torrent($_SESSION['utilisateur'],$_POST['utilisateur'], $_POST['mot_de_passe']);
			if ($telechargement_torrent == FALSE) {
				echo '<br>Impossible de t�l�charger un torrent avec ';
				echo $_POST['site_torrent'];
			}
			else {
				echo "<br>T�l�chargement d'un torrent sur ".$_POST['site_torrent']." -> OK";
				// Insertion dans la bdd de la t�che
				try {
					// Nouvel objet de base SQLite
					$bdd_handle = new PDO('sqlite:bdd/db.sqlite');
					$bdd_handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$query = "INSERT INTO taches(timestamp_dernier_ratio, timestamp_dernier_upload, id_membre, site, utilisateur, mot_de_passe, dernier_ratio, ratio_minimum, user_agent, type_connection) VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )";
					$requete = $bdd_handle->prepare($query);
					$requete->execute(array(time(), time(), $_SESSION['id'], $_POST['site_torrent'], $_POST['utilisateur'], $_POST['mot_de_passe'], $scrape_ratio, $_POST['ratio_minimum'], $_POST['user_agent'], $_POST['type_connection']));
					echo "<br>Enregistrement de votre t�che -> OK";
					// On ferme la bdd
					$bdd_handle = NULL;
						
				} catch (Exception $e) {
					die('<br>Enregistrement de votre t�che -> Erreur: '.$e->getMessage());
				}
			}
		}
	}

}

// Suppression d'une tache
function suppression_tache () {
	global $_POST;
	try {
		// Nouvel objet de base SQLite
		$bdd_handle = new PDO('sqlite:bdd/db.sqlite');
		// Quelques options
		$bdd_handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		// Recherche utilisateur mot de passe
		$query = "DELETE FROM taches WHERE id=?";
		$requete = $bdd_handle->prepare($query);
		$requete->execute(array($_GET['id_tache']));
		// On ferme la bdd
		$bdd_handle = NULL;
		// Suppression du fichier "site_torrent".ini correspondant
		unlink('utilisateurs/'.$_SESSION['utilisateur'].'/'.$_GET['site_torrent'].'.ini');
		echo "T�che supprim�e avec succ�s.";
	
	} catch (Exception $e) {
		die('Erreur : '.$e->getMessage());
	}
}

// R�cup�ration de la variable $action GET ou POST
if (!empty($_GET['action'])) $action = $_GET['action'];
if (!empty($_POST['action'])) $action = $_POST['action'];

// Liste les commandes disponibles
if (!empty($action)) {
	if ($action == "liste_taches") { liste_taches(); }
	elseif ($action == "ajouter_tache") { ajouter_tache(); }
	elseif ($action == "tache_ajoutee") { tache_ajoutee(); }
	elseif ($action == "supprimer_tache") { suppression_tache(); }
	else { echo "Mauvaise commande."; }
}
else {
	echo "Aucune commande dans l'URL.";
}
?>