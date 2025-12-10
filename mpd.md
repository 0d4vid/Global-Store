Création de la base de données
CREATE DATABASE global_store;
USE global_store;


Création des tables 
Table Categorie
CREATE TABLE categorie (
    id_cat INT AUTO_INCREMENT,
    libelle VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_cat)
);


Table Produit
CREATE TABLE produit (
    id_prod INT AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    prix_vente DECIMAL(10,2) NOT NULL,
    prix_achat DECIMAL(10,2) NOT NULL,
    stock_actuel INT NOT NULL,
    seuil_alert INT NOT NULL,
    id_cat INT NOT NULL,
    PRIMARY KEY (id_prod),
    FOREIGN KEY (id_cat) REFERENCES categorie(id_cat)
);


Table Client
CREATE TABLE client (
    id_client INT AUTO_INCREMENT,
    nom_client VARCHAR(100) NOT NULL,
    telephone VARCHAR(20),
    point_fidelite INT DEFAULT 0,
    PRIMARY KEY (id_client)
);


Table Fournisseur
CREATE TABLE fournisseur (
    id_fourn INT AUTO_INCREMENT,
    nom_fourn VARCHAR(100) NOT NULL,
    contact VARCHAR(50),
    adresse VARCHAR(150),
    PRIMARY KEY (id_fourn)
);


Table Utilisateur
CREATE TABLE utilisateur (
    id_user INT AUTO_INCREMENT,
    nom_user VARCHAR(100) NOT NULL,
    prenom_user VARCHAR(100),
    mail_user VARCHAR(100) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin','vendeur','stock') NOT NULL,
    PRIMARY KEY (id_user)
);


Table Vente
CREATE TABLE vente (
    id_vente INT AUTO_INCREMENT,
    num_facture VARCHAR(50) NOT NULL,
    date_vente DATETIME NOT NULL,
    statut VARCHAR(30),
    id_client INT,
    id_user INT NOT NULL,
    PRIMARY KEY (id_vente),
    FOREIGN KEY (id_client) REFERENCES client(id_client),
    FOREIGN KEY (id_user) REFERENCES utilisateur(id_user)
);


Table Ligne_Vente
CREATE TABLE ligne_vente (
    id_vente INT,
    id_prod INT,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id_vente, id_prod),
    FOREIGN KEY (id_vente) REFERENCES vente(id_vente),
    FOREIGN KEY (id_prod) REFERENCES produit(id_prod)
);


Table Paiement
CREATE TABLE paiement (
    id_paiement INT AUTO_INCREMENT,
    montant DECIMAL(10,2) NOT NULL,
    mode_paiement ENUM('especes','carte','mobile') NOT NULL,
    id_vente INT NOT NULL,
    PRIMARY KEY (id_paiement),
    FOREIGN KEY (id_vente) REFERENCES vente(id_vente)
);


Table Reapprovisionnement
CREATE TABLE reapprovisionnement (
    id_reappro INT AUTO_INCREMENT,
    date_reappro DATE NOT NULL,
    qte_recue INT NOT NULL,
    id_prod INT NOT NULL,
    id_fourn INT NOT NULL,
    id_user INT NOT NULL,
    PRIMARY KEY (id_reappro),
    FOREIGN KEY (id_prod) REFERENCES produit(id_prod),
    FOREIGN KEY (id_fourn) REFERENCES fournisseur(id_fourn),
    FOREIGN KEY (id_user) REFERENCES utilisateur(id_user)
);


Table Session_Caisse
CREATE TABLE session_caisse (
    id_session INT AUTO_INCREMENT,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME,
    fond_initial DECIMAL(10,2) NOT NULL,
    total_cloture DECIMAL(10,2),
    id_user INT NOT NULL,
    PRIMARY KEY (id_session),
    FOREIGN KEY (id_user) REFERENCES utilisateur(id_user)
);

Procédures stockées MySQL

DELIMITER //
Ajouter une catégorie
CREATE PROCEDURE ajouter_categorie (
    IN p_libelle VARCHAR(100)
)
BEGIN
    INSERT INTO categorie(libelle)
    VALUES (p_libelle);
END //

Afficher toutes les catégories
CREATE PROCEDURE lister_categories ()
BEGIN
    SELECT * FROM categorie;
END //

Modifier une catégorie
CREATE PROCEDURE modifier_categorie (
    IN p_id_cat INT,
    IN p_libelle VARCHAR(100)
)
BEGIN
    UPDATE categorie
    SET libelle = p_libelle
    WHERE id_cat = p_id_cat;
END //

Supprimer une catégorie
CREATE PROCEDURE supprimer_categorie (
    IN p_id_cat INT
)
BEGIN
    DELETE FROM categorie
    WHERE id_cat = p_id_cat;
END //


Ajouter un produit
CREATE PROCEDURE ajouter_produit (
    IN p_nom VARCHAR(100),
    IN p_description TEXT,
    IN p_prix_vente DECIMAL(10,2),
    IN p_prix_achat DECIMAL(10,2),
    IN p_stock INT,
    IN p_seuil INT,
    IN p_id_cat INT
)
BEGIN
    INSERT INTO produit(nom, description, prix_vente, prix_achat, stock_actuel, seuil_alert, id_cat)
    VALUES (p_nom, p_description, p_prix_vente, p_prix_achat, p_stock, p_seuil, p_id_cat);
