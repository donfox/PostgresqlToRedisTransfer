-- $Id$

-------------------------------------------------------------
CREATE SEQUENCE event_seq MINVALUE 1;
CREATE TABLE event (
    event_id                 integer   PRIMARY KEY,
    event_type_code          varchar,
    event_session            integer,
    event_address_id         integer,
    event_start_ts           timestamp,
    event_end_ts             timestamp,
    bahai_cmty_id             integer, -- Baha'i location where event took place
    host_bahai_cmty_id        integer, -- community hosted
    description              varchar,
    notes                    varchar,
    remarks                  varchar,

    edit_errors_group_id     integer
); 


-------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_event(
    p_event_type_code     varchar,
    p_event_session       integer,
    p_event_address_id    integer,
    p_event_start_ts      timestamp,
    p_event_end_ts        timestamp,
    p_bahai_cmty_id        integer, 
    p_host_bahai_cmty_id   integer, 
    p_description         varchar, 
    p_notes               varchar)
RETURNS integer as $$
DECLARE
    v_event_id  integer;
BEGIN
    v_event_id := nextval('event_seq');

    INSERT INTO event(
        event_id, 
        event_type_code,
        event_session,
        event_address_id,
        event_start_ts,
        event_end_ts,
        bahai_cmty_id,
        host_bahai_cmty_id,
        description,
        notes)
    VALUES(
        v_event_id,
        p_event_type_code,
        p_event_session,
        p_event_address_id,
        p_event_start_ts,
        p_event_end_ts,
        p_bahai_cmty_id,
        p_host_bahai_cmty_id,
        p_description,
        p_notes);


    RETURN v_event_id;

END;
$$ LANGUAGE plpgsql;


-------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_event(
    p_event_id            integer,
    p_event_type_code     varchar,
    p_event_session       integer,
    p_event_address_id    integer,
    p_event_start_ts      timestamp,
    p_event_end_ts        timestamp,
    p_bahai_cmty_id        integer, 
    p_host_bahai_cmty_id   integer, 
    p_description         varchar, 
    p_notes               varchar)
RETURNS void as $$
DECLARE
BEGIN
    UPDATE event
    SET
        event_type_code = p_event_type_code,
        event_session = p_event_session,
        event_address_id = p_event_address_id,
        event_start_ts = p_event_start_ts,
        event_end_ts = p_event_end_ts,
        bahai_cmty_id = p_bahai_cmty_id,
        host_bahai_cmty_id = p_host_bahai_cmty_id,
        description = p_description,
        notes = p_notes
    WHERE event_id = p_event_id;
END;
$$ LANGUAGE plpgsql;


-------------------------------------------------------------
CREATE OR REPLACE FUNCTION delete_event(
    p_event_id          integer)
RETURNS void as $$
DECLARE
    v_edit_errors_group_id   integer;
BEGIN

    SELECT into v_edit_errors_group_id
        edit_errors_group_id
        FROM event
        WHERE event_id = p_event_id;

    IF NOT v_edit_errors_group_id IS NULL THEN
        PERFORM delete_edit_errors_group(v_edit_errors_group_id);
    END IF;

    DELETE FROM event
        WHERE event_id = p_event_id;
END;
$$ LANGUAGE plpgsql;


-- roles are: host, tutor, facilitator, attendee
-------------------------------------------------------------
CREATE TABLE event_person (
    event_id            integer,
    person_id           integer, -- can link to 'member' OR 'non_member' table
    role                varchar,

    follow_up           boolean default false,
    follow_up_ts        timestamp,
    follow_up_action    varchar,
    remarks             varchar,

    PRIMARY KEY (event_id, person_id)
); 


-------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_update_event_person(
    p_event_id            integer,
    p_person_id           integer,
    p_role                varchar,
    p_follow_up           boolean,
    p_follow_up_ts        timestamp,
    p_follow_up_action    varchar)
RETURNS void as $$
DECLARE
    v_rec  record;
BEGIN

    SELECT into v_rec
        role, follow_up, follow_up_ts, follow_up_action
    FROM event_person
    WHERE
        event_id = p_event_id  AND
        person_id = p_person_id;

    IF FOUND THEN
        IF (v_rec.role = p_role  AND  v_rec.follow_up = p_follow_up  AND
            (v_rec.follow_up_ts = p_follow_up_ts OR
             (v_rec.follow_up_ts IS NULL AND p_follow_up_ts IS NULL))  AND
            v_rec.follow_up_action = p_follow_up_action)  THEN
            RETURN;
        END IF;

        UPDATE event_person
        SET
            role = p_role,
            follow_up = p_follow_up,
            follow_up_ts = p_follow_up_ts,
            follow_up_action = p_follow_up_action
        WHERE
            event_id = p_event_id  AND
            person_id = p_person_id;
    ELSE
        INSERT into event_person(
            event_id,
            person_id,
            role,
            follow_up,
            follow_up_ts,
            follow_up_action)
        VALUES(
            p_event_id,
            p_person_id,
            p_role,
            p_follow_up,
            p_follow_up_ts,
            p_follow_up_action);
     END IF;

