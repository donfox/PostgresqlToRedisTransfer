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
        aka = p_aka,
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
