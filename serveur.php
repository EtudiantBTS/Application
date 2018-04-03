<?php
include "fonctions.php";

// test si le paramètre "operation" est présent
if (isset($_REQUEST["operation"])) {

	if ($_REQUEST["operation"]=="enregistrer") {
		
		// récupération des données en post
		$lesdonnees = $_REQUEST["lesdonnees"] ;
		$donnee = json_decode($lesdonnees) ;
		$login = $donnee[0];
		$password = $donnee[1];
		$tabFrais = $donnee[2];
		$tabFraisHf = $donnee[3];

		print_r($donnee);
		// insertion dans la base de données
		try {
			print ("enregistrer%") ;
			$cnx = connexionPDO();
			//identification du visiteur
			$requete = "SELECT id from visiteur where login='".$login."' and mdp='".$password."'";
			$req = $cnx->prepare($requete);
			$req->execute();
			$idVisiteur = $req->fetch();
			$id = $idVisiteur[0];

			if ($id) {
				if (!empty($tabFrais)) {
					$annee = $tabFrais[0];
					$mois = $tabFrais[1];
					if (sizeof($mois)< 2) {
						$mois = '0'.$mois;
					}
					$date = $annee.$mois;
					$etape = $tabFrais[2];
					$km = $tabFrais[3];
					$nuitee = $tabFrais[4];
					$repas = $tabFrais[5];
					$data = array('ETP'=>$etape, 'KM'=>$km, 'NUI'=>$nuitee, 'REP'=>$repas);

					//on cree une nouvelle fiche de frais si aucune n existe pour ce mois
					$requete = "SELECT * from fichefrais where idvisiteur='".$id."' and mois='".$annee.$mois."'";
					$req = $cnx->prepare($requete);
					$req->execute();
					$fichefrais = $req->fetch();
					if ($fichefrais) {
						foreach ($data as $key => $value) {
							//on teste si la ligne existe deja
							$requete = "SELECT * from lignefraisforfait where idvisiteur='".$id."' and mois='".$annee.$mois."' and idfraisforfait='".$key."'";
							$req= $cnx->prepare($requete);
							$req->execute();
							$resultat = $req->fetch();
							if ($resultat) {
								$requete = 'UPDATE lignefraisforfait '
											.'SET quantite = :quantite '
											.' WHERE idvisiteur = :id '
											.'AND and mois = :mois '
											.'AND and idfraisforfait = :idfraisforfait';
								$req = $cnx->prepare($requete);
								$req->bindParam(':quantite', $value);
								$req->bindParam(':id', $id);
								$req->bindParam(':mois', $date);
								$req->bindParam(':idfraisforfait', $key);
								$req->execute();
							}else{
								$requete = "INSERT INTO lignefraisforfait(idvisiteur, mois, idfraisforfait, quantite) values('".$id."', '".$annee.$mois."', '".$key."', '".$value."')";
								$req = $cnx->prepare($requete);
								$req->execute();
							}
							print($requete);
							
						}
					}else{
						$requete = "INSERT INTO fichefrais(idvisiteur, mois) values ('".$id."', '".$annee.$mois."')";
						print_r($requete);
						$req = $cnx->prepare($requete);
						$req->execute();
						foreach ($data as $key => $value) {
							$requete = "INSERT INTO lignefraisforfait(idvisiteur, mois, idfraisforfait, quantite) values(".$id.", ".$annee.$mois.", ".$key.", ".$value." )";
							$req = $cnx->prepare($requete);
							$req->execute();
						}
					}

					
				}
			}else{
				print("Les identifiants ne sont pas corrects");
			}
			
		// capture d'erreur d'accès à la base de données
		} catch (PDOException $e) {
			print "Erreur !" . $e->getMessage();
			die();
		}

	}

}

?>