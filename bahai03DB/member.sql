-- $Id$
-- Bahai member related tables

-----------------------------------------------------------------------
CREATE TABLE member (
    person_id                integer PRIMARY KEY    -- first insert to 'person'
                               references person on delete cascade,

    -- *** PERSONAL INFORMATION ***

    last_name                varchar   NOT NULL,
    first_name               varchar   NOT NULL,
    aka                      varchar,

    is_male                  boolean,

    language                 varchar, 
    language_2nd             varchar, 

    date_of_birth            date,
    date_of_death            date,


    -- *** CONTACT INFO ***
    home_phone               varchar, 
    cell_phone               varchar, 
    fax_phone                varchar, 

    primary_phone_choice     smallint,     --  1=home,     2=work, 3=cell
    primary_email_choice     smallint,     --  1=personal, 2=work

    personal_email           varchar,
    personal_website_url     varchar,

    -- At which employers are member's preferred work phone & email?
    preferred_phone_employer varchar,
    preferred_email_employer varchar,


    --  Residential address:
    res_address_id           integer,
    res_address_ts           timestamp,
    prev_res_address_id      integer,
    prev_res_address_ts      timestamp,

    mailing_address_id       integer,
    mailing_address_ts       timestamp,
    prev_mailing_address_id  integer,
    prev_mailing_address_ts  timestamp,


    -- *** BAHAI information ***

    bahai_cmty_id             integer  REFERENCES bahai_community
                             ON DELETE CASCADE,

    -- Bahai country and personal id are required.
    -- The country id might not match that of the community or that of the
    -- address in the case of guests or member in transition.
    bahai_id_country         char(2)  default 'US',
    bahai_id                 varchar,    -- required, but not in draft

    date_became_bahai        date,
    is_deprived              boolean,

    -- The following is A, Y, J, or C
    --   Age categories:
    --      Adult         >=21
    --      youth         15-20
    --      junior youth  10-14
    --      children      <10
    age_category             char(1), -- ignored if date_of_birth is filled in

    -- *** LEGAL ***
    location_of_will         varchar, 

    attorney_name            varchar, 
    attorney_firm            varchar, 
    attorney_phone           varchar, 
    attorney_email           varchar, 
    attorney_address_id      integer, 


    -- *** EMPLOYMENT ***
    occupation               varchar,
    is_healthcare_provider   boolean,

        --  Also rows in the 'member_employment' link back to member.


    -- *** EMERGENCY information ***
         --   Rows in emergency_contact

    section_updated          varchar,
    remarks                  varchar,

    edit_errors_group_id     integer
); 

CREATE OR REPLACE RULE member_insert_rule
  AS ON INSERT TO member DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'member', NEW.person_id, 'I');

CREATE OR REPLACE RULE member_update_rule
  AS ON UPDATE TO member DO
  INSERT into change_log(session_id, table_name, row_key, trans_type, section)
    VALUES((select current_sess_id()), 'member', OLD.person_id, 'U',
            NEW.section_updated);

CREATE OR REPLACE RULE member_delete_rule
  AS ON DELETE TO member DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'member', OLD.person_id, 'D');


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_member(
    p_last_name                varchar,
    p_first_name               varchar,
    p_aka                      varchar,
    p_is_male                  boolean,
    p_language                 varchar, 
    p_language_2nd             varchar, 
    p_date_of_birth            date,
    p_date_of_death            date,
    p_bahai_cmty_id             integer,
    p_bahai_id_country         char(2),
    p_bahai_id                 varchar,
    p_date_became_bahai        date,
    p_is_deprived              boolean,
    p_age_category             char(1),
    p_occupation               varchar,
    p_is_healthcare_provider   boolean,
    p_edit_errors_group_id     integer
)
RETURNS integer as $$
DECLARE
    v_person_id      integer;
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
        CAST (1 as smallint),
        p_last_name,
        p_first_name,
        NULL,
        NULL,
        NULL,
        p_bahai_id_country,
        p_bahai_id,
        p_edit_errors_group_id);

    INSERT INTO member(
        person_id,
        last_name,
        first_name,
        aka,
        is_male,
        language, 
        language_2nd, 
        date_of_birth,
        date_of_death,
        bahai_cmty_id,
        bahai_id_country,
        bahai_id,
        date_became_bahai,
        is_deprived,
        age_category,
        occupation,
        is_healthcare_provider,
        edit_errors_group_id
        )
    VALUES(
        v_person_id,
        p_last_name,
        p_first_name,
        p_aka,
        p_is_male,
        p_language,
        p_language_2nd,
        p_date_of_birth,
        p_date_of_death,
        p_bahai_cmty_id,
        p_bahai_id_country,
        p_bahai_id,
        p_date_became_bahai,
        p_is_deprived,
        p_age_category,
        p_occupation,
        p_is_healthcare_provider,
        p_edit_errors_group_id
    );

    RETURN v_person_id;

