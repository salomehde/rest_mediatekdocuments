<?php
include_once("ConnexionPDO.php");

/**
 * Classe de construction des requêtes SQL à envoyer à la BDD
 */
class AccessBDD {
	
    public $login="root";
    public $mdp="";
    public $bd="mediatek86";
    public $serveur="localhost";
    public $port="3306";	
    public $conn = null;

    /**
     * constructeur : demande de connexion à la BDD
     */
    public function __construct(){
        try{
            $this->conn = new ConnexionPDO($this->login, $this->mdp, $this->bd, $this->serveur, $this->port);
        }catch(Exception $e){
            throw $e;
        }
    }

    /**
     * récupération de toutes les lignes d'une table
     * @param string $table nom de la table
     * @return lignes de la requete
     */
    public function selectAll($table){
        if($this->conn != null){
            switch ($table) {
                case "livre" :
                    return $this->selectAllLivres();
                case "dvd" :
                    return $this->selectAllDvd();
                case "revue" :
                    return $this->selectAllRevues();
                case "exemplaire" :
                    return $this->selectAllExemplairesRevue();
                case "commandedocument" :
                    return $this->selectAllCommandesDocument();
                case "finabonnement" :
                    return $this->selectAllFinAbonnement();
                default:
                    // cas d'un select portant sur une table simple, avec tri sur le libellé
                    return $this->selectAllTableSimple($table);
            }			
        }else{
            return null;
        }
    }

    /**
     * récupération d'une ligne d'une table
     * @param string $table nom de la table
     * @param string $id id de la ligne à récupérer
     * @return ligne de la requete correspondant à l'id
     */	
    public function selectOne($table, $id){
        if($this->conn != null){
            switch($table){
                case "exemplaire" :
                    return $this->selectAllExemplairesRevue($id);
                case "commandedocument" :
                    return $this->selectAllCommandesDocument($id);
                case "abonnement" :
                    return $this->selectAllAbonnements($id);
                case "utilisateur" :
                    return $this->selectUtilisateur($id);
                default:
                    // cas d'un select portant sur une table simple			
                    $param = array(
                        "id" => $id
                    );
                    return $this->conn->query("select * from $table where id=:id;", $param);					
            }				
        }else{
                return null;
        }
    }

    /**
     * récupération de toutes les lignes de d'une table simple (sans jointure) avec tri sur le libellé
     * @param type $table
     * @return lignes de la requete
     */
    public function selectAllTableSimple($table){
        $req = "select * from $table order by libelle;";		
        return $this->conn->query($req);		
    }

    /**
     * récupération de toutes les lignes de la table Livre et les tables associées
     * @return lignes de la requete
     */
    public function selectAllLivres(){
        $req = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $req .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $req .= "from livre l join document d on l.id=d.id ";
        $req .= "join genre g on g.id=d.idGenre ";
        $req .= "join public p on p.id=d.idPublic ";
        $req .= "join rayon r on r.id=d.idRayon ";
        $req .= "order by titre ";		
        return $this->conn->query($req);
    }	

    /**
     * récupération de toutes les lignes de la table DVD et les tables associées
     * @return lignes de la requete
     */
    public function selectAllDvd(){
        $req = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $req .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $req .= "from dvd l join document d on l.id=d.id ";
        $req .= "join genre g on g.id=d.idGenre ";
        $req .= "join public p on p.id=d.idPublic ";
        $req .= "join rayon r on r.id=d.idRayon ";
        $req .= "order by titre ";	
        return $this->conn->query($req);
    }	

    /**
     * récupération de toutes les lignes de la table Revue et les tables associées
     * @return lignes de la requete
     */
    public function selectAllRevues(){
        $req = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $req .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $req .= "from revue l join document d on l.id=d.id ";
        $req .= "join genre g on g.id=d.idGenre ";
        $req .= "join public p on p.id=d.idPublic ";
        $req .= "join rayon r on r.id=d.idRayon ";
        $req .= "order by titre ";
        return $this->conn->query($req);
    }
    
