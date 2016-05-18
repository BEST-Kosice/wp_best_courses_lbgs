DROP TABLE best_event;

CREATE TABLE best_event (
	id_event int not null primary key auto_increment,
    event_name varchar(100) not null,
    place varchar(40) not null,
    dates varchar(40) not null,
    event_type varchar(100) not null,
    acad_compl varchar(20),
    fee varchar(10) not null,
    app_deadline varchar(30),
    login_url varchar(1000)
);

INSERT INTO best_event (event_name, place, dates, event_type, acad_compl, fee, app_deadline, login_url)
VALUES ('', '', '', '', '','', '', '');

DELETE FROM best_event WHERE id_event = 1;

UPDATE best_event SET event_name = '', place = '', dates = '',
	event_type = '', acad_compl = '', fee = '', app_deadline = '', login_url = ''
    WHERE id_event = 2;