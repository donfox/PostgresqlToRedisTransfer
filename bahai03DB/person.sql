-- $Id$


CREATE SEQUENCE person_id_seq MINVALUE 1;

-----------------------------------------------------------------------
-- Used for all of the following:
--   1) member of the bahai community
--   2) bahai guest from another community
--   3) non-member seeker
--   4) person referenced elsewhere and thus requiring a row
--
--   Bahai members of the given community will also have an entry in the
--   'member' table (using the same key).
-----------------------------------------------------------------------
CREATE TABLE person (
    person_id                integer default nextval('person_id_seq')
                             PRIMARY KEY,

    bahai_cmty_id            integer  NOT NULL,

    person_category          smallint default 1,  
                             -- VALUES:
                             --   1 = member   (of bahai community)
                             --   2 = guest    (bahai from another community)
                             --   3 = seeker
                             --   4 = external (other person referenced in db)
                             -- (iff = 1, then there's a 'member' row)

    last_name                varchar   NOT NULL,
    first_name               varchar   NOT NULL,


    --  If person_category = 1, then these column values are derived from
    --  the corresponding 'member' table row.
    --  Otherwise they will be entered directly by user.
    primary_phone            varchar,
    primary_email            varchar,
    contact_address_id       integer,

    -- used only if person_category = 1 or 2
    bahai_id_country         char(2)  default 'US',
    bahai_id                 varchar,   -- required, but not in draft
    edit_errors_group_id     integer,

    UNIQUE(bahai_cmty_id, first_name, last_name)
);



-----------------------------------------------------------------------
CREATE TABLE person_category_label (
    person_category          smallint PRIMARY KEY,
    label                    varchar
);


-----------------------------------------------------------------------
COPY person_category_label(person_category,label) FROM stdin DELIMITER ':';
1:member
2:guest
3:seeker
4:external
\.


-----------------------------------------------------------------------
CREATE OR REPLACE VIEW person_label(
    person_id,
    label) as 
SELECT
    person.person_id,
    person.last_name || ', ' || person.first_name || '  (' ||
        bahai_community.bahai_cmty_code || ' : ' ||
        person_category_label.label || ')' as label
FROM
    person, person_category_label, bahai_community
WHERE
    person.person_category = person_category_label.person_category   AND
    bahai_community.bahai_cmty_id = person.bahai_cmty_id;


-----------------------------------------------------------------------
--
-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_person(
    p_bahai_cmty_id            integer,
    p_person_category          smallint,
    p_last_name                varchar,
    p_first_name               varchar,
    p_primary_phone            varchar,
    p_primary_email            varchar,
    p_contact_address_id       integer,
    p_bahai_id_country         char(2),
    p_bahai_id                 varchar,
    p_edit_errors_group_id     integer)
RETURNS integer as $$
DECLARE
    v_person_id integer;
BEGIN
    v_person_id := nextval('person_id_seq');

    INSERT into person(
        person_id,
        bahai_cmty_id,
        person_category,
        last_name,
        first_name,
        primary_phone,
        primary_email,
        contact_address_id,
        bahai_id_country,
        bahai_id,
        edit_errors_group_id)
    VALUES(
        v_person_id,
        p_bahai_cmty_id,
        p_person_category,
        p_last_name,
        p_first_name,
        p_primary_phone,
        p_primary_email,
        p_contact_address_id,
        p_bahai_id_country,
        p_bahai_id,
        p_edit_errors_group_id);

    IF (p_person_category = 1) THEN
        PERFORM insert_update_member_stub(
            v_person_id,
            p_bahai_cmty_id,
            p_last_name,
            p_first_name,
            p_bahai_id_country,
            p_bahai_id,
            p_primary_phone,
            p_primary_email,
            p_contact_address_id);

    END IF;

    RETURN v_person_id;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--
-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_person(
    p_person_id                integer,
    p_person_category          smallint,
    p_last_name                varchar,
    p_first_name               varchar,
    p_primary_phone            varchar,
    p_primary_email            varchar,
    p_contact_address_id       integer,
    p_bahai_id_country         char(2),
    p_bahai_id                 varchar,
    p_edit_errors_group_id     integer)
RETURNS void as $$
DECLARE
    v_person     record;
BEGIN

    SELECT into v_person * FROM person where person_id = p_person_id;

    IF (v_person.person_category = 1 AND p_person_category <> 1) THEN
        PERFORM delete_member(p_person_id);
    END IF;

    IF (v_person.person_category <> 1 AND p_person_category = 1) THEN
        SELECT into v_person * FROM person WHERE person_id = p_person_id;

        PERFORM insert_update_member_stub(
            p_person_id,
            v_person.bahai_cmty_id,
            p_last_name,
            p_first_name,
            p_bahai_id_country,
            p_bahai_id,
            p_primary_phone,
            p_primary_email,
            p_contact_address_id);

    END IF;
    

    UPDATE person
    SET
        person_category = p_person_category,
        last_name = p_last_name,
        first_name = p_first_name,
        primary_phone = p_primary_phone,
        primary_email = p_primary_email,
        contact_address_id = p_contact_address_id,
        bahai_id_country = p_bahai_id_country,
        bahai_id = p_bahai_id,
        edit_errors_group_id = p_edit_errors_group_id
    WHERE 
        person_id = p_person_id;
      
END;
$$ LANGUAGE plpgsql;

-----------------------------------------------------------------------
--
-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION delete_person(
    p_person_id                integer)
RETURNS void as $$
DECLARE
    v_person record;
BEGIN
    SELECT into v_person * 
        FROM person
        WHERE person_id = p_person_id;

    -- *****************************

    DELETE FROM person
      WHERE person_id = p_person_id;

END;
$$ LANGUAGE plpgsql;


GRANT SELECT, INSERT, UPDATE, DELETE, REFERENCES ON person TO apache;

GRANT SELECT, UPDATE ON person_id_seq TO apache;

GRANT SELECT ON person_label TO apache;
