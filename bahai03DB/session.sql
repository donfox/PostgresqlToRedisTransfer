-- $Id$
--  Basic Infrastructure and Session handling schema

--SET min_log_messages TO WARNING;

--CREATE LANGUAGE plpgsql;

-----------------------------------------------------------------------
CREATE TABLE admin (
    id                   integer  PRIMARY KEY, 
    version              varchar, 
    version_ts           timestamp,
    remarks              varchar
); 
insert into admin (id,version,version_ts) 
values (1,'Version 0.3', current_timestamp);


--------------------------------------------------------------------------
CREATE SEQUENCE db_error_id_seq MINVALUE 1;

CREATE TABLE db_error_log(
    db_error_id          integer PRIMARY KEY,
    ts                   timestamp  default now(),
    query                varchar,
    error_msg            varchar
);

CREATE OR REPLACE FUNCTION log_db_error(
    p_query              varchar,
    p_error_msg          varchar)
RETURNS integer as $$
DECLARE
    v_db_error_id   integer;
BEGIN
    v_db_error_id := nextval('db_error_id_seq');

    INSERT INTO db_error_log(db_error_id, query, error_msg)
        VALUES(v_db_error_id, p_query, p_error_msg);

    RETURN v_db_error_id;
END;
$$ LANGUAGE plpgsql;


--------------------------------------------------------------------------
CREATE TABLE feedback(
    ts                   timestamp default now(),
    login                varchar,
    feedback_text        varchar,
    PRIMARY KEY(ts, login)
);

CREATE OR REPLACE FUNCTION leave_feedback(
    p_feedback_text      varchar)
RETURNS void as $$
DECLARE
    v_login    varchar;
    v_sess_id  varchar;
BEGIN
    
    SELECT into v_sess_id  sess_id FROM session_id;
    SELECT into v_login  login FROM app_session 
      WHERE session_id = v_sess_id;

    INSERT INTO feedback(login, feedback_text)
      VALUES(v_login, p_feedback_text);

END;
$$ LANGUAGE plpgsql;



--------------------------------------------------------------------------
--  A row is inserted for each insert/update/delete of the user and
--  application data tables.
--------------------------------------------------------------------------
CREATE TABLE change_log (
    ts                   timestamp  default now(),
    table_name           varchar,
    row_key              varchar,
    section              varchar,   -- often left null
    trans_type           char(1),
        CHECK (trans_type = 'I' or trans_type = 'U' or trans_type = 'D'),
        -- (for Insert, Update, Delete)
    session_id           varchar
);


--------------------------------------------------------------------------
--  'current_sess_id'  -- 
--
--  A function to encapsulate global access to the session id in the
--  database session.
--  It reads (and thus depends upon the creation of) a temporary table
--  storing the session id; this table is created by the functions
--  'establish_session' and 'refresh_session'.
--------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION current_sess_id() RETURNS varchar as $$
DECLARE
    v_sess_id varchar;
BEGIN
    SELECT into v_sess_id sess_id from session_id;
    IF NOT FOUND THEN
        RAISE EXCEPTION 'SESSION PROBLEM';
    END IF;

    RETURN v_sess_id;
END;
$$ LANGUAGE plpgsql;


--------------------------------------------------------------------------
--
--------------------------------------------------------------------------
CREATE SEQUENCE edit_errors_group_id_seq MINVALUE 1;


--------------------------------------------------------------------------
--
--
--------------------------------------------------------------------------
CREATE TABLE edit_errors_group (
    edit_errors_group_id      integer PRIMARY KEY,
    session_id                varchar,
    datatype                  varchar,
    row_descriptor            varchar
);


--------------------------------------------------------------------------
--
--
--------------------------------------------------------------------------
CREATE TABLE edit_error (
    edit_errors_group_id      integer,
    edit_error_num            integer,
    message                   varchar,
    context                   varchar,

    PRIMARY KEY(edit_errors_group_id, edit_error_num)
);


