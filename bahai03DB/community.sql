-- $Id$
-- Bahai community and related tables

-----------------------------------------------------------------------
--
-----------------------------------------------------------------------
CREATE TABLE bahai_cluster (
    cluster_code          varchar  PRIMARY KEY,
    cluster_name          varchar,

    -- Area Teaching Committee info set up in 'atc_member'

    region                varchar,
    remarks               varchar,

    edit_errors_group_id  integer
); 


CREATE SEQUENCE bahai_cmty_id_seq MINVALUE 1;
-----------------------------------------------------------------------
--  Bahai community is also known as Bahai location
CREATE TABLE bahai_community (
    bahai_cmty_id            integer  default nextval('bahai_cmty_id_seq')
                             PRIMARY KEY,

    -- country + bahai_cmty_code together are unique
    country_code             char(2)   NOT NULL  REFERENCES country,
    bahai_cmty_code          varchar   NOT NULL,

    bahai_cmty_name          varchar   NOT NULL,

    --  The location within the country stored in a table determined by
    --  country_code (us_location, gb_location, etc.)
    --  Similary the bc_address is stored (us_address, gb_address, etc.).

    time_zone                varchar,    -- used in php code

    is_lsa                   boolean default 't', 
                        -- local spiritual assembly ?
                        -- if yes, then there's a record in lsa_info table

    comm_website_url         varchar,

    --  bc = bahai center
    bc_address_id            integer,
    bc_phone                 varchar,
    bc_fax                   varchar,
    bc_website_url           varchar,

    bahai_cluster            varchar 
                             references bahai_cluster on delete cascade,
    bahai_eu                 varchar, 
                             -- key to bahai_eu  table (electoral unit) 
     

    remarks                  varchar,

    edit_errors_group_id     integer,

    UNIQUE (country_code, bahai_cmty_code)
); 

CREATE OR REPLACE RULE bahai_community_insert_rule
  AS ON INSERT TO bahai_community DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'bahai_community',
      NEW.bahai_cmty_id, 'I');

CREATE OR REPLACE RULE bahai_community_update_rule
  AS ON UPDATE TO bahai_community DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'bahai_community',
      OLD.bahai_cmty_id, 'U');

CREATE OR REPLACE RULE bahai_community_delete_rule
  AS ON DELETE TO bahai_community DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'bahai_community',
      OLD.bahai_cmty_id, 'D');



-----------------------------------------------------------------------
--  Local Spiritual Assembly
--    (an addendum to the bahai_community table)
-----------------------------------------------------------------------
CREATE TABLE lsa_info (
    bahai_cmty_id              integer  PRIMARY KEY,

    lsa_secretary              integer,
    lsa_treasurer              integer,

    lsa_member_1               integer,
    lsa_member_2               integer,
    lsa_member_3               integer,
    lsa_member_4               integer,
    lsa_member_5               integer,
    lsa_member_6               integer,
    lsa_member_7               integer,
    lsa_member_8               integer,
    lsa_member_9               integer,

    lsa_address                integer,
    lsa_phone                  varchar,
    lsa_fax                    varchar,
    lsa_email                  varchar
);


-----------------------------------------------------------------------
--  insert_bahai_community  --
--
-----------------------------------------------------------------------
CREATE or REPLACE FUNCTION insert_bahai_community(
    p_country_code             varchar,
    p_bahai_cmty_code          varchar,
    p_bahai_cmty_name          varchar,
    p_time_zone                varchar,
    p_is_lsa                   boolean,
    p_comm_website_url         varchar,
    p_bc_address_id            integer,
    p_bc_phone                 varchar,
    p_bc_fax                   varchar,
    p_bc_website_url           varchar,
    p_bahai_cluster            varchar,
    p_bahai_eu                 varchar,
    p_edit_errors_group_id     integer)
RETURNS integer as $$
DECLARE
    v_bahai_cmty_id integer;
BEGIN
    v_bahai_cmty_id := nextval('bahai_cmty_id_seq');

    INSERT into bahai_community (
        bahai_cmty_id,
        country_code,
        bahai_cmty_code,
        bahai_cmty_name,
        time_zone,
        is_lsa,
        comm_website_url,
        bc_address_id,
        bc_phone,
        bc_fax,
        bc_website_url,
        bahai_cluster,
        bahai_eu,
        edit_errors_group_id)
    VALUES (
        v_bahai_cmty_id,
        p_country_code,
        p_bahai_cmty_code,
        p_bahai_cmty_name,
        p_time_zone,
        p_is_lsa,
        p_comm_website_url,
        p_bc_address_id,
        p_bc_phone,
        p_bc_fax,
        p_bc_website_url,
        p_bahai_cluster,
        p_bahai_eu,
        p_edit_errors_group_id);

    RETURN v_bahai_cmty_id;
