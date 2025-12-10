C'est parti. Voici le fichier **`requirements.md`** complet et définitif. Il consolide toutes les informations de l'énoncé, de la structure technique définie (Stack PHP/Tailwind), et du fichier SQL (`MPD.docx`) que vous avez fourni.

Ce document est votre "Bible" pour le développement. Il fait le lien entre la base de données (Backend) et les pages Web (Frontend).

-----

# 📋 REQUIREMENTS.MD - Global Store Manager (SAD366)

## 1\. Présentation du Projet

**Nom du Projet :** Système de Gestion des Ventes "Global Store"
**Contexte :** Projet académique SAD366 - Administration des Bases de Données.
**Objectif :** Développer une application web robuste pour gérer les ventes, les stocks et les rapports d'un magasin, reposant sur une architecture de base de données optimisée (Procédures stockées).

-----

## 2\. Stack Technique

L'architecture choisie privilégie la rapidité de mise en place et la robustesse des transactions SQL.

  * **Serveur Local :** XAMPP / WAMP (Apache + MySQL).
  * **Langage Backend :** **PHP 8+** (Natif, sans framework backend).
  * **Base de Données :** **MySQL** (Usage intensif des procédures stockées et triggers).
  * **Frontend (Style) :** **Tailwind CSS** (via CDN).
  * **Composants UI :** **Flowbite** (via CDN pour les tableaux, modales, formulaires).
  * **Icônes :** **Heroicons** (SVG).
  * **Visualisation de données :** **Chart.js** (pour les graphiques du Dashboard).

-----

## 3\. Architecture de la Base de Données

Le développement de l'application doit strictement respecter le script SQL fourni (`MPD.docx`).

### [cite_start]3.1 Tables Principales [cite: 5, 7, 9, 11, 13, 15, 17, 19, 21, 23]

1.  **`categorie`** : Classification des produits.
2.  **`produit`** : Catalogue (Stock, Prix Achat/Vente, Seuil alerte).
3.  **`client`** : Gestion de la fidélité.
4.  **`fournisseur`** : Sources d'approvisionnement.
5.  **`utilisateur`** : Gestion des accès (Admin, Vendeur, Stock).
6.  **`vente`** : En-tête des factures.
7.  **`ligne_vente`** : Détail des produits vendus (Quantité, Prix unitaire figé).
8.  **`paiement`** : Suivi des encaissements (Espèces, Mobile, Carte).
9.  **`reapprovisionnement`** : Historique des entrées de stock.
10. **`session_caisse`** : Suivi des ouvertures/clotures de caisse par vendeur.

### 3.2 Procédures Stockées (API SQL)

L'application PHP **ne doit pas** faire de requêtes `INSERT` ou `UPDATE` directes complexes. Elle doit appeler les procédures stockées définies pour garantir l'intégrité.

  * [cite_start]**Vente :** `CALL creer_vente(...)` [cite: 61][cite_start], `CALL ajouter_ligne_vente(...)` [cite: 64] (gère la décrémentation stock auto)[cite_start], `CALL ajouter_paiement(...)`[cite: 68].
  * [cite_start]**Stock :** `CALL reapprovisionner_produit(...)` [cite: 71] (gère l'incrémentation stock auto)[cite_start], `CALL modifier_stock_produit(...)`[cite: 44].
  * [cite_start]**CRUD :** `CALL ajouter_produit(...)` [cite: 38][cite_start], `CALL ajouter_client(...)` [cite: 50][cite_start], `CALL ajouter_utilisateur(...)`[cite: 55].
  * [cite_start]**Stats :** `CALL chiffre_affaires_total()` [cite: 76][cite_start], `CALL produits_plus_vendus()` [cite: 79][cite_start], `CALL clients_fideles()`[cite: 82].

-----

## 4\. Structure des Dossiers & Fichiers

Organisation stricte pour le travail en groupe.

```text
/global_store
│
├── /assets
│   ├── /css (style custom si besoin)
│   ├── /js (scripts pour Chart.js ou AJAX vente)
│   └── /img (logo)
│
├── /config
[cite_start]│   └── db.php  (Connexion PDO à la BDD 'global_store' [cite: 2])
│
├── /includes
│   ├── header.php (Inclusion CDN Tailwind/Flowbite + Navbar dynamique selon rôle)
│   ├── footer.php (Scripts JS finaux)
│   └── functions.php (Helpers PHP: verif session, formatage prix)
│
├── /admin (Accès: role = 'admin')
│   ├── dashboard.php (Stats globales + Graphiques)
│   ├── utilisateurs.php (Liste + Ajout utilisateurs)
│   ├── produits.php (Liste + Ajout produits/catégories)
│   └── categories.php (Gestion spécifique catégories)
│
├── /vendeur (Accès: role = 'vendeur')
│   ├── caisse.php (Interface principale de vente POS)
│   ├── clients.php (Ajout rapide de client)
│   ├── historique.php (Mes ventes du jour)
│   └── session.php (Ouvrir/Fermer la caisse)
│
├── /stock (Accès: role = 'stock')
│   ├── inventaire.php (Vue globale stocks + Alertes rouge)
│   ├── reappro.php (Formulaire entrée stock fournisseur)
│   └── fournisseurs.php (CRUD fournisseurs)
│
├── index.php (Page de Login)
└── logout.php (Script de déconnexion)
```

-----

## 5\. Spécifications Fonctionnelles Détaillées

### 5.1 Authentification & Sécurité

  * **Page :** `index.php`
  * **Fonction :** Formulaire (Email/Mot de passe).
  * **Logique :**
      * [cite_start]Vérification dans la table `utilisateur`[cite: 13].
      * Vérification du hash mot de passe (`password_verify`).
      * Création de `$_SESSION['user_id']`, `$_SESSION['role']`, `$_SESSION['name']`.
      * **Redirection automatique :**
          * Si Admin → `/admin/dashboard.php`
          * Si Vendeur → `/vendeur/caisse.php`
          * Si Stock → `/stock/inventaire.php`

### 5.2 Module Vendeur (Point de Vente)

  * **Page :** `/vendeur/caisse.php`
  * **Interface :**
      * **Gauche :** Liste des produits (Recherche par nom). Affichage Card (Nom, Prix, Stock).
      * **Droite :** Panier virtuel (Tableau HTML).
  * **Fonctionnalités :**
      * Ajouter un produit au panier (Via variable `$_SESSION['panier']`).
      * [cite_start]Sélectionner un client (Liste déroulante `lister_clients` [cite: 53]).
      * **Bouton "Valider Vente" :**
        1.  Ouvre une modale "Paiement".
        2.  Saisie Montant Espèces / Mobile / Carte.
        3.  PHP exécute : `CALL creer_vente`, puis boucle sur le panier pour `CALL ajouter_ligne_vente`, puis `CALL ajouter_paiement`.
        4.  Génération d'un reçu PDF (simple `window.print()` d'une page blanche HTML).
  * **Page :** `/vendeur/session.php`
      * [cite_start]Bouton "Ouvrir Caisse" (Insert dans `session_caisse` [cite: 23]).
      * Bouton "Clôturer Caisse" (Update `total_cloture`).