--------------------------------------------------------------------------
--------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_edit_errors_group(
    p_datatype         varchar,
    p_row_descriptor   varchar)
RETURNS integer as $$
DECLARE
    v_edit_errors_group_id  integer;
    v_sess_id               varchar;
BEGIN

    v_edit_errors_group_id := nextval('edit_errors_group_id_seq');

    v_sess_id := current_sess_id();

    INSERT INTO edit_errors_group(
        edit_errors_group_id,
        session_id,
        datatype,  
        row_descriptor)
    VALUES(
        v_edit_errors_group_id,
        v_sess_id,
        p_datatype,
        p_row_descriptor);

    RETURN v_edit_errors_group_id;

END;
$$ LANGUAGE plpgsql;



--------------------------------------------------------------------------
--------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION add_edit_error(
    p_edit_errors_group_id  integer,
    p_message               varchar,
    p_context               varchar)
RETURNS void as $$
DECLARE
    v_edit_error_num   integer;
BEGIN

    v_edit_error_num := 0;

    SELECT INTO v_edit_error_num  MAX(edit_error_num)
        FROM edit_error 
        WHERE edit_errors_group_id = p_edit_errors_group_id;

    IF v_edit_error_num is NULL  THEN
        v_edit_error_num := 0;
    END IF;

    v_edit_error_num := v_edit_error_num + 1;

    INSERT INTO edit_error(
            edit_errors_group_id,
            edit_error_num,
            message,
            context)
        VALUES(
            p_edit_errors_group_id,
            v_edit_error_num,
            p_message,
            p_context);

END;
$$ LANGUAGE plpgsql;


--------------------------------------------------------------------------
--------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION delete_edit_errors_group(
    p_edit_errors_group_id integer)
RETURNS void as $$
BEGIN

    DELETE FROM edit_errors_group
        WHERE edit_errors_group_id = p_edit_errors_group_id;
    DELETE FROM edit_error
        WHERE edit_errors_group_id = p_edit_errors_group_id;

END;
$$ LANGUAGE plpgsql;


--************************************
--**                                **
--**     USERS/SESSION HANDLING     **
--**                                **
--************************************

--------------------------------------------------------------------------
--  A row is inserted each time a user is logged in.
--  This table is used in session security as well as for historical record.
--------------------------------------------------------------------------
CREATE TABLE app_session (
    session_id         varchar  PRIMARY KEY,
    session_start_ts   timestamp default now(),
    session_end_ts     timestamp,
    login              varchar     -- key to app_user, but NULL for superuser
);


-- PASSWORDS are encoded with MD5 before putting in the database.

-----------------------------------------------------------------------
-- The 'superuser' is the unchangeable login name for creator of
-- mortal app_user entries.
-- The 'superuser' password cannot be changed over the web.
CREATE TABLE superuser (
    login      varchar PRIMARY KEY 
        DEFAULT 'superuser'  CHECK(login='superuser'),
        --  Guarantees 'superuser' unchanged and only one row.
    password   varchar  NOT NULL  DEFAULT MD5('verbus') 
);
INSERT into superuser default values;


-----------------------------------------------------------------------
--  The 'app_user' (application user) table contains login information
--  for mortal users whose scope restricted to a particular bahai community.
--  This would be used for secretary, treasurer, etc.
--  In addition there are access levels for member information and for 
--  financial information.
--  Users can be configured to be able to create other users at their
--  same community, but those users' access cannot exceed that of the
--  their creator.
--  Each user can change their password (after logging in, of course).
--  Also, the password for a user can only be reset by the superuser OR
--  his/her creator (which will often be the superuser).
CREATE TABLE app_user (
    login                  varchar PRIMARY KEY UNIQUE
                           CHECK (NOT login = 'select login from superuser'),

    password               varchar  NOT NULL,
    password_old           varchar,  -- to avoid alternating between 2 pwds
    
    bahai_cmty_id          integer   NOT NULL,

    created_by             varchar,
                           -- NULL means superuser, otherwise key to app_user

    full_name              varchar,

    email                  varchar,

    -- Timestamps are required to be able to distinguish password change 
    -- from other updates.
    update_ts              timestamp,  -- not changed when password changed
    password_change_ts     timestamp,

    remarks                varchar,

    edit_errors_group_id   integer
); 

