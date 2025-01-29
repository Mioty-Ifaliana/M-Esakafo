create database m_esakafo
\c m_esakafo

CREATE TABLE plats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    sprite VARCHAR(100) NOT NULL,
    temps_cuisson TIME,
    prix DECIMAL(10, 2)
);

CREATE TABLE user (
    id SERIAL PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    mot_de_passe VARCHAR(100) NOT NULL
);

CREATE TABLE ingredients (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    id_unite INT,
    sprite VARCHAR(100) NOT NULL
);

CREATE TABLE unite (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL
);

CREATE TABLE mouvements (
    id SERIAL PRIMARY KEY,
    id_ingredient INT,
    entree INT,
    sortie INT
);

CREATE TABLE plats (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    sprite VARCHAR(100) NOT NULL,
    temps_cuisson TIME,
    prix DECIMAL
);

CREATE TABLE recette (
    id SERIAL PRIMARY KEY,
    id_plat INT,
    id_ingredient INT,
    quantite_ingredient INT
);

CREATE TABLE commande (
    id SERIAL PRIMARY KEY,
    numero_ticket INT,
    id_user INT,
    id_plats INT,
    quantite_plats INT,
    statut INT,
    date_commande DATE
);

CREATE TABLE mode_paiement (
    id SERIAL PRIMARY KEY,
    mode_paiement VARCHAR(100) NOT NULL
);

CREATE TABLE payement_commande (
    id SERIAL PRIMARY KEY,
    id_user INT,
    numero_ticket INT,
    prix_total DECIMAL,
    id_mode_paiement INT,
    statut INT
);




