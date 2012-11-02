<?php
/*
	Transmission de l'upload et du download au tracker 
*/

// --------------------- Librairie --------------------------------
include(__DIR__."/librairies/torrent.php");
include(__DIR__."/librairies/ecrire_ini.php");

// --------------------- Configuration ----------------------------

// Importation de la configuration (stock�e dans le fichier configuration.ini)
$tableau_ini = parse_ini_file(__DIR__."/configuration/configuration.ini");

// Importation du scraper � charger
$site_torrent_ini = parse_ini_file(__DIR__."/configuration/site_torrent.ini", true);
if ($tableau_ini['site torrent'] == '') {  // Si aucun site de torrent priv� selectionn�
	addLog("Aucun site de torrent s�lectionn�, fin du script.");
	exit;
	}
include($site_torrent_ini[$tableau_ini['site torrent']]['chemin scraper']);

// --------------------- Fonctions --------------------------------

// Fonction d'�criture d'un log sur l'�cran et dans le fichier log.txt
function addLog($txt) {
	if (!file_exists(__DIR__."/log.txt")) file_put_contents(__DIR__."/log.txt", "");
	file_put_contents(__DIR__."/log.txt",date("[j/m/y H:i:s]")." - $txt \r\n".file_get_contents(__DIR__."/log.txt"));
	echo date("[j/m/y H:i:s]")." - $txt <br>";
 }

 // Fonction d'extraction des informations du torrent
 function extraction_torrent($chemin) {	
	// Cr�ation de l'objet torrent
	$torrent = new Torrent($chemin);
	
	// R�cup�ration du hash
	$torrents['hash'] = $torrent->hash_info();
	
	// R�cup�ration de announce (si plusieurs announce, conserver uniquement le premier)
	$announce_recupere = $torrent->announce();
	if (is_array($announce_recupere))
		$torrents['announce'] = $announce_recupere['0']['0'];
	Else
		$torrents['announce'] = $announce_recupere;
		
	return $torrents;
 }

// --------------------- Script --------------------------------------

addLog("Lancement du script"); 

// R�cup�ration du ratio
addLog("R�cup�ration du ratio sur ".$tableau_ini['site torrent']);
$scrape_ratio = scrape_ratio();
if ($scrape_ratio == FALSE) {
	addLog("Impossible de r�cup�rer votre ratio, fin du script.");
	exit;
	}
addLog("Votre ratio: ".$scrape_ratio);

// Ecriture du dernier ratio connu dans configuration.ini
$tableau_ini['dernier ratio connu'] = $scrape_ratio;
unlink(__DIR__."/configuration/configuration.ini");
$ini = new ini (__DIR__.'/configuration/configuration.ini', 'Configuration de php ratio manager'); // Utilisation de la class php ini
$ini->ajouter_array($tableau_ini);
$ini->ecrire();

// Tester si le ratio est inf�rieur au ratio minimum
if($scrape_ratio > $tableau_ini['ratio minimum']) {
	addLog("Votre ratio est sup�rieur au ratio minimum (".$tableau_ini['ratio minimum'].") , fin du script.");
	exit;
	}

// Tester si le fichier existe et s'il est lisible
if(is_readable($tableau_ini['chemin du torrent']))
    addLog("Chemin du fichier correct = ".$tableau_ini['chemin du torrent']);
else {
	addLog("Chemin du fichier incorrect ou illisible.");
	exit;
	}

// R�cup�ration des infos du tracker
$torrents = extraction_torrent($tableau_ini['chemin du torrent']);
addLog("Torrent Hash = ".$torrents['hash']." Announce = ".$torrents['announce']);
addLog("Announce = ".$torrents['announce']);

// Hash -> transformation en binaire et encodage en url
$torrents['hash_bin_url'] = urlencode( pack("H*" , $torrents['hash'] ) );
addLog("Hash en Binaire URL = ".$torrents['hash_bin_url']);

// Cr�ation d'un peer id en MD5 � l'aide de l'heure
$torrents['peer_id'] = $tableau_ini['user agent prefixe'].substr(md5(time().microtime()),0,12);
addLog("Identit�e du Peer en MD5 = ".$torrents['peer_id']);

// Communication au tracker du d�but de la connection
addLog("Envoi de vos stats au tracker");

// Envoi de la commande start
$commande_started = $torrents['announce'].'?info_hash='.$torrents['hash_bin_url'].'&peer_id='.$torrents['peer_id'].'&event=started';
addLog("Envoi de la commande started = ".$commande_started);
$ch = curl_init();
curl_setopt($ch,CURLOPT_FRESH_CONNECT, true); 
curl_setopt($ch,CURLOPT_TIMEOUT, 15); 
curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 15);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch,CURLOPT_USERAGENT,$tableau_ini['user agent']);
curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
curl_setopt($ch,CURLOPT_URL,$commande_started);
$reponse_serveur = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
addLog("Code http de retour = ".$http_code);
	
if ($http_code != 200) { // Code 200 = requ�te correctement �x�cut�e
	addLog("Echec pour joindre le tracker");
	Exit;
	}
Else {
	addLog("R�ponse = ".$reponse_serveur);
	}

// G�n�re une vitesse d'upload et de download entre les valeurs maxi et mini, regarde si quota d�pass� pour upload ou download
$uploaded = mt_rand($tableau_ini['valeur mini upload'] / 1000, $tableau_ini['valeur maxi upload'] / 1000) * 1000;
$downloaded = mt_rand($tableau_ini['valeur mini download'] / 1000, $tableau_ini['valeur maxi download'] / 1000) * 1000;

// Envoi de la commande Stopped
$commande_stopped = $torrents['announce'].'?info_hash='.$torrents['hash_bin_url'].'&peer_id='.$torrents['peer_id'].'&uploaded='.$uploaded.'&downloaded='.$downloaded.'&event=stopped';
addLog("Envoi de la commande stopped = ".$commande_stopped);
$ch = curl_init();
curl_setopt($ch,CURLOPT_FRESH_CONNECT, true); 
curl_setopt($ch,CURLOPT_TIMEOUT, 15); 
curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 15);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch,CURLOPT_USERAGENT,$tableau_ini['user agent']);
curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
curl_setopt($ch,CURLOPT_URL,$commande_stopped);
$reponse_serveur = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
	
addLog("Code http de retour = ".$http_code);
	
if ($http_code != 200) { // Code 200 = requ�te correctement �x�cut�e
	addLog("Echec pour joindre le tracker");
	Exit;
	}
Else {
	addLog("R�ponse = ".$reponse_serveur);
	}

addLog("Transmission de vos stats au tracker = OK!!!");
addLog("//----------------------------------------------------");
?>