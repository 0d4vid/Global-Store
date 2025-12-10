-- SQL Script to Populate the Database with More Products and Categories

USE global_store;

-- Add new categories
CALL ajouter_categorie('Hygiène & Beauté');
CALL ajouter_categorie('Entretien Ménager');
CALL ajouter_categorie('Bébé & Enfant');
CALL ajouter_categorie('Papeterie');
CALL ajouter_categorie('Boissons');

-- Add more products to existing and new categories

-- Alimentation (id_cat = 1)
CALL ajouter_produit('Spaghetti Panzani 500g', 'Pâtes alimentaires de qualité supérieure', 800, 650, 100, 20, 1, NULL);
CALL ajouter_produit('Huile de Soja Mayor 1L', 'Huile végétale raffinée', 1500, 1200, 80, 15, 1, NULL);
CALL ajouter_produit('Sardines à huile Broli', 'Boîte de sardines de 125g', 500, 350, 150, 30, 1, NULL);
CALL ajouter_produit('Lait en poudre Nido 400g', 'Lait entier pour toute la famille', 2500, 2200, 50, 10, 1, NULL);
CALL ajouter_produit('Sucre en morceaux 1kg', 'Sucre de canne en morceaux', 1000, 800, 200, 40, 1, NULL);
CALL ajouter_produit('Farine de blé La Merveille 1kg', 'Farine tout usage type 45', 750, 600, 120, 25, 1, NULL);
CALL ajouter_produit('Sel de cuisine 500g', 'Sel fin iodé', 200, 100, 300, 50, 1, NULL);
CALL ajouter_produit('Cube Maggi Crevette 10g', 'Bouillon en cube saveur crevette', 100, 50, 500, 100, 1, NULL);

-- Électronique (id_cat = 2)
CALL ajouter_produit('Smartphone Tecno Spark 10', '64Go RAM, 4Go ROM', 75000, 68000, 15, 3, 2, NULL);
CALL ajouter_produit('Fer à repasser Philips', 'Semelle anti-adhésive', 12000, 9500, 25, 5, 2, NULL);
CALL ajouter_produit('Rallonge électrique 6 prises', 'Câble de 5 mètres avec interrupteur', 4500, 3000, 40, 10, 2, NULL);
CALL ajouter_produit('Clé USB Sandisk 32Go', 'USB 3.0 haute vitesse', 5000, 3500, 60, 15, 2, NULL);
CALL ajouter_produit('Écouteurs Bluetooth Pro', 'Son stéréo, autonomie 6h', 8000, 6000, 30, 5, 2, NULL);

-- Vêtements (id_cat = 3)
CALL ajouter_produit('Polo Homme Coton', 'Couleur Bleu, Taille XL', 7500, 5000, 40, 10, 3, NULL);
CALL ajouter_produit('Robe dété Femme', 'Motifs floraux, Taille M', 10000, 7000, 20, 5, 3, NULL);
CALL ajouter_produit('Chaussettes de sport (Paire)', 'Taille unique, Coton', 1000, 500, 100, 20, 3, NULL);
CALL ajouter_produit('Jeans Slim Homme', 'Denim brut, Taille 32', 15000, 11000, 25, 5, 3, NULL);

-- Cosmétiques (id_cat = 4)
CALL ajouter_produit('Savon de toilette Dove', 'Pain de 100g, hydratant', 600, 400, 80, 20, 4, NULL);
CALL ajouter_produit('Déodorant Spray Axe', 'Senteur marine, 150ml', 2000, 1500, 50, 10, 4, NULL);
CALL ajouter_produit('Pâte dentifrice Colgate', 'Tube de 75ml, protection caries', 800, 550, 70, 15, 4, NULL);
CALL ajouter_produit('Lait de corps Nivea', 'Hydratation intense, 250ml', 3500, 2800, 40, 10, 4, NULL);

-- Hygiène & Beauté (id_cat = 5)
CALL ajouter_produit('Shampooing Head & Shoulders', 'Anti-pelliculaire, 200ml', 2500, 2000, 45, 10, 5, NULL);
CALL ajouter_produit('Gel Douche Fa', 'Senteur Tropical, 250ml', 1800, 1400, 60, 15, 5, NULL);
CALL ajouter_produit('Coton-tiges (Boîte de 200)', 'Tiges en plastique', 500, 300, 100, 30, 5, NULL);
CALL ajouter_produit('Crème solaire SPF 50', 'Haute protection UVA/UVB', 6000, 4500, 20, 5, 5, NULL);

-- Entretien Ménager (id_cat = 6)
CALL ajouter_produit('Eau de Javel La Croix 1L', 'Désinfectant multi-usages', 1000, 700, 70, 20, 6, NULL);
CALL ajouter_produit('Liquide vaisselle Madar 500ml', 'Dégraissant citron', 800, 600, 90, 25, 6, NULL);
CALL ajouter_produit('Éponge à récurer (Lot de 3)', 'Double face', 500, 250, 150, 40, 6, NULL);
CALL ajouter_produit('Insecticide Aérosol Super U', 'Action rapide, 300ml', 1500, 1100, 60, 15, 6, NULL);

-- Bébé & Enfant (id_cat = 7)
CALL ajouter_produit('Couches Pampers Taille 4 (20)', 'Pour bébé de 9 à 14 kg', 5000, 4200, 40, 10, 7, NULL);
CALL ajouter_produit('Lingettes bébé (Paquet de 72)', 'Hypoallergénique', 1200, 800, 80, 20, 7, NULL);
CALL ajouter_produit('Biberon 240ml', 'Tétine en silicone', 2000, 1500, 30, 10, 7, NULL);

-- Papeterie (id_cat = 8)
CALL ajouter_produit('Cahier 200 pages', 'Grand format, quadrillé', 700, 450, 200, 50, 8, NULL);
CALL ajouter_produit('Stylo à bille Bic (Bleu)', 'Pointe moyenne', 100, 50, 500, 100, 8, NULL);
CALL ajouter_produit('Ramette de papier A4', '500 feuilles, 80g/m²', 3500, 2800, 50, 10, 8, NULL);

-- Boissons (id_cat = 9)
CALL ajouter_produit('Coca-Cola 1.5L', 'Bouteille PET', 1000, 750, 100, 25, 9, NULL);
CALL ajouter_produit('Eau minérale Supermont 1.5L', 'Source naturelle', 500, 300, 200, 50, 9, NULL);
CALL ajouter_produit('Jus de fruits Presséa Ananas 1L', '100% pur jus', 1200, 900, 80, 20, 9, NULL);
CALL ajouter_produit('Bière 33 Export 65cl', 'Bière blonde', 700, 500, 120, 30, 9, NULL);

-- Add a few more products to make it look populated
CALL ajouter_produit('Tomate en conserve 400g', 'Tomates pelées', 600, 450, 90, 20, 1, NULL);
CALL ajouter_produit('Chargeur de téléphone universel', 'USB-C, Micro USB, Lightning', 3500, 2500, 50, 10, 2, NULL);
CALL ajouter_produit('Sandales en plastique', 'Taille 42', 2500, 1500, 60, 15, 3, NULL);
CALL ajouter_produit('Parfum d ambiance Fleur d oranger', 'Diffuseur 100ml', 4000, 3000, 30, 5, 6, NULL);
CALL ajouter_produit('Talc pour bébé 200g', 'Adoucit et protège', 1500, 1100, 40, 10, 7, NULL);
CALL ajouter_produit('Vin rouge Baron de France', 'Bouteille 75cl', 4500, 3500, 20, 5, 9, NULL);

COMMIT;