END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--  update_bahai_community  --
--
--
-----------------------------------------------------------------------
CREATE or REPLACE FUNCTION update_bahai_community(
    p_bahai_cmty_id           integer,
    p_country_code            varchar,
    p_bahai_cmty_code         varchar,
    p_bahai_cmty_name         varchar,
    p_time_zone               varchar,
    p_is_lsa                  boolean,
    p_comm_website_url        varchar,
    p_bc_address_id           integer,
    p_bc_phone                varchar,
    p_bc_fax                  varchar,
    p_bc_website_url          varchar,
    p_bahai_cluster           varchar,
    p_bahai_eu                varchar,
    p_edit_errors_group_id    integer)
RETURNS integer as $$
DECLARE
BEGIN

    UPDATE bahai_community
    SET
        country_code = p_country_code,
        bahai_cmty_code = p_bahai_cmty_code,
        bahai_cmty_name = p_bahai_cmty_name,
        time_zone = p_time_zone,
        is_lsa = p_is_lsa,
        comm_website_url = p_comm_website_url,
        bc_address_id = p_bc_address_id,
        bc_phone = p_bc_phone,
        bc_fax = p_bc_fax,
        bc_website_url = p_bc_website_url,
        bahai_cluster = p_bahai_cluster,
        bahai_eu = p_bahai_eu,
        edit_errors_group_id = p_edit_errors_group_id
    WHERE
        bahai_cmty_id = p_bahai_cmty_id;

    RETURN 0;
END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--  delete_bahai_community  --
--
-----------------------------------------------------------------------
CREATE or REPLACE FUNCTION delete_bahai_community(
    p_bahai_cmty_id           integer)
RETURNS integer as $$
BEGIN
    DELETE from bahai_community
    WHERE 
        bahai_cmty_id = p_bahai_cmty_id;

    RETURN 0;
END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--
-----------------------------------------------------------------------
CREATE TABLE lsa_service (
    bahai_cmty_id             integer,
    person_id                 integer,
    start_of_service          timestamp,
    end_of_service            timestamp,
    served_as                 varchar default 'Member',
    remarks                   varchar,
    PRIMARY KEY (bahai_cmty_id, person_id, start_of_service)
); 


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_bahai_cluster(
    p_cluster_code            varchar,
    p_cluster_name            varchar)
    RETURNS void as $$
DECLARE
BEGIN

    INSERT INTO bahai_cluster(cluster_code, cluster_name)
    values(p_cluster_code, p_cluster_name);

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_bahai_cluster(
    p_cluster_code            varchar,
    p_cluster_name            varchar)
    RETURNS void as $$
DECLARE
BEGIN

    UPDATE bahai_cluster
    SET  cluster_name = p_cluster_name
    WHERE cluster_code = p_cluster_code;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION delete_bahai_cluster(
    p_cluster_code            varchar)
    RETURNS void as $$
BEGIN

    DELETE FROM bahai_cluster
    WHERE cluster_code = p_cluster_code;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--  Association table :   member => atc (bahai_cluster)
-----------------------------------------------------------------------
CREATE TABLE atc_member (
    cluster_code               varchar
                               references bahai_cluster on delete cascade,
    person_id                  integer,

    PRIMARY KEY (cluster_code, person_id)
);


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION cluster_update_atc_members(
    p_cluster_code             varchar,
    p_members                  integer[])
    RETURNS void as $$
BEGIN

    DELETE from atc_member WHERE cluster_code = p_cluster_code;
   
    FOR i IN 1..array_upper(p_members, 1) LOOP
        INSERT INTO atc_member(cluster_code, person_id)
            VALUES(p_cluster_code, p_members[i]);
    END LOOP;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--
-----------------------------------------------------------------------
CREATE TABLE bahai_eu (
    bahai_eu_code              varchar,
    bahai_eu_name              varchar,
    region                     varchar,
    remarks                    varchar
); 



GRANT SELECT, INSERT, UPDATE, DELETE, REFERENCES ON
  bahai_community, lsa_info, lsa_service, bahai_cluster, atc_member, bahai_eu
    TO apache;

GRANT SELECT, UPDATE ON
  bahai_cmty_id_seq 
    TO apache;
