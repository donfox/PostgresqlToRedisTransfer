SELECT establish_session('superuser', MD5('verbus'), 'fake_session_1');

INSERT INTO bahai_cluster(cluster_code, cluster_name)
  VALUES('clust1', 'cluster #1');

INSERT INTO bahai_community(bahai_cmty_id, country_code,
                           bahai_cmty_code, bahai_cmty_name,
                           state_code, county)
  VALUES(1, 'US', 'loc1', 'test loc #1', 'CA', 'Alameda');

INSERT INTO app_user(login, password, bahai_cmty_id)
  VALUES('powerful', MD5('abc'), 1);

SELECT app_user_set_privilege('powerful', 'member',   CAST (2 as smallint));
SELECT app_user_set_privilege('powerful', 'app_user', CAST (2 as smallint));
SELECT app_user_set_privilege('powerful', 'event',    CAST (2 as smallint));

SELECT app_user_set_privilege('powerful', 'person',   CAST (2 as smallint));
