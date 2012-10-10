CREATE TABLE generic_location(
    bahai_cmty_id            integer PRIMARY KEY,
    location_line            varchar(80)
);


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_generic_location(
    p_bahai_cmty_id          integer,
    p_location_line          varchar)
RETURNS void as $$
BEGIN

    INSERT INTO generic_location(bahai_cmty_id, location_line)
        VALUES (p_bahai_cmty_id, p_location_line);

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_generic_location(
    p_bahai_cmty_id          integer,
    p_location_line          varchar)
RETURNS void as $$
BEGIN

    UPDATE generic_location
    SET
        location_line = p_location_line
    WHERE
        bahai_cmty_id = p_bahai_cmty_id;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE TABLE generic_address(
    address_id       integer NOT NULL default nextval('address_id_seq'),
    address_1        varchar,
    address_2        varchar,
    address_3        varchar,
    address_4        varchar,
    country_code     char(2) REFERENCES country
);


CREATE OR REPLACE RULE generic_address_insert_rule
  AS ON INSERT TO generic_address DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'generic_address', NEW.address_id, 'I');

CREATE OR REPLACE RULE generic_address_update_rule
  AS ON UPDATE TO generic_address DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'generic_address', OLD.address_id, 'U');

CREATE OR REPLACE RULE generic_address_delete_rule
  AS ON DELETE TO generic_address DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'generic_address', OLD.address_id, 'D');


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_generic_address(
    p_address_1        varchar,
    p_address_2        varchar,
    p_address_3        varchar,
    p_address_4        varchar,
    p_country_code     char(2) )
RETURNS integer as $$
DECLARE
    v_address_id       integer;
BEGIN
    v_address_id := nextval('address_id_seq');

    INSERT into generic_address(
        address_id,
        address_1,
        address_2,
        address_3,
        address_4,
        country_code)
    VALUES(
        v_address_id,
        p_address_1,
        p_address_2,
        p_address_3,
        p_address_4,
        p_country_code);

    RETURN v_address_id;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_generic_address(
    p_address_id       integer,
    p_address_1        varchar,
    p_address_2        varchar,
    p_address_3        varchar,
    p_address_4        varchar,
    p_country_code     char(2) )
RETURNS void as $$
BEGIN

    UPDATE generic_address
    SET
        address_1 = p_address_1,
        address_2 = p_address_2,
        address_3 = p_address_3,
        address_4 = p_address_4,
        country_code = p_country_code
    WHERE address_id = p_address_id;

END;
$$ LANGUAGE plpgsql;



-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION delete_generic_address(
    p_address_id       integer)
RETURNS void as $$
DECLARE
BEGIN

    DELETE from generic_address 
    WHERE address_id = p_address_id;

END;
$$ LANGUAGE plpgsql;


GRANT SELECT, INSERT, UPDATE, DELETE, REFERENCES ON
    generic_address, generic_location
    TO apache;