CREATE OR REPLACE RULE app_user_insert_rule
  AS ON INSERT TO app_user DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'app_user', NEW.login, 'I');

CREATE OR REPLACE RULE app_user_update_rule
  AS ON UPDATE TO app_user WHERE (NEW.update_ts IS NOT NULL AND
     (OLD.update_ts IS NULL OR NEW.update_ts <> OLD.update_ts))
  DO
  INSERT into change_log(session_id, table_name, row_key, trans_type, ts)
    VALUES( (select current_sess_id()), 'app_user', NEW.login, 'U',
    NEW.update_ts);

CREATE OR REPLACE RULE app_user_password_rule
  AS ON UPDATE TO app_user WHERE NEW.update_ts = OLD.update_ts DO
  INSERT into change_log(session_id, table_name, row_key, trans_type, ts)
    VALUES( (select current_sess_id()), 'app_user', NEW.login, 'U',
    NEW.password_change_ts);

CREATE OR REPLACE RULE app_user_delete_rule
  AS ON DELETE TO app_user DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'app_user', OLD.login, 'D');

-----------------------------------------------------------------------
--  insert_app_user  --
-----------------------------------------------------------------------
CREATE or REPLACE FUNCTION insert_app_user(
    p_login                 varchar,
    p_bahai_cmty_id         integer,
    p_full_name             varchar,
    p_email                 varchar,
    p_password              varchar,  -- expected to be passed in MD5 encoded
    p_edit_errors_group_id  integer)
    RETURNS void as $$
BEGIN
    INSERT into app_user(login, bahai_cmty_id, full_name, email, password, 
           edit_errors_group_id)
    VALUES(p_login, p_bahai_cmty_id, p_full_name, p_email, p_password,
           p_edit_errors_group_id);
END;
$$ LANGUAGE plpgsql;



-----------------------------------------------------------------------
--  update_app_user  --
-----------------------------------------------------------------------
CREATE or REPLACE FUNCTION update_app_user(
    p_login                 varchar,
    p_full_name             varchar,
    p_email                 varchar,
    p_edit_errors_group_id  integer)
    RETURNS void as $$
BEGIN
    UPDATE app_user 
    SET
        full_name = p_full_name,
        email = p_email,
        edit_errors_group_id = p_edit_errors_group_id,
        update_ts = now()
    WHERE login = p_login;
END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--  delete_app_user  --
-----------------------------------------------------------------------
CREATE or REPLACE FUNCTION delete_app_user(
    p_login     varchar)
    RETURNS void as $$
BEGIN
    DELETE FROM app_user WHERE login = p_login;
END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--
-----------------------------------------------------------------------
CREATE TABLE app_user_privilege(
    login                   varchar,
    domain                  varchar,
    privilege_level         smallint  default 0,  -- meaning varies by domain
    PRIMARY KEY(login,domain)
);


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION app_user_set_privilege(
    p_login                 varchar,
    p_domain                varchar,
    p_privilege_level       smallint)
    RETURNS void as $$
DECLARE
    v_privilege_level    smallint;
BEGIN
    SELECT INTO v_privilege_level privilege_level
    FROM app_user_privilege
    WHERE login = p_login AND domain = p_domain;

    IF FOUND THEN
        IF (v_privilege_level != p_privilege_level) THEN 
            UPDATE app_user_privilege
            SET privilege_level = p_privilege_level
            WHERE login = p_login  AND  domain = p_domain;
        END IF;
    ELSE
        IF (p_privilege_level > 0) THEN
            INSERT INTO app_user_privilege(login,domain,privilege_level)
            VALUES(p_login,p_domain,p_privilege_level);
        END IF;
    END IF;