END;
$$ LANGUAGE plpgsql;

-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION member_set_primary_phone_email(
    p_person_id                integer,
    p_primary_phone            varchar,
    p_primary_email            varchar)
RETURNS void as $$
DECLARE
    v_member     record;
BEGIN

    SELECT INTO v_member * FROM member WHERE person_id = p_person_id;

    -------------
    --  PHONE  --
    -------------
    IF (v_member.primary_phone_choice = 1  OR
        (v_member.primary_phone_choice = 2 AND 
         v_member.preferred_phone_employer IS NULL))  THEN

        UPDATE member
        SET
          home_phone = p_primary_phone
        WHERE
          person_id = p_person_id;
 
    ELSIF (v_member.primary_phone_choice = 2) THEN

        UPDATE member_employment
        SET
          member_work_phone = p_primary_phone
        WHERE
          person_id = p_person_id  AND
          employer_name = v_member.preferred_phone_employer;
 
    ELSIF (v_member.primary_phone_choice = 3) THEN

        UPDATE member
        SET
          cell_phone = p_primary_phone
        WHERE
          person_id = p_person_id;
 
    END IF;

    -------------
    --  EMAIL  --
    -------------
    IF (v_member.primary_email_choice = 1  OR
        (v_member.primary_email_choice = 2 AND 
         v_member.preferred_email_employer IS NULL))  THEN

        UPDATE member
        SET
          personal_email = p_primary_email
        WHERE
          person_id = p_person_id;
 
    ELSIF (v_member.primary_email_choice = 2) THEN

        UPDATE member_employment
        SET
          member_work_email = p_primary_email
        WHERE
          person_id = p_person_id  AND
          employer_name = v_member.preferred_email_employer;
 
    END IF;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_update_member_stub(
    p_person_id                integer,
    p_bahai_cmty_id             integer,
    p_last_name                varchar,
    p_first_name               varchar,
    p_bahai_id_country         char(2),
    p_bahai_id                 varchar,
    p_primary_phone            varchar,
    p_primary_email            varchar,
    p_address_id               integer)
RETURNS void as $$
DECLARE
    v_member     record;
BEGIN

    SELECT INTO v_member * FROM member WHERE person_id = p_person_id;

    IF FOUND THEN 
        UPDATE member
        SET
          bahai_cmty_id = p_bahai_cmty_id,
          last_name = p_last_name,
          first_name = p_first_name,
          bahai_id_country = p_bahai_id_country,
          bahai_id = p_bahai_id,
          address_id = p_address_id
        WHERE
          person_id = p_person_id;

        PERFORM member_set_primary_phone_email(p_person_id,
                p_primary_phone, p_primary_email);

    ELSE

        INSERT INTO member(
            person_id,
            bahai_cmty_id,
            last_name,
            first_name,
            home_phone,
            personal_email,
            primary_phone_choice,
            primary_email_choice,
            bahai_id_country,
            bahai_id
            )
        VALUES(
            p_person_id,
            p_bahai_cmty_id,
            p_last_name,
            p_first_name,
            p_primary_phone,
            p_primary_email,
            1,
            1,
            p_bahai_id_country,
            p_bahai_id
        );

    END IF;


END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_member(
    p_person_id                integer,
    p_last_name                varchar,
    p_first_name               varchar,
    p_aka                      varchar,
    p_is_male                  boolean,
    p_language                 varchar, 
    p_language_2nd             varchar, 
    p_date_of_birth            date,
    p_date_of_death            date,
    p_occupation               varchar,
    p_is_healthcare_provider   boolean,
    p_edit_errors_group_id     integer
)
RETURNS void as $$
DECLARE
    v_person                  record;
    v_edit_errors_group_id integer;
