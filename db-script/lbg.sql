DROP TABLE lbg;

CREATE TABLE lbg (
	id_lbg int not null primary key auto_increment,
    city varchar(50) not null,
    state varchar(50) not null,
    web_page varchar(200)
);

INSERT INTO lbg (city, state, web_page)
	VALUES ('', '', '');
    
UPDATE lbg SET city = '', state = '', web_page = ''
	WHERE id_lbg = '';
    
DELETE FROM lbg WHERE id_lbg = '';
