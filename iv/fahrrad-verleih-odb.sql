create table ivan_kunde (
  id int not null primary key,
  vorname varchar(255) not null,
  adresse varchar(255) not null,
  postleizahl int not null,
  telefon varchar(255) not null,
  anrede varchar(255) not null,
  nachname varchar(255) not null
);

create table ivan_tour (
  id int not null primary key,
  schwierigkeitsgrad int not null,
  name varchar(255) not null,
  beschreibung varchar(255) not null,
  laenge_km double not null,
  max_kunden int not null,
  zielort varchar(255) not null,
  startort varchar(255) not null
);

create table ivan_tour_event (
  teid int not null primary key,
  end_datum date not null,
  start_datum date not null,
  tid int not null,
  
  foreign key (tid) references ivan_tour (id)
);

create table ivan_kunde_tour_event (
  kid int not null,
  teid int not null,
  
  primary key (kid, teid),
  foreign key (kid) references ivan_kunde(id),
  foreign key (teid) references ivan_tour_event(teid)
);

create table ivan_preisgruppe (
  id int not null primary key,
  bezeichnung varchar(255) not null
);

create table ivan_model (
  id int not null primary key,
  bezeichnung varchar(255) not null,
  rahmengroesse double not null,
  pgid int not null,
  
  foreign key (pgid) references ivan_preisgruppe(id)
);

create table ivan_fahrrad (
  nr int not null primary key,
  art varchar(255) not null,
  modelnr int not null,
  letzte_wartung date not null,
  anschaffungspreis double not null,
  anschaffungsdatum date not null,
  
  foreign key (modelnr) references ivan_model(id)
);

create table ivan_sondernzubehoer (
  id int not null primary key,
  bezeichnung varchar(255) not null
);

create table ivan_sondernzubehoer_ausleihe (
  kid int not null,
  datum_start date not null,
  szid int not null,
  datum_rueckgabe date not null,
  
  primary key (datum_start, szid),
  foreign key (kid) references ivan_kunde(id),
  foreign key (szid) references ivan_sondernzubehoer(id)
);

create table ivan_fahrrad_ausleihe (
  vname varchar(255) not null,
  kaution double not null,
  fid int not null,
  datum_start date not null,
  datum_rueckgabe date not null,
  kid int not null,
  
  primary key (datum_start, fid),
  foreign key (kid) references ivan_kunde(id),
  foreign key (fid) references ivan_fahrrad(nr)
);
