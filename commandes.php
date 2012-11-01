<?php
/*
	Fichier pour �x�cuter les commandes
*/

// Importation des librairies
include("librairies/ecrire_ini.php");

// Fonction pour remettre les param�tres par d�faut
function remise_configuration_origine() {
	unlink("configuration/configuration.ini");
	copy("configuration/configuration.ini.defaut", "configuration/configuration.ini");
	echo "Remise des param�tres par d�faut";
	echo "<br><a href='index.php'>Retour</a>";
 }

// Fonction pour sauvegarder la configuration dans configuration.ini
function sauvegarde_configuration_ini() {
	$configuration_ini = parse_ini_file("configuration/configuration.ini");
	$client_ini = parse_ini_file("configuration/client.ini", true);
	$type_connection_ini = parse_ini_file("configuration/type_connection.ini", true);
	$site_torrent_ini = parse_ini_file("configuration/site_torrent.ini", true);
	if (empty($_POST['torrent'])) $_POST['torrent'] = ""; // V�rification si pas de fichier torrent
	$configuration_ini['chemin du torrent'] = $_POST['torrent'];
	$configuration_ini['user agent'] = $client_ini[$_POST['user_agent']]['user agent'];
	$configuration_ini['type connection'] = $_POST['type_connection'];
	$configuration_ini['valeur maxi upload'] = $type_connection_ini[$_POST['type_connection']]['valeur maxi upload'];
	$configuration_ini['valeur mini upload'] = $type_connection_ini[$_POST['type_connection']]['valeur mini upload'];
	$configuration_ini['valeur maxi download'] = $type_connection_ini[$_POST['type_connection']]['valeur maxi download'];
	$configuration_ini['valeur mini download'] = $type_connection_ini[$_POST['type_connection']]['valeur mini download'];
	$configuration_ini['site torrent'] = $_POST['site_torrent'];
	$configuration_ini['utilisateur site torrent'] = $site_torrent_ini[$_POST['site_torrent']]['utilisateur'];
	$configuration_ini['mot de passe site torrent'] = $site_torrent_ini[$_POST['site_torrent']]['mot de passe'];
	$configuration_ini['ratio minimum'] = $_POST['ratio_minimum'];

	unlink("configuration/configuration.ini");
	
	$ini = new ini ('configuration/configuration.ini', 'Configuration de php ratio manager'); // Utilisation de la class php ini
	$ini->ajouter_array($configuration_ini);
	$ini->ecrire();
	
	// Condition pour la demande des idenfifiants de connexion au site
	if (isset($_POST['configurer_site_torrent']) or $site_torrent_ini[$_POST['site_torrent']]['utilisateur'] == "" or $site_torrent_ini[$_POST['site_torrent']]['mot de passe'] == "") {
		echo "Configuration sauvegard�e, merci de rentrer vos identifiants pour le site: <br>";
		echo '
		<form method="post" action="commandes.php">
		<fieldset>
			<legend>'.$_POST['site_torrent'].'</legend>
			<label for="utilisateur">Utilisateur :</label>
			<input type="text" name="utilisateur" id="utilisateur" title="nom d\'utilisateur" size="15" maxlength="50" value="'.$site_torrent_ini[$_POST['site_torrent']]['utilisateur'].'" required/><br>
			<label for="mot_de_passe">Mot de passe :</label>
			<input type="password" name="mot de passe" id="mot_de_passe" title="Mot de passe" size="15" maxlength="50" value="'.$site_torrent_ini[$_POST['site_torrent']]['mot de passe'].'" required/><br>
			<input type="hidden" name="site_torrent" value="'.$_POST['site_torrent'].'">
			<input type="hidden" name="action" value="configuration_site_torrent">
			<input type="submit" value="Appliquer" />
		</fieldset>
		</form>';
	}
	else {
		echo "Configuration sauvegard�e";
		echo "<br><a href='index.php'>Retour</a>";
	}
 }

// Fonction pour visionner le log
function voir_log() {
	if (!file_exists("log.txt")) {
		echo "Fichier log vide";
		echo "<br><a href='index.php'>Retour</a>";
		Exit; 
	}
	$contenu_log = file_get_contents('log.txt');
	echo '<textarea rows="20" cols="100">' . $contenu_log . '</textarea>';
	echo "<br><a href='index.php'>Retour</a>";
 }

// Fonction d'�ffacement du log
function efface_log()
 {
	if (!file_exists("log.txt")) {
		echo "Fichier log d�j� vide";
		echo "<br><a href='index.php'>Retour</a>";
		Exit; }
	unlink("log.txt");
	echo "Fichier log �ffac�";
	echo "<br><a href='index.php'>Retour</a>";
 }

// Fonction enregistrement des identifiants du site de torrent priv�
function configuration_site_torrent()
{
	$site_torrent_ini = parse_ini_file("configuration/site_torrent.ini", true);
	$site_torrent_ini[$_POST['site_torrent']]['utilisateur'] = $_POST['utilisateur'];
	$site_torrent_ini[$_POST['site_torrent']]['mot de passe'] = $_POST['mot_de_passe'];
	
	unlink("configuration/site_torrent.ini");
	
	$ini = new ini ('configuration/site_torrent.ini', 'Configuration des differents sites de torrent prive'); // Utilisation de la class php ini
	$ini->ajouter_array($site_torrent_ini);
	$ini->ecrire();
	
	echo "Enregistrement de vos informations de connexion �ffectu�es.";
	echo "<br><a href='index.php'>Retour</a>";
}
 
 // Si commande vide
if (empty($_POST['action'])) {
	echo "Aucune commande dans l'URL";
	echo "<br><a href='index.php'>Retour</a>";
	Exit; }
	
// Liste les commandes disponibles
switch ($_POST['action'])
 { 
    case "remise_configuration_origine";
		remise_configuration_origine();
	break;
	
	case "sauvegarde_configuration_ini":
		sauvegarde_configuration_ini();
	break;
	
	case "efface_log":
        efface_log();
    break;
    
	case "voir_log":
		voir_log();
	break;
	
	case "configuration_site_torrent":
		configuration_site_torrent();
	break;
	
    default:
        echo "Commande inconnu";
		echo "<br><a href='index.php'>Retour</a>";
 }
 
?>