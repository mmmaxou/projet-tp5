<?php
session_start();
include("../divers/connexion.php");
include("../divers/balises.php");
include("../divers/agoTimeFormat.php");
date_default_timezone_set("Europe/Paris");

if(!isset($_SESSION['id'])) {
    // On n'est pas connecté, il faut retourner à la pgae de login
    header("Location:login.php");
}

include("entete.php");

if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // On n a pas donné le numéro de l'id de la personne dont on veut afficher le mur.
    // On affiche un message et on meurt
    echo "Bizarre !!!!";die(1);
}

// On affiche le menu
include("menu.php");

// On veut affchier notre mur ou celui d'un de nos amis et pas faire n'importe quoi 
echo '<div class="mur">';

$sql = "SELECT * FROM utilisateur WHERE id=?";
$query = $pdo->prepare($sql);
$query -> execute(array($_GET['id']));
$line = $query->fetch();

echo "<h1>Mur de ".$line['login']."</h1>";

$ok = false;
$etat = null;
if($_GET['id']==$_SESSION['id']) {
    $ok = true; // C notre mur, pas de soucis
} else {
    // Verifions si on est amis avec cette personne
    $sql = "SELECT * FROM lien WHERE etat='ami' 
		AND ((idUtilisateur1=? AND idUtilisateur2=?) OR (idUtilisateur1=? AND idUtilisateur2=?))";

    // les deux ids à tester sont : $_GET['id'] et $_SESSION['id']		
    // A completer. Il faut récupérer une ligne, si il y en a pas ca veut dire que l'on est pas ami avec cette personne

    $query=$pdo->prepare($sql);
    $query->execute( array($_GET['id'], $_SESSION['id'], $_SESSION['id'], $_GET['id']));

    $line = $query->fetch();

    if ( $line['etat'] != "ami" ) {
        $ok = false;
    } else {
        echo 'Vous etes amis';
        $ok = true;
    }

}
if($ok == false) {
    echo "Vous n'êtes pas encore ami, vous ne pouvez voir son mur !!<br/>";

    $sql = "SELECT * FROM lien WHERE (idUtilisateur1=? AND idUtilisateur2=?) OR (idUtilisateur1=? AND idUtilisateur2=?)";
    $query = $pdo->prepare($sql);
    $query -> execute(array($_GET['id'], $_SESSION['id'], $_SESSION['id'], $_GET['id']));

    $line = $query->fetch();
    $etat = $line['etat'];


    if ($etat == "attente") {
        echo "Invitation deja envoyée";
    } else if ($etat == "banni") {
        echo "L'utilisateur vous a banni";
    } else {
        echo "<a href='../traitement/demanderamitie.php?id=".$_GET['id']."'><input type='button' value='Demander en Ami'></a>";
    }	

    die(1);
}


// Tout va bien, il maintenant possible d'afficher le mur en question.
// A completer
// Requête de sélection des éléments d'un mur
// SELECT * FROM ecrit WHERE idAmi=?
// le paramètre  est le $_GET['id']

//Formulaire de post
echo "<div class='message'>";

echo "<form action='../traitement/ecrire.php' method='POST' enctype='multipart/form-data'>
        <p>Poster un message : </p>
        <input type='text' name='titre' value='Titre'>
        <textarea name='statut' placeholder='Votre message ...' ></textarea><br/>
        <input type='file' id='hiddenfile' name='imageStatut' style='display:none' onchange='getfile()'>
        <input type='button' value='Ajouter une image' onclick='getfile()' />
        <p id='demo'></p>
        <input type='submit' value='Poster'>
        <input type='hidden' name='id' value=".$_GET['id'].">
      </form>";
echo "</div>";


//Affichage des post
echo "<div class='ecrit'>";

$sql = "SELECT * FROM ecrit WHERE idAmi=?";
$query=$pdo->prepare($sql);
$query->execute(array($_GET['id']));

while ($line = $query-> fetch() ) {

    //    renderArray($line);

    $sql = "SELECT * FROM utilisateur WHERE id=?";
    $q = $pdo->prepare($sql);
    $q->execute(array($line["idAuteur"]));


    while ( $auteur = $q->fetch()) {

        afficherPost( $line, $auteur );

    } 

}

function afficherPost( $data, $auteur) {
    echo "<div class='well' id='well".$data['id']."'>";

    echo "<h3>".htmlspecialchars($data["titre"])."</h3><br/>";
    echo "<p class='texte'>".htmlspecialchars($data["contenu"])."</p>";
    
    if (isset($data['image'])) {
        echo "<img src='../uploads/".$data['image']."' ><br/>";
    }
    
    echo "<div class='hr'><hr /></div>";
    
     $time_added =$data['dateEcrit']; 
     // $notifies['date_time'] some sql datebase time
    echo $converted_time = AgoTimeFormat::makeAgo(strtotime($time_added)); 
    echo "<p class='sous-texte'>Ecrit par <a href='#id=?'>zaedazd</a>";
    echo "<a href=''>Heyo</a>";

//    echo lien("../traitement/editer.php?id=".$data['id'],"E | Editer",array('class'=>'gestion', 'data-toggle'=>'modal', 'data-target'=>'#texte-modal'));
    echo lien("","E | Editer",array('class'=>'gestion', 'data-toggle'=>'modal','id'=>$data['id'],'onClick'=>'editer(this)'));
    echo lien("../traitement/effacer.php?id=".$data['id'],"X | Supprimer",array('class'=>'gestion'));

    echo "</p>";
    echo "</div>";
    
    echo '
<div id="texte-modal" class="modal fade" role="dialog">
<div class="modal-dialog">

<!-- Modal content-->
<div class="modal-content">
     <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Editer un post</h4>
      </div>
      <form action="../traitement/editer.php" method="post">
      <div class="modal-body">
        <input type="text" name="titre">
        <textarea name="statut" style="resize: vertical;"></textarea><br/>
        <label>Supprimer l\'image </label>
        <input type="checkbox" name="del" value="del">
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-default" value="edit">Modifier</button>
        <input type="hidden" name="id-well">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
      </form>
</div>

</div>
</div>';

}

echo '</div>';
echo '</div>';
// On termine par le pied de page

include("pied.php");



?>