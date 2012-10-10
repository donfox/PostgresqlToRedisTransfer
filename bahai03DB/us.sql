CREATE TABLE us_location(
    bahai_cmty_id            integer PRIMARY KEY,
    state_code               char(2),
    city                     varchar
);


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_us_location(
    p_bahai_cmty_id          integer,
    p_state_code             char(2),
    p_city                   varchar)
RETURNS void as $$
BEGIN

    INSERT INTO us_location(bahai_cmty_id, state_code, city)
        VALUES (p_bahai_cmty_id, p_state_code, p_city);

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_us_location(
    p_bahai_cmty_id          integer,
    p_state_code             char(2),
    p_city                   varchar)
RETURNS void as $$
BEGIN

    UPDATE us_location
    SET
        state_code = p_state_code,
        city = p_city
    WHERE
        bahai_cmty_id = p_bahai_cmty_id;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE TABLE us_address(
    address_id       integer NOT NULL default nextval('address_id_seq'),
    address_1        varchar,
    address_2        varchar,
    city             varchar,
    state_code       char(2),
    zip_code         varchar,
    country_code     char(2) REFERENCES country
);


CREATE OR REPLACE RULE us_address_insert_rule
  AS ON INSERT TO us_address DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'us_address', NEW.address_id, 'I');

CREATE OR REPLACE RULE us_address_update_rule
  AS ON UPDATE TO us_address DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'us_address', OLD.address_id, 'U');

CREATE OR REPLACE RULE us_address_delete_rule
  AS ON DELETE TO us_address DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'us_address', OLD.address_id, 'D');


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_us_address(
    p_address_1        varchar,
    p_address_2        varchar,
    p_city             varchar,
    p_state_code       char(2),
    p_zip_code         varchar,
    p_country_code     char(2) )
RETURNS integer as $$
DECLARE
    v_address_id       integer;
BEGIN
    v_address_id := nextval('address_id_seq');

    INSERT into us_address(
        address_id,
        address_1,
        address_2,
        city,
        state_code,
        zip_code,
        country_code)
    VALUES(
        v_address_id,
        p_address_1,
        p_address_2,
        p_city,
        p_state_code,
        p_zip_code,
        'US');

    RETURN v_address_id;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_us_address(
    p_address_id       integer,
    p_address_1        varchar,
    p_address_2        varchar,
    p_city             varchar,
    p_state_code       char(2),
    p_zip_code         varchar,
    p_country_code     char(2) )
RETURNS void as $$
BEGIN

    UPDATE us_address
    SET
        address_1 = p_address_1,
        address_2 = p_address_2,
        city = p_city,
        state_code = p_state_code,
        zip_code = p_zip_code
    WHERE address_id = p_address_id;

END;
$$ LANGUAGE plpgsql;



-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION delete_us_address(
    p_address_id       integer)
RETURNS void as $$
DECLARE
BEGIN

    DELETE from us_address 
    WHERE address_id = p_address_id;

END;
$$ LANGUAGE plpgsql;


GRANT SELECT, INSERT, UPDATE, DELETE, REFERENCES ON
    us_address, us_location
    TO apache;
