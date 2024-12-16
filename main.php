<?php
// Database connection details
$host = "localhost";
$username = "root";
$password = "";
$dbname = "PlantBiodiversity";

// Connect to MySQL server
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (!$conn->query($sql)) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// Create user_table
$sql = "CREATE TABLE IF NOT EXISTS user_table (
    email VARCHAR(50) NOT NULL PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    student_id VARCHAR(10) NULL,
    dob DATE NULL,
    gender VARCHAR(6) NOT NULL,
    contact_number VARCHAR(15) NULL,
    hometown VARCHAR(50) NOT NULL,
    profile_image VARCHAR(100) NULL,
    resume VARCHAR(100) NULL
)";
if (!$conn->query($sql)) {
    die("Error creating user_table: " . $conn->error);
}

// Create account_table with a foreign key referencing user_table's email
$sql = "CREATE TABLE IF NOT EXISTS account_table (
    email VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    type VARCHAR(5) NOT NULL,
    PRIMARY KEY (email),
    FOREIGN KEY (email) REFERENCES user_table(email) 
    ON DELETE CASCADE ON UPDATE CASCADE
)";
if (!$conn->query($sql)) {
    die("Error creating account_table: " . $conn->error);
}

// Create plant_table
$sql = "CREATE TABLE IF NOT EXISTS plant_table (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Scientific_Name VARCHAR(50) NOT NULL,
    Common_Name VARCHAR(50) NOT NULL,
    family VARCHAR(100) NOT NULL,
    genus VARCHAR(100) NOT NULL,
    species VARCHAR(100) NOT NULL,
    plants_image VARCHAR(100) NULL,
    description VARCHAR(100) NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' -- New status column
)";
if (!$conn->query($sql)) {
    die("Error creating plant_table: " . $conn->error);
}


// Function to insert data if not already present
function insertData($conn, $table, $sql)
{
    // Check if the table is empty
    $result = $conn->query("SELECT COUNT(*) as count FROM $table");
    $row = $result->fetch_assoc();
    if (!$row['count']) {
        // Insert data into the table
        if (!$conn->query($sql)) {
            die("Error inserting data into $table: " . $conn->error);
        }
    }
}

// Insert dummy data into user_table
$sql = "INSERT INTO user_table (email, first_name, last_name, student_id, dob, gender, contact_number, hometown, profile_image, resume)
    VALUES 
    ('admin@swin.edu.my', 'Admin', 'User', '56565849', '1980-01-01', 'Male', '1234567890', 'Admin City', 'profile_images/boy.jpg', NULL),
    ('user1@example.com', 'John', 'Doe', '12565842', '1990-02-02', 'Male', '0987654321', 'Hometown1','profile_images/boy.jpg', NULL),
    ('user2@example.com', 'Jane', 'Smith', '75565849', '1985-03-03', 'Female', '1234567890', 'Hometown2','profile_images/boy.jpg', NULL),
    ('user3@example.com', 'Alice', 'Brown', '62565849', '1995-04-04', 'Female', '0987654321', 'Hometown3','profile_images/boy.jpg', NULL)";
insertData($conn, "user_table", $sql);

// Insert dummy data into account_table
$sql = "INSERT INTO account_table (email, password, type)
        VALUES 
        ('admin@swin.edu.my', '" . password_hash("admin", PASSWORD_DEFAULT) . "', 'admin'),
        ('user1@example.com', '" . password_hash("user1pass", PASSWORD_DEFAULT) . "', 'user'),
        ('user2@example.com', '" . password_hash("user2pass", PASSWORD_DEFAULT) . "', 'user'),
        ('user3@example.com', '" . password_hash("user3pass", PASSWORD_DEFAULT) . "', 'user')";
insertData($conn, "account_table", $sql);