BEGIN

    SELECT into v_person *
        FROM person 
        WHERE person_id = p_person_id;

    IF NOT (v_person.last_name = p_last_name  AND
            v_person.first_name = p_first_name) THEN

       UPDATE person
       SET
           last_name = p_last_name,
           first_name = p_first_name
       WHERE person_id = p_person_id;

    END IF;


    ---------------------------------------------------------------
    -- CLEAR out the old edit errors for this record
    -- (from previous transaction).
    ---------------------------------------------------------------
    SELECT into v_edit_errors_group_id 
        edit_errors_group_id
        FROM member
        WHERE person_id = p_person_id;

    IF NOT v_edit_errors_group_id IS NULL THEN
        PERFORM delete_edit_errors_group(v_edit_errors_group_id);
    END IF;


    UPDATE member
    SET
        last_name = p_last_name,
        first_name = p_first_name,
        is_male = p_is_male,
        language = p_language,
        language_2nd = p_language_2nd,
        date_of_birth = p_date_of_birth,
        date_of_death = p_date_of_death,
        occupation = p_occupation,
        is_healthcare_provider = p_is_healthcare_provider,
        edit_errors_group_id = p_edit_errors_group_id
    WHERE person_id = p_person_id;

    PERFORM member_propagate_to_person(p_person_id);

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_member_bahai(
    p_person_id                integer,
    p_bahai_id_country         char(2),
    p_bahai_id                 varchar,
    p_date_became_bahai        date,
    p_is_deprived              boolean,
    p_age_category             char(1))
RETURNS void as $$
DECLARE
    v_member record;
    v_person record;
BEGIN

    SELECT into v_member
          bahai_id_country, bahai_id, date_became_bahai, is_deprived,
          age_category
        FROM member
        WHERE person_id = p_person_id;

    IF (v_member.bahai_id_country = p_bahai_id_country  AND
            v_member.bahai_id = p_bahai_id  AND
            v_member.date_became_bahai = p_date_became_bahai  AND
            v_member.is_deprived = p_is_deprived  AND
            v_member.age_category = p_age_category)  THEN
        RETURN;
    END IF;

    UPDATE member
    SET
        bahai_id_country = p_bahai_id_country,
        bahai_id = p_bahai_id,
        date_became_bahai = p_date_became_bahai,
        is_deprived = p_is_deprived,
        age_category = p_age_category
    WHERE person_id = p_person_id;

    SELECT into v_person
          bahai_id_country, bahai_id
        FROM person
        WHERE person_id = p_person_id;

    IF NOT (v_person.bahai_id_country = p_bahai_id_country  AND
            v_person.bahai_id = p_bahai_id)  THEN
        UPDATE person
        SET
            bahai_id_country = p_bahai_id_country,
            bahai_id = p_bahai_id
        WHERE person_id = p_person_id;
    END IF;

    PERFORM member_propagate_to_person(p_person_id);

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_member_contact(
    p_person_id                integer,
    p_home_phone               varchar, 
    p_cell_phone               varchar, 
    p_fax_phone                varchar, 
    p_primary_phone_choice     integer,
    p_primary_email_choice     integer,
    p_preferred_phone_employer varchar,
    p_preferred_email_employer varchar,
    p_personal_email           varchar,
    p_personal_website_url     varchar)
RETURNS void as $$
DECLARE
    v_member                   record;
    v_person                   record;
    v_primary_phone_choice     integer;
    v_primary_email_choice     integer;
    v_primary_phone            varchar;
    v_primary_email            varchar;