END //

Lister les produits avec catégorie
CREATE PROCEDURE lister_produits ()
BEGIN
    SELECT 
        p.id_prod,
        p.nom,
        p.prix_vente,
        p.stock_actuel,
        c.libelle AS categorie
    FROM produit p
    JOIN categorie c ON p.id_cat = c.id_cat;
END //

Modifier le stock d’un produit
CREATE PROCEDURE modifier_stock_produit (
    IN p_id_prod INT,
    IN p_stock INT
)
BEGIN
    UPDATE produit
    SET stock_actuel = p_stock
    WHERE id_prod = p_id_prod;
END //

Supprimer un produit
CREATE PROCEDURE supprimer_produit (
    IN p_id_prod INT
)
BEGIN
    DELETE FROM produit
    WHERE id_prod = p_id_prod;
END //


Ajouter un client
CREATE PROCEDURE ajouter_client (
    IN p_nom VARCHAR(100),
    IN p_tel VARCHAR(20)
)
BEGIN
    INSERT INTO client(nom_client, telephone)
    VALUES (p_nom, p_tel);
END //

Lister les clients
CREATE PROCEDURE lister_clients ()
BEGIN
    SELECT * FROM client;
END //


Ajouter un utilisateur
CREATE PROCEDURE ajouter_utilisateur (
    IN p_nom VARCHAR(100),
    IN p_prenom VARCHAR(100),
    IN p_mail VARCHAR(100),
    IN p_mdp VARCHAR(255),
    IN p_role VARCHAR(20)
)
BEGIN
    INSERT INTO utilisateur(nom_user, prenom_user, mail_user, mot_de_passe, role)
    VALUES (p_nom, p_prenom, p_mail, p_mdp, p_role);
END //

Lister les utilisateurs
CREATE PROCEDURE lister_utilisateurs ()
BEGIN
    SELECT id_user, nom_user, prenom_user, mail_user, role
    FROM utilisateur;
END //


Créer une vente
CREATE PROCEDURE creer_vente (
    IN p_facture VARCHAR(50),
    IN p_id_client INT,
    IN p_id_user INT
)
BEGIN
    INSERT INTO vente(num_facture, date_vente, statut, id_client, id_user)
    VALUES (p_facture, NOW(), 'EN_COURS', p_id_client, p_id_user);
END //

Ajouter une ligne de vente
CREATE PROCEDURE ajouter_ligne_vente (
    IN p_id_vente INT,
    IN p_id_prod INT,
    IN p_qte INT,
    IN p_prix DECIMAL(10,2)
)
BEGIN
    INSERT INTO ligne_vente(id_vente, id_prod, quantite, prix_unitaire)
    VALUES (p_id_vente, p_id_prod, p_qte, p_prix);

    UPDATE produit
    SET stock_actuel = stock_actuel - p_qte
    WHERE id_prod = p_id_prod;
END //


Enregistrer un paiement
CREATE PROCEDURE ajouter_paiement (
    IN p_id_vente INT,
    IN p_montant DECIMAL(10,2),
    IN p_mode VARCHAR(20)
)
BEGIN
    INSERT INTO paiement(id_vente, montant, mode_paiement)
    VALUES (p_id_vente, p_montant, p_mode);
END //


REAPPROVISIONNEMENT
CREATE PROCEDURE reapprovisionner_produit (
    IN p_id_prod INT,
    IN p_qte INT,
    IN p_id_fourn INT,
    IN p_id_user INT
)
BEGIN
    INSERT INTO reapprovisionnement(date_reappro, qte_recue, id_prod, id_fourn, id_user)
    VALUES (CURDATE(), p_qte, p_id_prod, p_id_fourn, p_id_user);

    UPDATE produit
    SET stock_actuel = stock_actuel + p_qte
    WHERE id_prod = p_id_prod;
END //


STATISTIQUES 
Chiffre d’affaires total
CREATE PROCEDURE chiffre_affaires_total ()
BEGIN
    SELECT SUM(montant) AS chiffre_affaires
    FROM paiement;
END //

Produits les plus vendus
CREATE PROCEDURE produits_plus_vendus ()
BEGIN
    SELECT 
        p.nom,
        SUM(lv.quantite) AS total_vendu
    FROM ligne_vente lv
    JOIN produit p ON lv.id_prod = p.id_prod
    GROUP BY p.nom
    ORDER BY total_vendu DESC;
END //

Clients les plus fidèles
CREATE PROCEDURE clients_fideles ()
BEGIN
    SELECT 
        c.nom_client,
        COUNT(v.id_vente) AS nombre_achats
    FROM client c
    JOIN vente v ON c.id_client = v.id_client
    GROUP BY c.nom_client;
END //


DELIMITER ;