END;
$$ LANGUAGE plpgsql;


----------------------------------------------------------------------
--  password_is_ok
----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION password_is_ok(
    p_login                 varchar,
    p_password              varchar)  -- expected to be encoded with MD5
    RETURNS boolean as $$
DECLARE
    v_password varchar;
    v_password_old varchar;
    v_ts timestamp;
BEGIN
    SELECT INTO v_password, v_password_old
        password, password_old
        FROM app_user WHERE login = p_login;

    IF  p_password = v_password  OR  p_password = v_password_old THEN
        RETURN FALSE;
    ELSE 
        RETURN TRUE;
    END IF;

END;
$$ LANGUAGE plpgsql;


----------------------------------------------------------------------
--  change_password  --
----------------------------------------------------------------------
CREATE or REPLACE FUNCTION change_password(
    p_login                 varchar,
    p_password              varchar)  -- expected to be encoded with MD5
    RETURNS void as $$
DECLARE
    v_password varchar;
    v_password_old varchar;
    v_ts timestamp;
BEGIN
    SELECT INTO v_password, v_password_old
        password, password_old
        FROM app_user WHERE login = p_login;

    IF  p_password = v_password  OR  p_password = v_password_old THEN
        RAISE EXCEPTION 'Cannot reuse old password';
        RETURN;
    END IF;

    UPDATE app_user
    SET
        password_old = v_password,
        password = p_password,
        password_change_ts = now()
    WHERE login = p_login;
END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE or REPLACE VIEW app_user_creator(
    login,
    creator) as 
SELECT 
    au.login,
    as1.login as creator
FROM 
    app_user as au
      LEFT OUTER JOIN change_log as ch on (au.login = ch.row_key and
        ch.table_name = 'app_user' and trans_type = 'I')
      LEFT OUTER JOIN app_session as as1 on (as1.session_id = ch.session_id);


-----------------------------------------------------------------------
CREATE or REPLACE VIEW app_user_mods(
    login,
    create_ts,
    create_user,
    update_ts,
    update_user,
    password_change_ts,
    password_change_user) as 

SELECT 
    au.login,
    ch1.ts as create_ts,
    as1.login as create_user,
    au.update_ts,
    as2.login as update_user,
    au.password_change_ts,
    as3.login as password_change_user
FROM 
    app_user as au
      LEFT OUTER JOIN change_log as ch1 on (au.login = ch1.row_key and
        ch1.table_name = 'app_user' and trans_type = 'I')
        LEFT OUTER JOIN app_session as as1 on (as1.session_id = ch1.session_id)
      LEFT OUTER JOIN change_log ch2 on (au.update_ts = ch2.ts)
        LEFT OUTER JOIN app_session as as2 on (as2.session_id = ch2.session_id)
      LEFT OUTER JOIN change_log ch3 on (au.password_change_ts = ch3.ts)
        LEFT OUTER JOIN app_session as as3 on (as3.session_id = ch3.session_id);



-----------------------------------------------------------------------
--  save_session_id
--
-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION save_session_id(p_sess_id varchar)
        RETURNS void as $$
BEGIN
    CREATE TEMP TABLE session_id(sess_id varchar);
    INSERT INTO session_id(sess_id) values(p_sess_id);
END;
$$ LANGUAGE plpgsql;
    
        

-----------------------------------------------------------------------
--  establish_session --
--
--  1) Verify the correct login/password combo
--  2) Insert a record for the session into the 'app_session' table.
--  3) Create the TEMPORARY table 'session_id'.
--  4) RETURN the timestamp of the previous session
--     (or NULL if this is the first login).
--
--  If the login/password is incorrect, an EXCEPTION is raised.
-----------------------------------------------------------------------
CREATE or REPLACE FUNCTION establish_session(
    p_login              varchar,
    p_password           varchar,
    p_sess_id            varchar)