BEGIN

    -- 1=home, 2=work, 3=cell
    IF p_primary_phone_choice IS NULL OR p_primary_phone_choice = 0  THEN
        v_primary_phone_choice := 1;
    ELSE
        v_primary_phone_choice := p_primary_phone_choice;
    END IF;

    -- 1=personal, 2=work
    IF p_primary_email_choice IS NULL OR p_primary_email_choice = 0  THEN
        v_primary_email_choice := 1;
    ELSE
        v_primary_email_choice := p_primary_email_choice;
    END IF;

    SELECT INTO v_member
        home_phone,
        cell_phone,
        fax_phone,
        primary_phone_choice,
        primary_email_choice,
        preferred_phone_employer,
        preferred_email_employer,
        personal_email,
        personal_website_url
    FROM member
    WHERE
        person_id =            p_person_id;

    IF p_home_phone = v_member.home_phone AND
       p_cell_phone = v_member.cell_phone AND
       p_fax_phone = v_member.fax_phone AND
       v_primary_phone_choice = v_member.primary_phone_choice AND
       v_primary_email_choice = v_member.primary_email_choice AND
       p_preferred_phone_employer = v_member.preferred_phone_employer AND
       p_preferred_email_employer = v_member.preferred_email_employer AND
       p_personal_email = v_member.personal_email   AND
       p_personal_website_url = v_member.personal_website_url    THEN

        RETURN;

    END IF;


    UPDATE member
    SET
        person_id                 = p_person_id,
        home_phone                = p_home_phone,
        cell_phone                = p_cell_phone,
        fax_phone                 = p_fax_phone,
        primary_phone_choice      = v_primary_phone_choice,
        primary_email_choice      = v_primary_email_choice,
        preferred_phone_employer  = p_preferred_phone_employer,
        preferred_email_employer  = p_preferred_email_employer,
        personal_email            = p_personal_email,
        personal_website_url      = p_personal_website_url
    WHERE
        person_id =            p_person_id;

    PERFORM member_propagate_to_person(p_person_id);

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_member_legal(
    p_person_id                integer,
    p_location_of_will         varchar, 
    p_attorney_name            varchar, 
    p_attorney_firm            varchar, 
    p_attorney_phone           varchar, 
    p_attorney_email           varchar, 
    p_attorney_address_id      integer)
RETURNS void as $$
DECLARE
    v_location_of_will         varchar;
    v_attorney_name            varchar; 
    v_attorney_firm            varchar; 
    v_attorney_phone           varchar; 
    v_attorney_email           varchar; 
    v_attorney_address_id      integer;
BEGIN

    SELECT into
        v_location_of_will,
        v_attorney_name,
        v_attorney_firm,
        v_attorney_phone,
        v_attorney_email,
        v_attorney_address_id

        location_of_will,
        attorney_name,
        attorney_firm,
        attorney_phone,
        attorney_email,
        attorney_address_id
    FROM member
    WHERE
        person_id = p_person_id;


    IF p_location_of_will = v_location_of_will  AND
        p_attorney_name = v_attorney_name  AND
        p_attorney_firm = v_attorney_firm  AND
        p_attorney_phone = v_attorney_phone  AND
        p_attorney_email = v_attorney_email  AND
        (p_attorney_address_id = v_attorney_address_id OR 
         (p_attorney_address_id IS NULL AND v_attorney_address_id IS NULL)) 
            THEN

        RETURN;
    END IF;


    UPDATE member  
    SET
        location_of_will     = p_location_of_will,
        attorney_name        = p_attorney_name,
        attorney_firm        = p_attorney_firm,
        attorney_phone       = p_attorney_phone,
        attorney_email       = p_attorney_email,
        attorney_address_id  = p_attorney_address_id
    WHERE
        person_id = p_person_id;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--  Called when INSERTING a person (with category = 1 for member) 
--  OR when updating a person (where the category is changed to 1).
--  Any subsequent updates of the member should be done from the 
--  member page.
-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION member_propagate_from_person(
    p_person_id            integer,
    p_primary_phone        varchar,
    p_primary_email        varchar,
    p_contact_address_id   integer,
    p_bahai_id_country     char(2),
    p_bahai_id             varchar)
RETURNS void as $$
DECLARE
    v_edit_errors_group_id    integer;
    v_member               record;
BEGIN
    SELECT into v_member
        home_phone, cell_phone, primary_phone_choice, primary_email_choice,
        preferred_phone_employer, preferred_email_employer
      FROM member
      WHERE person_id = p_person_id;

    PERFORM update_member_contact(p_person_id,
        p_primary_phone, null, null, 1, 1, null, null, p_primary_email, null);

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--  Called when UPDATING member.
-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION member_propagate_to_person(
    p_person_id            integer)
