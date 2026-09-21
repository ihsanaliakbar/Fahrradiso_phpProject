<?php
$sql = "
-- drop table ivan_sz_ausleihe_sz;
-- drop table ivan_f_ausleihe_f;
-- drop table ivan_fahrrad_ausleihe;
-- drop table ivan_fahrrad;
-- drop table ivan_model;
-- drop table ivan_preisgruppe;
-- drop table ivan_kunde_tour_event;
-- drop table ivan_tour_event;
-- drop table ivan_tour;
-- drop table ivan_sondernzubehoer_ausleihe;
-- drop table ivan_sondernzubehoer;
-- drop table ivan_kunde;

create table if not exists ivan_kunde (
	id int not null auto_increment primary key,
	vorname text not null,
	adresse text not null,
	postleizahl int not null,
	telefon text not null,
	anrede text not null,
	nachname text not null
);

create table if not exists ivan_tour (
	id int not null auto_increment primary key,
	schwierigkeitsgrad int not null,
	name text not null,
	beschreibung text not null,
	laenge_km double not null,
	max_kunden int not null,
	zielort text not null,
	startort text not null
);

create table if not exists ivan_tour_event (
	teid int not null auto_increment primary key,
	end_datum date not null,
	start_datum date not null,
	tid int not null,
	
	foreign key (tid) references ivan_tour (id)
);

create table if not exists ivan_kunde_tour_event (
	kid int not null,
	teid int not null,
	
	primary key (kid, teid),
	foreign key (kid) references ivan_kunde(id),
	foreign key (teid) references ivan_tour_event(teid)
);

create table if not exists ivan_preisgruppe (
	id int not null auto_increment primary key,
	bezeichnung text not null
);

insert into ivan_preisgruppe(bezeichnung) values
('Deluxe'), 
('Gehoben'),
('Standard'),
('Robust');

create table if not exists ivan_model (
	id int not null auto_increment primary key,
	bezeichnung text not null,
	rahmengroesse double not null,
	pgid int not null,
	
	foreign key (pgid) references ivan_preisgruppe(id)
);

create table if not exists ivan_sondernzubehoer (
	id int not null auto_increment primary key,
	bezeichnung text not null
);

create table if not exists ivan_sondernzubehoer_ausleihe (
	id int not null auto_increment primary key,
	kid int not null,
	datum_start date not null,
	
	foreign key (kid) references ivan_kunde(id)
);

create table if not exists ivan_sz_ausleihe_sz (
	szid int not null,
	datum_rueckgabe date not null,
	az_ausleihe_id int not null,

	primary key (szid, datum_rueckgabe, az_ausleihe_id),
	foreign key (szid) references ivan_sondernzubehoer(id),
	foreign key (az_ausleihe_id) references ivan_sondernzubehoer_ausleihe(id)
);

create table if not exists ivan_fahrrad (
	nr int not null auto_increment primary key,
	art text not null,
	modelnr int not null,
	letzte_wartung date not null,
	anschaffungspreis double not null,
	anschaffungsdatum date not null,
	
	foreign key (modelnr) references ivan_model(id)
);

create table if not exists ivan_fahrrad_ausleihe (
	id int not null auto_increment primary key,
	vname text not null,
	kaution double not null,
	datum_start date not null,
	kid int not null,
	
	foreign key (kid) references ivan_kunde(id)
);

create table if not exists ivan_f_ausleihe_f (
	fid int not null,
	datum_rueckgabe date not null,
	f_ausleihe_id int not null,

	primary key (fid, datum_rueckgabe, f_ausleihe_id),
	foreign key (fid) references ivan_fahrrad(nr),
	foreign key (f_ausleihe_id) references ivan_fahrrad_ausleihe(id)
);
";

function exec_query($q) {
	if ($result = mysqli_query($conn, $q)) {
		while($row = mysqli_fetch_array($result))
		{
			echo implode(" | ", $row);
		}
		mysqli_free_result($result);
	}
}

$conn = mysqli_connect("localhost","tre_itm2_24","BraunWeissesPasswort","tre_itm2_24");

if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
}

if (!mysqli_multi_query($conn, $sql)) {
  echo "Error creating table: " . mysqli_error($conn);
  die();
}

do {} while (mysqli_next_result($conn));
echo "Tables created successfully";

mysqli_close($conn);
?>