// Insert dummy data into plant_table
$sql = "INSERT INTO plant_table (Scientific_Name, Common_Name, family, genus, species, plants_image, description, status) 
VALUES 
('Dipterocarpus bourdillonii', 'Chiratta anjili', 'Dipterocarpaceae', 'Dipterocarpus', 'Dipterocarpus bourdillonii', 'Dipterocarpus_bourdillonii.JPG', 'plants_description/Dipterocarpus bourdillonii.pdf', 'approved'),
('Dipterocarpus caudiferus', 'Keruing Putih', 'Dipterocarpaceae', 'Dipterocarpus', 'Dipterocarpus caudiferus', 'Dipterocarpus_caudiferus.jpg', 'plants_description/Dipterocarpus caudiferus.pdf', 'approved'),
('Dipterocarpus costatus', 'Keruing Bukit','Dipterocarpaceae','Dipterocarpus','Dipterocarpus costatus','Dipterocarpus_costatus.jpg','plants_description/Dipterocarpus costatus.pdf', 'approved'),
('Dipterocarpus alatus','hairy-leaf apitong','Dipterocarpaceae','Dipterocarpus', 'Dipterocarpus alatus','Dipterocarpus_alatus.jpg','plants_description/Dipterocarpus alatus.pdf','approved'),('Aegopodium podagraria','ground elder','Apiaceae','Aegopodium','Aegopodium podagraria','Aegopodium_podagraria.jpg','','approved'),
('Alcea rosea','common hollyhock','Malvaceae','Alcea','Alcea rosea','Alcea_rosea.jpg','','approved'),
('Alliaria petiolata','garlic mustard','Brassicaceae','Alliaria','Alliaria petiolata','Alliaria_petiolata.jpg','','approved'),
('Anemone alpina','alpine anemone','Ranunculaceae','Anemone','Anemone alpina','Anemone_alpina.jpg','','approved'),
('Anemone hepatica','liverleaf','Ranunculaceae','Anemone','Anemone hepatica','Anemone_hepatica.jpg','','approved'),
('Anemone hupehensis','Japanese anemone','Ranunculaceae','Anemone','Anemone hupehensis','Anemone_hupehensis.jpg','','approved'),
('Anemone nemorosa','wood anemone','Ranunculaceae','Anemone','Anemone nemorosa','Anemone_nemorosa.jpg','','approved'),
('Angelica sylvestris','wild angelica','Apiaceae','Angelica','Angelica sylvestris','Angelica_sylvestris.jpg','','approved'),
('Anthurium andraeanum','flamingo flower','Araceae','Anthurium','Anthurium andraeanum','Anthurium_andraeanum.jpg','','approved'),
('Barbarea vulgaris','yellow rocket','Brassicaceae','Barbarea','Barbarea vulgaris','Barbarea_vulgaris.jpg','','approved'),
('Calendula officinalis','pot marigold','Asteraceae','Calendula','Calendula officinalis','Calendula_officinalis.jpg','','approved'),
('Centranthus ruber','red valerian','Caprifoliaceae','Centranthus','Centranthus ruber','Centranthus_ruber.jpg','','approved'),
('Cirsium arvense','creeping thistle','Asteraceae','Cirsium','Cirsium arvense','Cirsium_arvense.jpg','','approved'),
('Cirsium vulgare','spear thistle','Asteraceae','Cirsium','Cirsium vulgare','Cirsium_vulgare.jpg','','approved'),
('Cucurbita pepo','zucchini','Cucurbitaceae','Cucurbita','Cucurbita pepo','Cucurbita_pepo.jpg','','approved'),
('Cymbalaria muralis','ivy-leaved toadflax','Plantaginaceae','Cymbalaria','Cymbalaria muralis','Cymbalaria_muralis.jpg','','approved'),
('Daucus carota','wild carrot','Apiaceae','Daucus','Daucus carota','Daucus_carota.jpg','','approved'),
('Fittonia albivenis','nerve plant','Acanthaceae','Fittonia','Fittonia albivenis','Fittonia_albivenis.jpg','','approved'),
('Fragaria vesca','wild strawberry','Rosaceae','Fragaria','Fragaria vesca','Fragaria_vesca.jpg','','approved'),
('Helminthotheca echioides','bristly oxtongue','Asteraceae','Helminthotheca','Helminthotheca echioides','Helminthotheca_echioides.jpg','','approved'),
('Humulus lupulus','common hop','Cannabaceae','Humulus','Humulus lupulus','Humulus_lupulus.jpg','','approved'),
('Hypericum androsaemum','tutsan','Hypericaceae','Hypericum','Hypericum androsaemum','Hypericum_androsaemum.jpg','','approved'),
('Hypericum calycinum','Aaron’s beard','Hypericaceae','Hypericum','Hypericum calycinum','Hypericum_calycinum.jpg','','approved'),
('Hypericum perforatum','St John’s wort','Hypericaceae','Hypericum','Hypericum perforatum','Hypericum_perforatum.jpg','','approved'),
('Lactuca serriola','prickly lettuce','Asteraceae','Lactuca','Lactuca serriola','Lactuca_serriola.jpg','','approved'),
('Lamium album','white dead-nettle','Lamiaceae','Lamium','Lamium album','Lamium_album.jpg','','approved'),
('Lamium galeobdolon','yellow archangel','Lamiaceae','Lamium','Lamium galeobdolon','Lamium_galeobdolon.jpg','','approved'),
('Lamium maculatum','spotted dead-nettle','Lamiaceae','Lamium','Lamium maculatum','Lamium_maculatum.jpg','','approved'),
('Lamium purpureum','red dead-nettle','Lamiaceae','Lamium','Lamium purpureum','Lamium_purpureum.jpg','','approved'),
('Lapsana communis','nipplewort','Asteraceae','Lapsana','Lapsana communis','Lapsana_communis.jpg','','approved'),
('Lavandula angustifolia','English lavender','Lamiaceae','Lavandula','Lavandula angustifolia','Lavandula_angustifolia.jpg','','approved'),
('Lavandula stoechas','Spanish lavender','Lamiaceae','Lavandula','Lavandula stoechas','Lavandula_stoechas.jpg','','approved'),
('Liriodendron tulipifera','tulip tree','Magnoliaceae','Liriodendron','Liriodendron tulipifera','Liriodendron_tulipifera.jpg','','approved'),
('Lupinus polyphyllus','garden lupin','Fabaceae','Lupinus','Lupinus polyphyllus','Lupinus_polyphyllus.jpg','','approved'),
('Melilotus albus','white sweet clover','Fabaceae','Melilotus','Melilotus albus','Melilotus_albus.jpg','','approved'),
('Mercurialis annua','annual mercury','Euphorbiaceae','Mercurialis','Mercurialis annua','Mercurialis_annua.jpg','','approved'),
('Ophrys apifera','bee orchid','Orchidaceae','Ophrys','Ophrys apifera','Ophrys_apifera.jpg','','approved'),
('Papaver rhoeas','corn poppy','Papaveraceae','Papaver','Papaver rhoeas','Papaver_rhoeas.jpg','','approved'),
('Papaver somniferum','opium poppy','Papaveraceae','Papaver','Papaver somniferum','Papaver_somniferum.jpg','','approved'),
('Pelargonium graveolens','rose geranium','Geraniaceae','Pelargonium','Pelargonium graveolens','Pelargonium_graveolens.jpg','','approved'),
('Pelargonium zonale','zonal geranium','Geraniaceae','Pelargonium','Pelargonium zonale','Pelargonium_zonale.jpg','','approved'),
('Perovskia atriplicifolia','Russian sage','Lamiaceae','Perovskia','Perovskia atriplicifolia','Perovskia_atriplicifolia.jpg','','approved'),
('Punica granatum','pomegranate','Lythraceae','Punica','Punica granatum','Punica_granatum.jpg','','approved'),
('Pyracantha coccinea','scarlet firethorn','Rosaceae','Pyracantha','Pyracantha coccinea','Pyracantha_coccinea.jpg','','approved'),
('Schefflera arboricola','dwarf umbrella tree','Araliaceae','Schefflera','Schefflera arboricola','Schefflera_arboricola.jpg','','approved'),
('Sedum acre','golden stonecrop','Crassulaceae','Sedum','Sedum acre','Sedum_acre.jpg','','approved'),
('Sedum album','white stonecrop','Crassulaceae','Sedum','Sedum album','Sedum_album.jpg','','approved'),
('Sedum rupestre','reflexed stonecrop','Crassulaceae','Sedum','Sedum rupestre','Sedum_rupestre.jpg','','approved'),
('Sedum sediforme','tassel stonecrop','Crassulaceae','Sedum','Sedum sediforme','Sedum_sediforme.jpg','','approved'),
('Smilax aspera','rough bindweed','Smilacaceae','Smilax','Smilax aspera','Smilax_aspera.jpg','','approved'),
('Tagetes erecta','African marigold','Asteraceae','Tagetes','Tagetes erecta','Tagetes_erecta.jpg','','approved'),
('Trachelospermum jasminoides','star jasmine','Apocynaceae','Trachelospermum','Trachelospermum jasminoides','Trachelospermum_jasminoides.jpg','','approved'),
('Tradescantia fluminensis','wandering jew','Commelinaceae','Tradescantia','Tradescantia fluminensis','Tradescantia_fluminensis.jpg','','approved'),
('Tradescantia pallida','purple heart','Commelinaceae','Tradescantia','Tradescantia pallida','Tradescantia_pallida.jpg','','approved'),
('Tradescantia virginiana','Virginia spiderwort','Commelinaceae','Tradescantia','Tradescantia virginiana','Tradescantia_virginiana.jpg','','approved'),
('Tradescantia zebrina','inch plant','Commelinaceae','Tradescantia','Tradescantia zebrina','Tradescantia_zebrina.jpg','','approved'),
('Trifolium incarnatum','crimson clover','Fabaceae','Trifolium','Trifolium incarnatum','Trifolium_incarnatum.jpg','','approved'),
('Trifolium pratense','red clover','Fabaceae','Trifolium','Trifolium pratense','Trifolium_pratense.jpg','','approved'),
('Trifolium repens','white clover','Fabaceae','Trifolium','Trifolium repens','Trifolium_repens.jpg','','approved'),
('Zamioculcas zamiifolia','ZZ plant','Araceae','Zamioculcas','Zamioculcas zamiifolia','Zamioculcas_zamiifolia.jpg','','approved');";
insertData($conn, "plant_table", $sql);



