RETURNS timestamp as $$
DECLARE
    v_login              varchar;
    v_password           varchar;
    v_previous_login_ts  timestamp;
BEGIN
    -- Check to see if there's a match (by login) for the superuser.
    -- If it is an attempt at logging in as superuser, then regardless of
    -- whether there's a password match, we don't want to try the 'app_user'
    -- table.
    SELECT password FROM superuser INTO v_password
    WHERE superuser.login = p_login;

    IF FOUND THEN
        IF (v_password != p_password) THEN
            RAISE EXCEPTION 'Invalid Login/Password';
        END IF;
        v_login := NULL;

    --  Otherwise if this is another user, just check to see if the
    --  login/password is valid.
    ELSE
        PERFORM login
        FROM app_user
        WHERE
            app_user.login = p_login  AND
            app_user.password = p_password;

        IF NOT FOUND  THEN
            RAISE EXCEPTION 'Invalid Login/Password';
        END IF;

        v_login := p_login;
    END IF;

    PERFORM end_session(p_sess_id);

    --  For a successful login, insert a record into 'app_session' for
    --  this session.
    --  But first, we need to read the starting timestamp from the
    --  last session for this user.
    IF (v_login IS NULL) THEN
        SELECT session_start_ts
        FROM app_session
            INTO v_previous_login_ts
            WHERE login IS NULL
            ORDER BY session_start_ts DESC
            LIMIT 1;
    ELSE
        SELECT session_start_ts
        FROM app_session
            INTO v_previous_login_ts
            WHERE login = p_login 
            ORDER BY session_start_ts DESC
            LIMIT 1;
    END IF;

    IF NOT FOUND THEN
        v_previous_login_ts := NULL;
    END IF;

    INSERT into app_session (session_id, login)
    VALUES (p_sess_id, v_login);

    --
    PERFORM save_session_id(p_sess_id);

    RETURN v_previous_login_ts;
END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--  refresh_session  --
--
--  Returns TRUE if the given IP address matches the one that started
--  the given session.
--  As a protection against session stealing, each PHP invocation
--  must call this function and abort if it returns FALSE.
--  The TEMPORARY table 'session_id' is created.
-----------------------------------------------------------------------
CREATE or REPLACE FUNCTION refresh_session(p_session_id  varchar)
        RETURNS void as $$
BEGIN
    PERFORM
        s.session_id
    FROM
        app_session s
    WHERE
        s.session_id = p_session_id  AND
        s.session_end_ts IS NULL;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Invalid Login/Password';
    END IF;

    PERFORM save_session_id(p_session_id);
END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--  end_session  --
--
--  To logoff.
-----------------------------------------------------------------------
CREATE or REPLACE FUNCTION end_session(p_session_id varchar)
        RETURNS void as $$
BEGIN
    UPDATE app_session
    SET
        session_end_ts = now()
    WHERE 
        session_id = p_session_id  AND
        session_end_ts is NULL;
END;
$$ LANGUAGE plpgsql;


CREATE or REPLACE FUNCTION session_is_superuser() 
        RETURNS boolean as $$
BEGIN
    PERFORM login from app_session where session_id = 
        (SELECT current_sess_id()) and login is null;

    RETURN FOUND;
END;
$$ LANGUAGE plpgsql;


GRANT SELECT, INSERT, UPDATE, DELETE, REFERENCES ON
  admin, change_log, app_session, superuser, app_user, app_user_privilege,
  edit_errors_group, edit_error, db_error_log
    TO apache;

GRANT SELECT, INSERT ON feedback TO apache;

GRANT SELECT ON
  app_user_mods, app_user_creator
    TO apache;

GRANT SELECT, UPDATE ON edit_errors_group_id_seq, db_error_id_seq TO apache;


--GRANT EXECUTE ON
--  current_sess_id, insert_app_user, update_app_user, delete_app_user,
--  change_password, save_session_id, establish_session, refresh_session,
--  end_session, session_is_superuser
--    TO apache;

