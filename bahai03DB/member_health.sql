-- $Id$
-- Bahai member health related tables

-----------------------------------------------------------------------
--  
-----------------------------------------------------------------------
CREATE TABLE member_health (
    person_id            integer   PRIMARY KEY,

    -- *** HEALTH CARE ***
         --   Rows in 'healthcare_provider'
    primary_provider         integer     -- to designate which is primary

         --   Rows in 'medical_condition'
);


-----------------------------------------------------------------------
--  Association table   member => healthcare_provider
-----------------------------------------------------------------------
CREATE TABLE member_healthcare_provider (
    person_id            integer,
    healthcare_provider_id   integer,

    PRIMARY KEY (person_id, healthcare_provider_id)
);
    

-----------------------------------------------------------------------
--  Linked to from member table.
--  Note that entries can be shared by multiple members
--  (such as would commonly be the case in a household).
-----------------------------------------------------------------------
CREATE SEQUENCE healthcare_prov_id_seq MINVALUE 1;
CREATE TABLE healthcare_provider (
    healthcare_prov_id   integer NOT NULL
                              default nextval('healthcare_prov_id_seq') 
                              PRIMARY KEY,
    person_id            integer,
    npid                 varchar,
    professional_type    varchar,
    practice_group       varchar
);


CREATE OR REPLACE FUNCTION  insert_healthcare_provider(
    p_person_id            integer,
    p_npid                 varchar,
    p_professional_type    varchar,
    p_practice_group       varchar)
RETURNS integer as $$
DECLARE
    v_healthcare_prov_id integer;
BEGIN

    v_healthcare_prov_id := nextval('healthcare_prov_id_seq');
    
    INSERT INTO healthcare_provider(
        healthcare_prov_id,
        person_id,
        npid,
        professional_type,
        practice_group)
    VALUES(
        v_healthcare_prov_id,
        p_person_id,
        p_npid,
        p_professional_type,
        p_practice_group);

    RETURN v_healthcare_prov_id;

END;
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION associate_member_healthcare_provider(
    p_person_id          integer,
    p_healthcare_prov_id integer)
RETURNS void as $$
BEGIN

    INSERT into member_healthcare_provider(
        person_id,
        healthcare_prov_id)
    VALUES(
        p_person_id,
        p_healthcare_prov_id);

END;
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION dissociate_member_healthcare_provider(
    p_person_id          integer,
    p_healthcare_prov_id integer)
RETURNS void as $$
DECLARE
    v_count integer;
BEGIN

    DELETE FROM member_healthcare_provider
    WHERE
        person_id = p_person_id  AND
        healthcare_prov_id = p_healthcare_prov_id;

    SELECT INTO v_count COUNT FROM member_healthcare_provider
    WHERE
        healthcare_prov_id = p_healthcare_prov_id;

    IF v_count = 0  THEN
        DELETE FROM healthcare_provider
        WHERE healthcare_prov_id = p_healthcare_prov_id;
    END IF;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--
-----------------------------------------------------------------------
CREATE TABLE medical_condition (
    person_id           integer,
    disease_condition   varchar,
    medicine            varchar,
    equipment           varchar,
    require_power       boolean default false,
    remarks             varchar,

    PRIMARY KEY (person_id, disease_condition)
); 


CREATE OR REPLACE FUNCTION insert_medical_condition(
    p_person_id           integer,
    p_disease_condition   varchar,
    p_medicine            varchar,
    p_equipment           varchar,
    p_require_power       boolean)
RETURNS void as $$
BEGIN

    INSERT INTO medical_condition(
        person_id,
        disease_condition,
        medicine,
        equipment,
        require_power)
    VALUES(
        p_person_id,
        p_disease_condition,
        p_medicine,
        p_equipment,
        p_require_power);

END;
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION update_medical_condition(
    p_person_id           integer,
    p_disease_condition   varchar,
    p_medicine            varchar,
    p_equipment           varchar,
    p_require_power       boolean)
RETURNS void as $$
DECLARE
BEGIN

    UPDATE medical_condition
    SET
        medicine = p_medicine,
        equipment = p_equipment,
        require_power = p_require_power
    WHERE 
        person_id = p_person_id  AND
        disease_condition = p_disease_condition;

END;
$$ LANGUAGE plpgsql;



CREATE OR REPLACE FUNCTION delete_medical_condition(
    person_id           integer,
    disease_condition   varchar)
RETURNS void as $$
BEGIN

    DELETE FROM medical_condition
    WHERE 
        person_id = p_person_id  AND
        disease_condition = p_disease_condition;

END;
$$ LANGUAGE plpgsql;




GRANT SELECT, INSERT, UPDATE, DELETE, REFERENCES ON
  member_healthcare_provider, healthcare_provider, medical_condition 
    TO apache;

GRANT SELECT, UPDATE ON
  healthcare_prov_id_seq
    TO apache;