// ('Dipterocarpus grandiflorus','large-flower apitong','Dipterocarpaceae','Dipterocarpus','Dipterocarpus grandiflorus','Dipterocarpus_grandiflorus.jpeg','','approved'),
// ('Dipterocarpus intricatus','intricate apitong','Dipterocarpaceae','Dipterocarpus','Dipterocarpus intricatus','Dipterocarpus_intricatus.jpg','','approved'),
// ('Dipterocarpus retusus','rounded apitong','Dipterocarpaceae','Dipterocarpus','Dipterocarpus retusus','Dipterocarpus_retusus.jpg','','approved'),
// ('Dipterocarpus tuberculatus','tuberculate apitong','Dipterocarpaceae','Dipterocarpus','Dipterocarpus tuberculatus','Dipterocarpus_tuberculatus.jpg','','approved'),
// ('Dipterocarpus turbinatus','Indian gum tree','Dipterocarpaceae','Dipterocarpus','Dipterocarpus turbinatus','Dipterocarpus_turbinatus.jpg','','approved'),
// ('Dipterocarpus obtusifolius','blunt-leaf apitong','Dipterocarpaceae','Dipterocarpus','Dipterocarpus obtusifolius','Dipterocarpus_obtusifolius.jpg','','approved'),
// ('Dipterocarpus gracilis','graceful apitong','Dipterocarpaceae','Dipterocarpus','Dipterocarpus gracilis','Dipterocarpus_gracilis.jpg','','approved'),
// ('Dipterocarpus costatus','ribbed apitong','Dipterocarpaceae','Dipterocarpus','Dipterocarpus costatus','Dipterocarpus_costatus.jpg','','approved'),
// ('Dipterocarpus kunstleri','Kunstler’s apitong','Dipterocarpaceae','Dipterocarpus','Dipterocarpus kunstleri','Dipterocarpus_kunstleri.jpg','','approved'),
// ('Dipterocarpus kerrii','Kerr’s apitong','Dipterocarpaceae','Dipterocarpus','Dipterocarpus kerrii','Dipterocarpus_kerrii.jpg','','approved'),
// ('Dipterocarpus baudii','Baud’s apitong','Dipterocarpaceae','Dipterocarpus','Dipterocarpus baudii','Dipterocarpus_baudii.jpg','','approved'),
// ('Dipterocarpus dyeri','Dyer’s apitong','Dipterocarpaceae','Dipterocarpus','Dipterocarpus dyeri','Dipterocarpus_dyeri.jpg','','approved'),
// ('Dipterocarpus caudatus','tailed apitong','Dipterocarpaceae','Dipterocarpus','Dipterocarpus caudatus','Dipterocarpus_caudatus.jpg','','approved'),
// ('Dipterocarpus semivestitus','half-covered apitong','Dipterocarpaceae','Dipterocarpus','Dipterocarpus semivestitus','Dipterocarpus_semivestitus.jpg','','approved'),
// ('Dipterocarpus elongatus','elongated apitong','Dipterocarpaceae','Dipterocarpus','Dipterocarpus elongatus','Dipterocarpus_elongatus.jpg','','approved'),
// ('Dipterocarpus philippinensis','Philippine apitong','Dipterocarpaceae','Dipterocarpus','Dipterocarpus philippinensis','Dipterocarpus_philippinensis.jpg','','approved'),
// ('Dipterocarpus cornutus','horned apitong','Dipterocarpaceae','Dipterocarpus','Dipterocarpus cornutus','Dipterocarpus_cornutus.jpg','','approved'),
// ('Dipterocarpus condorensis','Condor apitong','Dipterocarpaceae','Dipterocarpus','Dipterocarpus condorensis','Dipterocarpus_condorensis.jpg','','approved'),
// ('Dipterocarpus tempehes','tempehes apitong','Dipterocarpaceae','Dipterocarpus','Dipterocarpus tempehes','Dipterocarpus_tempehes.jpg','','approved')