     /**
     * récupération de toutes les abonnements se terminant dans moins de 30 jours
     * @return lignes de la requete
     */
    public function selectAllFinAbonnement(){
        $req ="Select a.dateFinAbonnement, a.idRevue, d.titre as RevueTitre ";
        $req .="FROM abonnement a ";
        $req .="JOIN revue r ON a.idRevue = r.id ";
	$req .="JOIN document d ON r.id = d.id ";
	$req .="WHERE DATEDIFF(a.dateFinAbonnement, CURRENT_TIMESTAMP) BETWEEN 0 AND 30 ";
	$req .="ORDER BY a.dateFinAbonnement ASC; ";
        return $this->conn->query($req);
    }
                
    /**
     * récupération de tous les exemplaires d'une revue
     * @param string $id id de la revue
     * @return lignes de la requete
     */
    public function selectAllExemplairesRevue($id){
        $param = array(
                "id" => $id
        );
        $req = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $req .= "from exemplaire e join document d on e.id=d.id ";
        $req .= "where e.id = :id ";
        $req .= "order by e.dateAchat DESC";		
        return $this->conn->query($req, $param);
    }		
    
    /**
     * récupération de toutes les commandes d'un livre ou dvd
     * @param string $id id de la commande
     * @return lignes de la requete
     */
    public function selectAllCommandesDocument($id)
    {
        $param = array(
                "idDocument" => $id
        );
        $req = "Select c.id, c.dateCommande, c.montant, cd.nbExemplaire, cd.idSuivi, cd.idLivreDvd, s.libelle as libelleSuivi ";
        $req .= "from commande c ";
        $req .= "join commandedocument cd on c.id=cd.id ";
        $req .= "join suivi s on cd.idSuivi=s.id ";
        $req .= "join livres_dvd ld on cd.idLivreDvd=ld.id ";
        $req .= "where cd.id=c.id and ld.id = :idDocument ";
        $req .= "order by c.dateCommande DESC";
        return $this->conn->query($req, $param);
    }
    
    /**
     * récupération de tout les abonnements d'une revue
     * @param string $id id de l'abonnement de la revue
     * @return lignes de la requete
     */
    public function selectAllAbonnements($id){
        $param = array(
                "idDocument" => $id
        );
        $req = "Select c.id, c.dateCommande, c.montant, a.dateFinAbonnement, a.idRevue ";
        $req .= "from commande c ";
        $req .= "join abonnement a on c.id=a.id ";
        $req .= "where a.idRevue= :idDocument ";
        $req .= "order by c.dateCommande DESC";
        return $this->conn->query($req, $param);
    }
    
    /**
     * récupération d'un des utilisateurs
     * @param string $id id de l'utilisateur
     * @return lignes de la requete
     */
    public function selectUtilisateur($id){
        $param = array(
                "login" => $id
        );
        $req = "SELECT u.idService, u.login, u.password, s.libelle ";
    $req .= "FROM utilisateur u ";
    $req .= "JOIN service s ON u.idService=s.idService ";
    $req .= "WHERE u.login= :login ";
        return $this->conn->query($req, $param);
    }
    