RETURNS void as $$
DECLARE
    v_edit_errors_group_id    integer;
    v_member                  record;
    v_person                  record;
    v_phone                   varchar;
    v_email                   varchar;
BEGIN
    SELECT into v_member *
        FROM member
        WHERE
            person_id = p_person_id;

    IF v_member.primary_phone_choice = 1  THEN
        v_phone := v_member.home_phone;
    ELSIF v_member.primary_phone_choice = 3  THEN
        v_phone := v_member.cell_phone;
    ELSIF (v_member.preferred_phone_employer IS NULL) THEN
        v_phone := NULL;
    ELSE
        SELECT into v_phone 
            member_work_phone 
            FROM member_employment
            WHERE
                person_id = p_person_id AND 
                employer_name = v_member.preferred_phone_employer;
    END IF;


    IF v_member.primary_email_choice = 1  THEN
        v_email := v_member.personal_email;
    ELSIF (v_member.preferred_email_employer IS NULL) THEN
        v_email := NULL;
    ELSE
        SELECT into v_email 
            member_work_email 
            FROM member_employment
            WHERE
                person_id = p_person_id AND 
                employer_name = v_member.preferred_email_employer;
    END IF;
 

    SELECT into v_person *
        FROM person
        WHERE person_id = p_person_id;

    UPDATE person
        SET
          last_name = v_member.last_name,
          first_name = v_member.first_name,
          primary_phone = v_phone,
          primary_email = v_email,
          bahai_id = v_member.bahai_id,
          bahai_id_country = v_member.bahai_id_country
        WHERE  person_id = p_person_id;

END;
$$ LANGUAGE plpgsql;



-----------------------------------------------------------------------
--
-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION delete_member(
    p_person_id integer) 
RETURNS void as $$
DECLARE
    v_edit_errors_group_id    integer;
BEGIN
    SELECT into v_edit_errors_group_id 
        edit_errors_group_id
        FROM member
        WHERE person_id = p_person_id;

    IF NOT v_edit_errors_group_id IS NULL THEN
        PERFORM delete_edit_errors_group(v_edit_errors_group_id);
    END IF;

    DELETE FROM member
        WHERE person_id = p_person_id;

    DELETE FROM person
        WHERE person_id = p_person_id;
END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE or REPLACE FUNCTION member_new_address(
    p_is_mailing    boolean,
    p_person_id     integer,
    p_address_1     varchar,
    p_address_2     varchar,
    p_city          varchar,
    p_state_code    varchar,
    p_zip_postal    varchar,
    p_country_code  varchar)
RETURNS integer as $$    -- address_id
DECLARE
    v_address_id integer;
BEGIN
    SELECT into v_address_id insert_member_address(p_address_1, p_address_2,
            p_city, p_state_code, p_zip_postal, p_country_code);

    PERFORM member_set_address(p_is_mailing, p_person_id, v_address_id);

    RETURN v_address_id;
END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE or REPLACE FUNCTION member_clear_address(
    p_is_mailing boolean,
    p_person_id integer)
RETURNS void as $$
DECLARE
BEGIN
    IF p_is_mailing THEN
        UPDATE member
        SET 
            mailing_address_id = NULL,
            mailing_address_ts = NULL
        WHERE 
            person_id = p_person_id;
    ELSE
        UPDATE member
        SET 
            res_address_id = NULL,
            res_address_ts = NULL
        WHERE 
            person_id = p_person_id;
    END IF;
END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE or REPLACE FUNCTION member_set_address(
    p_is_mailing boolean,
    p_person_id integer,
    p_address_id integer)
RETURNS void as $$
DECLARE
    v_rec  record;
