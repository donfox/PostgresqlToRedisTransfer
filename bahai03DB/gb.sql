
CREATE TABLE gb_location(
    bahai_cmty_id            integer PRIMARY KEY,
    post_town                varchar
);


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_gb_location(
    p_bahai_cmty_id          integer,
    p_post_town              varchar)
RETURNS void as $$
BEGIN

    INSERT INTO gb_location(
        bahai_cmty_id,
        post_town)
    VALUES (
        p_bahai_cmty_id,
        p_post_town);

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_gb_location(
    p_bahai_cmty_id          integer,
    p_post_town              varchar)
RETURNS void as $$
BEGIN

    UPDATE gb_location
    SET
        post_town = p_post_town
    WHERE
        bahai_cmty_id = p_bahai_cmty_id;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION delete_gb_location(
    bahai_cmty_id       integer)
RETURNS void as $$
DECLARE
BEGIN

    DELETE from gb_location 
    WHERE bahai_cmty_id = bahai_cmty_id;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE TABLE gb_address(
    address_id               integer PRIMARY KEY,
    address_1                varchar,
    building_name            varchar,
    street_address           varchar,
    locality                 varchar,
    post_town                varchar,
    postcode                 varchar
);


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_gb_address(
    p_address_1              varchar,
    p_building_name          varchar,
    p_street_address         varchar,
    p_locality               varchar,
    p_post_town              varchar,
    p_postcode               varchar)
RETURNS integer as $$
DECLARE
    v_address_id       integer;
BEGIN

    v_address_id := nextval('address_id_seq');

    INSERT INTO gb_address(
        address_id,
        address_1,
        building_name,
        street_address,
        locality,
        post_town,
        postcode)
    VALUES (
        v_address_id,
        p_address_1,
        p_building_name,
        p_street_address,
        p_locality,
        p_post_town,
        p_postcode);

    RETURN v_address_id;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_gb_address(
    p_address_id             integer,
    p_address_1              varchar,
    p_building_name          varchar,
    p_street_address         varchar,
    p_locality               varchar,
    p_post_town              varchar,
    p_postcode               varchar)
RETURNS void as $$
BEGIN

    UPDATE gb_address
    SET
        address_1 = p_address_1,
        building_name = p_building_name,
        street_address = p_street_address,
        locality = p_locality,
        post_town = p_post_town,
        postcode = p_postcode
    WHERE
        address_id = p_address_id;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION delete_gb_address(
    p_address_id       integer)
RETURNS void as $$
DECLARE
BEGIN

    DELETE from gb_address 
    WHERE address_id = p_address_id;

END;
$$ LANGUAGE plpgsql;


GRANT SELECT, INSERT, UPDATE, DELETE, REFERENCES ON
    gb_location, gb_address
    TO apache;