END;
$$ LANGUAGE plpgsql;



-------------------------------------------------------------
CREATE OR REPLACE FUNCTION delete_event_person(
    p_event_id            integer,
    p_person_id           integer)
RETURNS void as $$
DECLARE
BEGIN

    -- Make the function tolerant of non-existence.
    PERFORM event_id from event_person 
    WHERE
        event_id = p_event_id  AND
        person_id = p_person_id;

    IF FOUND THEN
        DELETE FROM event_person
        WHERE
            event_id = p_event_id  AND
            person_id = p_person_id;
    END IF;

END;
$$ LANGUAGE plpgsql;


-------------------------------------------------------------
CREATE TABLE event_notice (
    event_id            integer,
    event_notice_ts     timestamp default now(),
    event_notice_type   varchar,
    event_notice_text   varchar,

    PRIMARY KEY (event_id, event_notice_ts)
);


-------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_event_notice(
    p_event_id            integer,
    p_event_notice_type   varchar,
    p_event_notice_text   varchar)
RETURNS TIMESTAMP as $$
DECLARE
    v_event_notice_ts   TIMESTAMP;
BEGIN

    v_event_notice_ts := now();

    INSERT INTO event_notice(
        event_id,
        event_notice_ts,
        event_notice_type,
        event_notice_text)
    VALUES (
        p_event_id,
        v_event_notice_ts,
        p_event_notice_type,
        p_event_notice_text);

    RETURN v_event_notice_ts;

END;
$$ LANGUAGE plpgsql;


-------------------------------------------------------------
CREATE TABLE event_counts (
    event_id                    integer   PRIMARY KEY,
    num_bahai_adults            smallint,
    num_bahai_youths            smallint,
    num_bahai_juniors           smallint,
    num_bahai_children          smallint,

    num_non_bahai_adults        smallint,
    num_non_bahai_youths        smallint,
    num_non_bahai_juniors       smallint,
    num_non_bahai_children      smallint,

    num_new_non_bahai_adults    smallint,
    num_new_non_bahai_youths    smallint,
    num_new_non_bahai_juniors   smallint,
    num_new_non_bahai_children  smallint
);


-------------------------------------------------------------
CREATE OR REPLACE FUNCTION event_set_counts(
    p_event_id                    integer,
    p_num_bahai_adults            smallint,
    p_num_bahai_youths            smallint,
    p_num_bahai_juniors           smallint,
    p_num_bahai_children          smallint,

    p_num_non_bahai_adults        smallint,
    p_num_non_bahai_youths        smallint,
    p_num_non_bahai_juniors       smallint,
    p_num_non_bahai_children      smallint,

    p_num_new_non_bahai_adults    smallint,
    p_num_new_non_bahai_youths    smallint,
    p_num_new_non_bahai_juniors   smallint,
    p_num_new_non_bahai_children  smallint)
RETURNS void as $$
DECLARE
BEGIN

    PERFORM event_id FROM event_counts WHERE event_id = p_event_id;

    IF FOUND THEN
        UPDATE event_counts
        SET 
            num_bahai_adults = p_num_bahai_adults,
            num_bahai_youths = p_num_bahai_youths,
            num_bahai_juniors = p_num_bahai_juniors,
            num_bahai_children = p_num_bahai_children,
            num_non_bahai_adults = p_num_non_bahai_adults,
            num_non_bahai_youths = p_num_non_bahai_youths,
            num_non_bahai_juniors = p_num_non_bahai_juniors,
            num_non_bahai_children = p_num_non_bahai_children,
            num_new_non_bahai_adults = p_num_new_non_bahai_adults,
            num_new_non_bahai_youths = p_num_new_non_bahai_youths,
            num_new_non_bahai_juniors = p_num_new_non_bahai_juniors,
            num_new_non_bahai_children = p_num_new_non_bahai_children
        WHERE  event_id = p_event_id;
    ELSE
        INSERT into event_counts(
            event_id,
            num_bahai_adults,
            num_bahai_youths,
            num_bahai_juniors,
            num_bahai_children,
            num_non_bahai_adults,
            num_non_bahai_youths,
            num_non_bahai_juniors,
            num_non_bahai_children,
            num_new_non_bahai_adults,
            num_new_non_bahai_youths,
            num_new_non_bahai_juniors,
            num_new_non_bahai_children )
        VALUES(
            p_event_id,
            p_num_bahai_adults,
            p_num_bahai_youths,
            p_num_bahai_juniors,
            p_num_bahai_children,
            p_num_non_bahai_adults,
            p_num_non_bahai_youths,
            p_num_non_bahai_juniors,
            p_num_non_bahai_children,
            p_num_new_non_bahai_adults,
            p_num_new_non_bahai_youths,
            p_num_new_non_bahai_juniors,
            p_num_new_non_bahai_children );
    END IF;

END;
$$ LANGUAGE plpgsql;



GRANT SELECT, INSERT, UPDATE, DELETE, REFERENCES ON
  event, event_person, event_notice, event_counts
    TO apache;


GRANT SELECT, UPDATE ON event_seq TO apache;