Requêtes de test
Afficher tous les produits avec leur catégorie
SELECT 
    p.id_prod,
    p.nom,
    p.prix_vente,
    p.stock_actuel,
    c.libelle AS categorie
FROM produit p
JOIN categorie c ON p.id_cat = c.id_cat;


Afficher les ventes avec le nom du client et du vendeur
SELECT 
    v.num_facture,
    v.date_vente,
    v.statut,
    cl.nom_client,
    u.nom_user AS vendeur
FROM vente v
LEFT JOIN client cl ON v.id_client = cl.id_client
JOIN utilisateur u ON v.id_user = u.id_user;


Afficher le détail d’une vente (produits vendus)
SELECT 
    v.num_facture,
    p.nom AS produit,
    lv.quantite,
    lv.prix_unitaire,
    (lv.quantite * lv.prix_unitaire) AS montant
FROM ligne_vente lv
JOIN vente v ON lv.id_vente = v.id_vente
JOIN produit p ON lv.id_prod = p.id_prod;


Afficher les paiements avec le mode et la facture
SELECT 
    pa.id_paiement,
    pa.montant,
    pa.mode_paiement,
    v.num_facture
FROM paiement pa
JOIN vente v ON pa.id_vente = v.id_vente;


Afficher les réapprovisionnements avec produit, fournisseur et utilisateur
SELECT 
    r.date_reappro,
    r.qte_recue,
    p.nom AS produit,
    f.nom_fourn AS fournisseur,
    u.nom_user AS utilisateur
FROM reapprovisionnement r
JOIN produit p ON r.id_prod = p.id_prod
JOIN fournisseur f ON r.id_fourn = f.id_fourn
JOIN utilisateur u ON r.id_user = u.id_user;


Afficher les sessions de caisse par utilisateur
SELECT 
    sc.id_session,
    sc.date_debut,
    sc.date_fin,
    sc.fond_initial,
    sc.total_cloture,
    u.nom_user
FROM session_caisse sc
JOIN utilisateur u ON sc.id_user = u.id_user;



Remplissage de la base de donnees en appelant les procedures:

CATEGORIES
CALL ajouter_categorie('Alimentation');
CALL ajouter_categorie('Électronique');
CALL ajouter_categorie('Vêtements');
CALL ajouter_categorie('Cosmétiques');


UTILISATEURS (Admin, vendeur, stock)
CALL ajouter_utilisateur(
    'Ngassa',
    'Paul',
    'paul.ngassa@globalstore.cm',
    'admin123',
    'admin'
);

CALL ajouter_utilisateur(
    'Tchoumba',
    'Brenda',
    'brenda@globalstore.cm',
    'vendeur123',
    'vendeur'
);

CALL ajouter_utilisateur(
    'Etoa',
    'Junior',
    'junior@globalstore.cm',
    'stock123',
    'stock'
);


CLIENTS
CALL ajouter_client('Mbarga Alain', '699112233');
CALL ajouter_client('Fotso Mireille', '677445566');
CALL ajouter_client('Kamdem Yves', '655889900');


PRODUITS (PRIX EN FCFA)
CALL ajouter_produit(
    'Riz Royal Umbrella 25kg',
    'Sac de riz importé',
    17000,
    15000,
    20,
    5,
    1
);

CALL ajouter_produit(
    'Téléviseur LG 43 pouces',
    'Smart TV LED',
    250000,
    220000,
    5,
    1,
    2
);

CALL ajouter_produit(
    'T-shirt coton homme',
    'T-shirt taille L',
    5000,
    3000,
    30,
    5,
    3
);


FOURNISSEURS
INSERT INTO fournisseur(nom_fourn, contact, adresse)
VALUES 
('Société CERECAM', '699223344', 'Douala Akwa'),
('CamElectro SARL', '677334455', 'Yaoundé Centre'),
('Textile Bafoussam', '655667788', 'Bafoussam');


RÉAPPROVISIONNEMENT
CALL reapprovisionner_produit(
    1,
    10,
    1,
    3
);

CALL reapprovisionner_produit(
    2,
    3,
    2,
    3
);


OUVERTURE SESSION DE CAISSE
INSERT INTO session_caisse(date_debut, fond_initial, id_user)
VALUES (NOW(), 50000, 2);


CRÉATION D’UNE VENTE
CALL creer_vente(
    'FACT-001',
    1,
    2
);


LIGNES DE VENTE
CALL ajouter_ligne_vente(
    1,
    1,
    2,
    17000
);

CALL ajouter_ligne_vente(
    1,
    3,
    1,
    5000
);


PAIEMENTS (ESPECES + MOBILE)
CALL ajouter_paiement(1, 30000, 'especes');
CALL ajouter_paiement(1, 9000, 'mobile');


CONSULTATION DES DONNÉES
Produits + catégories
CALL lister_produits();

Clients
CALL lister_clients();

Utilisateurs
CALL lister_utilisateurs();


STATISTIQUES
Chiffre d’affaires
CALL chiffre_affaires_total();

Produits les plus vendus
CALL produits_plus_vendus();

Clients fidèles
CALL clients_fideles();