### 5.3 Module Gestionnaire de Stock

  * **Page :** `/stock/inventaire.php`
      * [cite_start]Tableau listant les produits via `CALL lister_produits`[cite: 41].
      * [cite_start]**Règle UI :** Si `stock_actuel` \<= `seuil_alert`[cite: 7], la ligne s'affiche en **Rouge** (bg-red-100).
  * **Page :** `/stock/reappro.php`
      * Formulaire : Choisir Produit -\> Choisir Fournisseur -\> Quantité.
      * [cite_start]Action PHP : `CALL reapprovisionner_produit(...)`[cite: 71].
      * [cite_start]*Note:* Cette action mettra à jour le stock automatiquement via la procédure[cite: 72].

### 5.4 Module Administrateur (Dashboard)

  * **Page :** `/admin/dashboard.php`
      * **Cartes (KPI) :**
          * [cite_start]CA Total (Appel `chiffre_affaires_total` [cite: 76]).
          * Nombre Ventes jour.
      * **Graphiques (Chart.js) :**
          * [cite_start]Top 5 Produits (Appel `produits_plus_vendus` [cite: 79]).
          * [cite_start]Top Clients Fidèles (Appel `clients_fideles` [cite: 82]).
  * **Page :** `/admin/utilisateurs.php`
      * [cite_start]Création de compte pour les employés via `CALL ajouter_utilisateur`[cite: 55].

-----

## 6\. Règles de Gestion & Contraintes

1.  **Stocks Négatifs :** Le PHP doit empêcher l'ajout au panier si `Quantité demandée > Stock actuel`. (Bien que la procédure SQL mette à jour, il vaut mieux bloquer avant en frontend).
2.  **Prix Fixes :** Lors de la vente, le prix enregistré dans `ligne_vente` doit être le prix du produit **au moment de la vente**, pas une référence dynamique.
3.  **Intégrité :** Une vente ne peut pas être créée sans `id_user` (Vendeur connecté).
4.  **Interface :** Toutes les pages doivent inclure `header.php` pour la navigation et `functions.php` pour la vérification de session (Si pas connecté -\> Redirect Login).

## 7\. Design System (Tailwind + Flowbite)

Pour aller vite, copiez-collez les composants Flowbite suivants :

  * **Navbar :** "Default Navbar" (avec liens conditionnels PHP).
  * **Tables :** "Table with search".
  * **Forms :** "General Form elements" (Inputs avec label flottant ou standard).
  * **Alerts :** Pour les messages de succès ("Vente enregistrée avec succès") ou erreur.
  * **Modals :** Pour la validation du paiement.

-----

## 8\. Livrables Attendus

1.  **Code Source :** Dossier complet zippé.
2.  **Base de Données :** Export `.sql` de la structure et des données (ou le script `MPD.docx` fourni).
3.  **Rapport PDF :** Incluant les captures d'écran de l'interface et les explications des procédures stockées utilisées.