BEGIN

    SELECT into v_rec 
        res_address_id, res_address_ts, 
        prev_res_address_id, prev_res_address_ts, 
        mailing_address_id, mailing_address_ts, 
        prev_mailing_address_id, prev_mailing_address_ts
    FROM member
    WHERE person_id = p_person_id;

    IF p_is_mailing THEN
        IF (v_rec.mailing_address_id IS NULL) THEN
            UPDATE member
            SET 
                mailing_address_id = p_address_id,
                mailing_address_ts = now()
            WHERE 
                person_id = p_person_id;
        ELSE
            IF (p_address_id = v_rec.mailing_address_id) THEN
                RETURN;
            END IF;

            UPDATE member
            SET 
                prev_mailing_address_id = v_rec.mailing_address_id,
                prev_mailing_address_ts = v_rec.mailing_address_ts,
                mailing_address_id = p_address_id,
                mailing_address_ts = now()
            WHERE 
                person_id = p_person_id;
        END IF;
    ELSE
        IF (v_rec.res_address_id IS NULL) THEN
            UPDATE member
            SET 
                res_address_id = p_address_id,
                res_address_ts = now()
            WHERE 
                person_id = p_person_id;
        ELSE
            IF (p_address_id = v_rec.res_address_id) THEN
                RETURN;
            END IF;

            UPDATE member
            SET 
                prev_res_address_id = v_rec.res_address_id,
                prev_res_address_ts = v_rec.res_address_ts,
                res_address_id = p_address_id,
                res_address_ts = now()
            WHERE 
                person_id = p_person_id;
        END IF;

    END IF;
END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION person_get_age_category(
    p_person_id                integer,
    p_current_ts               timestamp)
RETURNS char(1) as $$
DECLARE
    v_member record;
    v_age_cat  char(1);
BEGIN
    SELECT into v_member  date_of_birth, age_category
        FROM member
        WHERE person_id = p_person_id;
    IF FOUND THEN
        SELECT into v_age_cat
            calc_age_category(v_member.date_of_birth, p_current_ts,
                              v_member.age_category);
    ELSE
        v_age_cat := 'A';
    END IF;

    RETURN v_age_cat;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION calc_age_category(
    p_date_of_birth            timestamp,
    p_current_ts               timestamp,
    p_age_category             char(1))
RETURNS char(1) as $$
DECLARE
    v_age    integer;
BEGIN

    IF NOT (p_date_of_birth IS NULL) THEN
        SELECT into v_age
            extract (year from age(p_current_ts, p_date_of_birth));
        IF (v_age < 10) THEN
            RETURN 'C';
        ELSIF (v_age < 15) THEN
            RETURN 'J';
        ELSIF (v_age < 21) THEN
            RETURN 'Y';
        ELSE
            RETURN 'A';
        END IF;
    ELSIF NOT (p_age_category IS NULL) THEN
        RETURN p_age_category;
    ELSE
        RETURN 'A';
    END IF;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION member_propagate_address(
    p_from_person_id integer,
    p_to_person_ids integer[],
    p_include_residence boolean,
    p_include_mailing boolean)
RETURNS void as $$
DECLARE
    v_from_member RECORD;
    i integer;
BEGIN
    SELECT INTO v_from_member address_id, mailing_address_id
      FROM member 
      WHERE person_id = p_from_person_id;

    i := 1;
    WHILE NOT p_to_person_ids[i] IS NULL LOOP
       IF p_include_residence THEN
           PERFORM member_set_address(false, p_to_person_ids[i],
                   v_from_member.address_id);
       END IF;
       IF p_include_mailing THEN
           PERFORM member_set_address(true, p_to_person_ids[i],
                   v_from_member.mailing_address_id);
       END IF;
       i := i + 1;
    END LOOP;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--
-----------------------------------------------------------------------
CREATE TABLE member_employment (
    person_id         integer   NOT NULL REFERENCES member on delete cascade,

    employer_name     varchar NOT NULL,
    employer_addr_id  integer,
    employer_phone    varchar,

    member_work_phone varchar, 
    member_work_email varchar,

    PRIMARY KEY (person_id, employer_name)
);


-----------------------------------------------------------------------
--
-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_member_employment(
    p_person_id             integer,
    p_employer_name         varchar,
    p_employer_addr_id      integer,
    p_employer_phone        varchar,
    p_member_work_phone     varchar, 
    p_member_work_email     varchar)
