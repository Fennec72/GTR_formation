<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/
$GLOBALS['formats'] = array(
	'pdf' => 'application/pdf',
	'doc' => 'application/msword',
	'txt' => 'text/plain',
	'odt' => 'application/msword',	
	'log' => 'text/plain'	
);


function formulaires_ecrire_auteur_charger_dist($id_auteur, $id_article, $mail){
    include_spip('inc/texte');
    $puce = definir_puce();
    $valeurs = array(
        'sujet_message_auteur'=>'',
        'texte_message_auteur'=>'',
        'email_message_auteur'=>$GLOBALS['visiteur_session']['email']
    );
    
    // id du formulaire (pour en avoir plusieurs sur une meme page)
    $valeurs['id'] = ($id_auteur ? '_'.$id_auteur : '_ar'.$id_article);
    // passer l'id_auteur au squelette
    $valeurs['id_auteur'] = $id_auteur;
    $valeurs['id_article'] = $id_article;
    $mail['editable'] = false;
    
    return $valeurs;
}

function formulaires_ecrire_auteur_verifier_dist($id_auteur, $id_article, $mail){
	$formats = $GLOBALS['formats'];
	$erreurs = array();
    include_spip('inc/filtres');
	
	//Connaitre les piéces jointes
	$_FILES ? $_FILES : $GLOBALS['HTTP_POST_FILES'];
	
	//Est ce qu'il y a une pièce jointe ?
	//if($_FILES['cv']['error'] == UPLOAD_ERR_NO_FILE)
		//$erreurs['cv'] .= _T("info_obligatoire");
	//Connaitre les piéces jointes
	//$_FILES ? $_FILES : $GLOBALS['HTTP_POST_FILES'];
	//Test de la taille
	if($_FILES['cv']['error'] == UPLOAD_ERR_FORM_SIZE || $_FILES['cv']['size'] > 2097152)
		$erreurs['cv'] .= "La taille de votre CV excéde la taille autorisée";
		
	//Test des extensions
	$fichier = pathinfo($_FILES['cv']['name']);
	if (!in_array($fichier['extension'],array_keys($formats))) 
		$erreurs['cv'] .= "Le fichier de votre CV n'a pas un format accepté";
	
	if (!$adres = _request('email_message_auteur'))
        $erreurs['email_message_auteur'] = _T("info_obligatoire");
    elseif(!email_valide($adres))
        $erreurs['email_message_auteur'] = _T('form_prop_indiquer_email');
    else {
        include_spip('inc/session');
        session_set('email', $adres);
    }

    if (!$sujet=_request('sujet_message_auteur'))
        $erreurs['sujet_message_auteur'] = _T("info_obligatoire");
    elseif(!(strlen($sujet)>3))
        $erreurs['sujet_message_auteur'] = _T('forum_attention_trois_caracteres');

    if (!$texte=_request('texte_message_auteur'))
        $erreurs['texte_message_auteur'] = _T("info_obligatoire");
    elseif(!(strlen($texte)>10))
        $erreurs['texte_message_auteur'] = _T('forum_attention_dix_caracteres');

    if (!_request('confirmer') AND !count($erreurs))
        $erreurs['previsu']=' ';
    return $erreurs;
}

function formulaires_ecrire_auteur_traiter_dist($id_auteur, $id_article, $mail){
	$formats = $GLOBALS['formats'];
	
	$envoyer_mail = charger_fonction('envoyer_mail','inc');
	$adres = _request('email_message_auteur');
    $sujet=_request('sujet_message_auteur');
    $texte=_request('texte_message_auteur');
	
	//Déclarer un mail en partie multiple
	$boundary .= "piecejointe";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

	//Connaitre les piéces jointes
	$_FILES ? $_FILES : $GLOBALS['HTTP_POST_FILES'];

	//Connaitre l'extension
	$fichier = pathinfo($_FILES['cv']['name']);
	$extension = $fichier['extension'];

	//Formater le fichier pour le mail
	$fichier=file_get_contents($_FILES['cv']['tmp_name']);
	/* On utilise aussi chunk_split() qui organisera comme il faut l'encodage fait en base 64 pour se conformer aux standards */
	$fichier=chunk_split( base64_encode($fichier) );
	
	$message_mail.= "--" .$boundary. "\n";
	$message_mail.= "Content-Type: ".$formats[$extension]."; name=\"".$_FILES['cv']['name']."\"\n";
	$message_mail.= "Content-Transfer-Encoding: base64\n";
	$message_mail.= "Content-Disposition: attachment; filename=\"".$_FILES['cv']['name']."\"\r\n\n";
	$message_mail.= $fichier;
	
    
    $texte .= "\n\n-- "._T('envoi_via_le_site')." ".supprimer_tags(extraire_multi($GLOBALS['meta']['nom_site']))." (".$GLOBALS['meta']['adresse_site']."/) --\n";
    $envoyer_mail = charger_fonction('envoyer_mail','inc');

    if ($envoyer_mail($mail, $sujet, $texte, $adres,
    "X-Originating-IP: ".$GLOBALS['ip']))
        $message = _T('form_prop_message_envoye');
    else
        $message = _T('pass_erreur_probleme_technique');

    return array('message_ok'=>$message);
}

?>
