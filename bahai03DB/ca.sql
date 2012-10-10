CREATE TABLE ca_location(
    bahai_cmty_id            integer PRIMARY KEY,
    province_abbr            char(2),
    municipality             varchar
);


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_ca_location(
    p_bahai_cmty_id          integer,
    p_province_abbr          char(2),
    p_municipality           varchar)
RETURNS void as $$
BEGIN

    INSERT INTO ca_location(bahai_cmty_id, province_abbr, municipality)
        VALUES (p_bahai_cmty_id, p_province_abbr, p_municipality);

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_ca_location(
    p_bahai_cmty_id          integer,
    p_province_abbr          char(2),
    p_municipality           varchar)
RETURNS void as $$
BEGIN

    UPDATE ca_location
    SET
        bahai_cmty_id = p_bahai_cmty_id,
        province_abbr = p_province_abbr,
        municipality = p_municipality;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE TABLE ca_address(
    address_id               integer NOT NULL
                             default nextval('address_id_seq'),
    address_1                varchar,
    address_2                varchar,
    municipality             varchar,
    province_abbr            char(2),
    postal_code              varchar,
    country_code             char(2) REFERENCES country
);


CREATE OR REPLACE RULE ca_address_insert_rule
  AS ON INSERT TO ca_address DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'ca_address', NEW.address_id, 'I');

CREATE OR REPLACE RULE ca_address_update_rule
  AS ON UPDATE TO ca_address DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'ca_address', OLD.address_id, 'U');

CREATE OR REPLACE RULE ca_address_delete_rule
  AS ON DELETE TO ca_address DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'ca_address', OLD.address_id, 'D');


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_ca_address(
    p_address_1              varchar,
    p_address_2              varchar,
    p_municipality           varchar,
    p_province_abbr          char(2),
    p_postal_code            varchar,
    p_country_code           char(2) )
RETURNS integer as $$
DECLARE
    v_address_id       integer;
BEGIN
    v_address_id := nextval('address_id_seq');

    INSERT into ca_address(
        address_id,
        address_1,
        address_2,
        municipality,
        province_abbr,
        postal_code,
        country_code)
    VALUES(
        v_address_id,
        p_address_1,
        p_address_2,
        p_municipality,
        p_province_abbr,
        p_postal_code,
        'CA');

    RETURN v_address_id;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_ca_address(
    p_address_1              varchar,
    p_address_2              varchar,
    p_municipality           varchar,
    p_province_abbr          char(2),
    p_postal_code            varchar,
    p_country_code           char(2) )
RETURNS integer as $$
DECLARE
    v_address_id       integer;
BEGIN
    v_address_id := nextval('address_id_seq');

    INSERT into ca_address(
        address_id,
        address_1,
        address_2,
        municipality,
        province_abbr,
        postal_code,
        country_code)
    VALUES(
        v_address_id,
        p_address_1,
        p_address_2,
        p_municipality,
        p_province_abbr,
        p_postal_code,
        'CA');

    RETURN v_address_id;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_ca_address(
    p_address_id       integer,
    p_address_1        varchar,
    p_address_2        varchar,
    p_municipality     varchar,
    p_province_abbr    char(2),
    p_postal_code         varchar,
    p_country_code     char(2) )
RETURNS void as $$
BEGIN

    UPDATE ca_address
    SET
        address_1 = p_address_1,
        address_2 = p_address_2,
        municipality = p_municipality,
        province_abbr = p_province_abbr,
        postal_code = p_postal_code
    WHERE address_id = p_address_id;

END;
$$ LANGUAGE plpgsql;



-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION delete_ca_address(
    p_address_id       integer)
RETURNS void as $$
DECLARE
BEGIN

    DELETE from ca_address 
    WHERE address_id = p_address_id;

END;
$$ LANGUAGE plpgsql;


GRANT SELECT, INSERT, UPDATE, DELETE, REFERENCES ON
    ca_address, ca_location
    TO apache;