RETURNS void as $$
DECLARE
BEGIN

    INSERT into member_employment(
        person_id,
        employer_name,
        employer_addr_id,
        employer_phone,
        member_work_phone,
        member_work_email )
    VALUES(
        p_person_id,
        p_employer_name,
        p_employer_addr_id,
        p_employer_phone,
        p_member_work_phone,
        p_member_work_email );

END;
$$ LANGUAGE plpgsql;
   

-----------------------------------------------------------------------
--
-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_member_employment(
    p_person_id             integer,
    p_employer_name         varchar,
    p_employer_phone        varchar,
    p_member_work_phone     varchar, 
    p_member_work_email     varchar)
RETURNS void as $$
DECLARE
BEGIN

    UPDATE member_employment
    SET 
        employer_phone = p_employer_phone,
        member_work_phone = p_member_work_phone,
        member_work_email = p_member_work_email 
    WHERE
        person_id = p_person_id  AND  employer_name = p_employer_name;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--
-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION delete_member_employment(
    p_person_id             integer,
    p_employer_name         varchar)
RETURNS void as $$
DECLARE
    v_employer_addr_id  int4;
BEGIN

    SELECT into v_employer_addr_id employer_addr_id 
    FROM member_employment
    WHERE
        person_id = p_person_id  AND  employer_name = p_employer_name;

    IF FOUND THEN 
        DELETE FROM address
        WHERE address_id = v_employer_addr_id;
    END IF;

    DELETE FROM member_employment
    WHERE
        person_id = p_person_id  AND  employer_name = p_employer_name;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--
-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_employer_address(
    p_person_id             integer,
    p_employer_name         varchar,
    p_employer_addr_id      integer)
RETURNS void as $$
DECLARE
BEGIN

    UPDATE member_employment
    SET 
        employer_addr_id = p_employer_addr_id
    WHERE
        person_id = p_person_id  AND  employer_name = p_employer_name;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--
-----------------------------------------------------------------------
CREATE TABLE emergency_contact (
    person_id              integer REFERENCES member on delete cascade,
    rel_person_id          integer REFERENCES person on delete cascade,
    relationship           varchar,
    contact_order_num      smallint   -- ignored for now

);


-------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_update_emergency_contact(
    p_person_id            integer,
    p_rel_person_id        integer,
    p_relationship         varchar)
RETURNS void as $$
DECLARE
    v_rec  record;
BEGIN

    SELECT into v_rec
        relationship
    FROM emergency_contact
    WHERE
        person_id = p_person_id  AND
        rel_person_id = p_rel_person_id;

    IF FOUND THEN
        IF (v_rec.relationship = p_relationship)  THEN
            RETURN;
        END IF;

        UPDATE emergency_contact
        SET
            relationship = p_relationship
        WHERE
            person_id     = p_person_id    AND
            rel_person_id = p_rel_person_id;
    ELSE
        INSERT into emergency_contact(
            person_id,
            rel_person_id,
            relationship)
        VALUES(
            p_person_id,
            p_rel_person_id,
            p_relationship);
     END IF;

END;
$$ LANGUAGE plpgsql;



-------------------------------------------------------------
CREATE OR REPLACE FUNCTION delete_emergency_contact(
    p_person_id           integer,
    p_rel_person_id           integer)
RETURNS void as $$
DECLARE
BEGIN

    -- Make the function tolerant of non-existence.
    PERFORM person_id from emergency_contact 
    WHERE
        person_id = p_person_id  AND
        person_id = p_person_id;

    IF FOUND THEN
        DELETE FROM emergency_contact
        WHERE
            person_id = p_person_id  AND
            person_id = p_person_id;
    END IF;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--  MUST PASS ALL CONTACTS for a member.
-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION reorder_emergency_contacts(
    p_person_id            integer,
    p_rel_person_id_list   integer[])
RETURNS void as $$
DECLARE
BEGIN
    FOR i IN 1..array_upper(p_person_id_list, 1) LOOP
        UPDATE emergency_contact
        SET contact_order_num = i
        WHERE person_id = p_person_id  AND 
              rel_person_id = p_rel_person_id_list[i];
    END LOOP;

END;
$$ LANGUAGE plpgsql;



GRANT SELECT, INSERT, UPDATE, DELETE, REFERENCES ON
  member, member_employment, emergency_contact
    TO apache;