    /**
     * suppression d'une ligne dans une table simple
     * @param string $table nom de la table
     * @param array $champs nom et valeur de chaque champs
     * @return true si la suppression a fonctionné
     */	
    public function deleteSimple($table, $champs){
         if ($this->conn != null) {
            // construction de la requête
            $requete = "delete from $table where ";
            foreach ($champs as $key => $value) {
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete) - 5);
            return $this->conn->execute($requete, $champs);
        } else {
            return null;
        }
    }
    
    /**
     * Suppression d'une ligne dans une table
     * @param string $table nom de la table
     * @param array $champs nom et valeur de chaque champs
     * @return true si la suppression a fonctionné
     */
    public function delete($table, $champs) {
        if ($this->conn != null && $champs != null) {
            switch ($table) {
                case "commandedocument" :
                    return $this->deleteCommandeDocument($champs);
                case "abonnement" :
                    return $this->deleteAbonnement($champs);
                default:
                    // cas d'un delete portant sur une table simple
                    return $this->deleteSimple($table, $champs);
            }
        }else{
            return null;
        }
    }
    
    /**
     * Suppression d'une commande document
     * @param type $champs nom et valeur de chaque champs
     * @return
     */
    public function deleteCommandeDocument($champs) {
        // tableau des données de commande
        $champsCommande = [
            "Id" => $champs["Id"],
            "DateCommande" => $champs["DateCommande"],
            "Montant" => $champs["Montant"]
        ];
        $resultCommande = $this->deleteSimple("commande", $champsCommande);

        // tableau des données commande document
        $champsCommandeDocument = [
            "Id" => $champs["Id"],
            "NbExemplaire" => $champs["NbExemplaire"],
            "IdLivreDvd" => $champs["IdLivreDvd"],
            "IdSuivi" => $champs["IdSuivi"],
            "libelleSuivi" => $champs["LibelleSuivi"]
        ];
        $resultCommandeDocument = $this->deleteSimple("commandedocument", $champsCommandeDocument);
        
        return $resultCommandeDocument && $resultCommande;
    }
    
    /**
     * Suppression d'une commande revue
     * @param type $champs nom et valeur de chaque champs
     * @return 
     */
    public function deleteAbonnement($champs) {
        // tableau associatif des données commande
         $champsCommande = [
            "Id" => $champs["Id"],
            "DateCommande" => $champs["DateCommande"],
            "Montant" => $champs["Montant"]
        ];
        $resultCommande = $this->deleteSimple("commande", $champsCommande);
        
         // tableau associatif des données abonnement
        $champsAbonnement = [
            "Id" => $champs["Id"],
            "DateFinAbonnement" => $champs["DateFinAbonnement"],
            "IdRevue" => $champs["IdRevue"]
        ];
        $resultAbonnement = $this->deleteSimple("abonnement", $champsAbonnement);
        
        return $resultCommande && $resultAbonnement;
    }
    
    /**
     * Insertion dans une table simple
     * @param type $table
     * @param type $champs
     * @return true si l'ajout a fonctionné
     */
    public function insertTableSimple($table, $champs) {
        if ($this->conn != null && $champs != null) {
            // construction de la requête
            $requete = "insert into $table (";
            foreach ($champs as $key => $value){
                $requete .= "$key,";
            }
            // (enlève la dernière virgule)
            $requete = substr($requete, 0, strlen($requete)-1);
            $requete .= ") values (";
            foreach ($champs as $key => $value){
                $requete .= ":$key,";
            }
            // (enlève la dernière virgule)
            $requete = substr($requete, 0, strlen($requete)-1);
            $requete .= ");";	
            return $this->conn->execute($requete, $champs);
        } else {
            return null;
        }
    }
    
    /**
     * ajout d'une ligne dans une table
     * @param string $table nom de la table
     * @param array $champs nom et valeur de chaque champs de la ligne
     * @return true si l'ajout a fonctionné
     */	
    public function insertOne($table, $champs){
        if($this->conn != null && $champs != null){
            switch ($table) {
                case "commandedocument" :
                    return $this->insertCommandeDocument($champs);
                case "abonnement" :
                    return $this->insertAbonnement($champs);
                default:
                    // cas d'un insert portant sur une table simple
                    return $this->insertTableSimple($table, $champs);
            }
        }else{
            return null;
        }
    }
    
    /**
     * Ajout d'une commande de type livre
     * @param type $champs non et valeur de chaque champs
     */
    public function insertCommandeDocument($champs) {
        // tableau des données de commande
        $champsCommande = [
            "Id" => $champs["Id"],
            "DateCommande" => $champs["DateCommande"],
            "Montant" => $champs["Montant"]
        ];
        $resultCommande = $this->insertTableSimple("commande", $champsCommande);

        // tableau des données commande document
        $champsCommandeDocument = [
            "Id" => $champs["Id"],
            "NbExemplaire" => $champs["NbExemplaire"],
            "IdLivreDvd" => $champs["IdLivreDvd"],
            "IdSuivi" => $champs["IdSuivi"],
            "libelleSuivi" => $champs["LibelleSuivi"]
        ];
        $resultCommandeDocument = $this->insertTableSimple("commandedocument", $champsCommandeDocument);

        return $resultCommande && $resultCommandeDocument;
    }
    
    /**
     * Ajout d'une commande de type revue
     * @param type $champs nom et valeur de chaque champs
     */
    public function insertAbonnement($champs) {
        // tableau des données commande
        $champsCommande = [
            "Id" => $champs["Id"],
            "DateCommande" => $champs["DateCommande"],
            "Montant" => $champs["Montant"]
        ];
        $resultCommande = $this->insertTableSimple("commande", $champsCommande);

        // tableau des données abonnement
        $champsAbonnement = [
            "Id" => $champs["Id"],
            "DateFinAbonnement" => $champs["DateFinAbonnement"],
            "IdRevue" => $champs["IdRevue"]
        ];
        $resultAbonnement = $this->insertTableSimple("abonnement", $champsAbonnement);

        return $resultCommande && $resultAbonnement;
    }
    
     /**
     * Modification d'une ligne dans une table simple
     * @param type $table nom de la table
     * @param type $id id de la ligne à modifier
     * @param type $champs nom et valeur de chaque champs
     * @return true si la modification a fonctionné
     */
    public function updateTableSimple($table, $id, $champs) {
        if ($this->conn != null && $champs != null) {
            // construction de la requête
            $requete = "update $table set ";
            foreach ($champs as $key => $value){
                $requete .= "$key=:$key,";
            }
            // (enlève la dernière virgule)
            $requete = substr($requete, 0, strlen($requete)-1);				
            $champs["Id"] = $id;
            $requete .= " where Id=:Id;";				
            return $this->conn->execute($requete, $champs);
        } else {
            return null;
        }
    }

    /**
     * modification d'une ligne dans une table
     * @param string $table nom de la table
     * @param string $id id de la ligne à modifier
     * @param type $champs nom et valeur de chaque champs de la ligne
     * @return true si la modification a fonctionné
     */	
    public function updateOne($table, $id, $champs){
        if($this->conn != null && $champs != null){
            switch ($table) {
                case "commandedocument" :
                    return $this->updateCommandeDocument($id, $champs);
                default:
                    // cas d'une update portant sur une table simple
                    return $this->updateTableSimple($table, $id, $champs);
            }
        }else{
            return null;
        }
    }
    
    /**
     * Modification d'une commande de livre ou dvd
     * @param type $id id de la commande à modifier
     * @param type $champs nom et valeur de chaque champs
     */
    public function updateCommandeDocument($id, $champs) {
        // tableau des données commande document
        $champsCommandeDocument = [
            "Id" => $champs["Id"],
            "NbExemplaire" => $champs["NbExemplaire"],
            "IdLivreDvd" => $champs["IdLivreDvd"],
            "IdSuivi" => $champs["IdSuivi"],
            "libelleSuivi" => $champs["LibelleSuivi"]
        ];
        $resultCommandeDocument = $this->updateTableSimple("commandedocument", $id, $champsCommandeDocument);
        // tableau des données commande
        $champsCommande = [
            "Id" => $champs["Id"],
            "DateCommande" => $champs["DateCommande"],
            "Montant" => $champs["Montant"]
        ];
        $resultCommande = $this->updateTableSimple("commande", $id, $champsCommande);
        return $resultCommandeDocument && $resultCommande;
    }

}