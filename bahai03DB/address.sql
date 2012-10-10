-- $Id$

CREATE SEQUENCE country_display_order_seq MINVALUE 1;
-----------------------------------------------------------------------
CREATE TABLE country (
    country_code         char(2) PRIMARY KEY,
    country_name         varchar NOT NULL,
    display_order        integer default nextval('country_display_order_seq')
); 

COPY country(country_code,country_name) FROM stdin DELIMITER ':';
US:UNITED STATES
AF:AFGHANISTAN
AX:ALAND ISLANDS
DZ:ALGERIA
AS:AMERICAN SAMOA
AD:ANDORRA
AO:ANGOLA
AI:ANGUILLA
AQ:ANTARCTICA
AG:ANTIGUA AND BARBUDA
AR:ARGENTINA
AM:ARMENIA
AW:ARUBA
AU:AUSTRALIA
AT:AUSTRIA
AZ:AZERBAIJAN
BS:BAHAMAS
BH:BAHRAIN
BD:BANGLADESH
BB:BARBADOS
BY:BELARUS
BE:BELGIUM
BZ:BELIZE
BJ:BENIN
BM:BERMUDA
BT:BHUTAN
BO:BOLIVIA
BA:BOSNIA AND HERZEGOVINA
BW:BOTSWANA
BV:BOUVET ISLAND
BR:BRAZIL
IO:BRITISH INDIAN OCEAN TERR.
BN:BRUNEI DARUSSALAM
BG:BULGARIA
BF:BURKINA FASO
BI:BURUNDI
KH:CAMBODIA
CM:CAMEROON
CA:CANADA
CV:CAPE VERDE
KY:CAYMAN ISLANDS
CF:CENTRAL AFRICAN REPUBLIC
TD:CHAD
CL:CHILE
CN:CHINA
CX:CHRISTMAS ISLAND
CC:COCOS (KEELING) ISLANDS
CO:COLOMBIA
KM:COMOROS
CG:CONGO
CD:CONGO, DEMOCRATIC REPUBLIC OF
CK:COOK ISLANDS
CR:COSTA RICA
CI:COTE DIVOIRE
HR:CROATIA
CU:CUBA
CY:CYPRUS
CZ:CZECH REPUBLIC
DK:DENMARK
DJ:DJIBOUTI
DM:DOMINICA
DO:DOMINICAN REPUBLIC
EC:ECUADOR
EG:EGYPT
SV:EL SALVADOR
GQ:EQUATORIAL GUINEA
ER:ERITREA
EE:ESTONIA
ET:ETHIOPIA
FK:FALKLAND ISLANDS (MALVINAS)
FO:FAROE ISLANDS
FJ:FIJI
FI:FINLAND
FR:FRANCE
GF:FRENCH GUIANA
PF:FRENCH POLYNESIA
TF:FRENCH SOUTHERN TERRITORIES
GA:GABON
GM:GAMBIA
GE:GEORGIA
DE:GERMANY
GH:GHANA
GI:GIBRALTAR
GR:GREECE
GL:GREENLAND
GD:GRENADA
GP:GUADELOUPE
GU:GUAM
GT:GUATEMALA
GN:GUINEA
GW:GUINEA-BISSAU
GY:GUYANA
HT:HAITI
HM:HEARD ISLAND AND MCDONALD ISL
VA:VATICAN CITY
HN:HONDURAS
HK:HONG KONG
HU:HUNGARY
IS:ICELAND
IN:INDIA
ID:INDONESIA
IR:IRAN, ISLAMIC REPUBLIC OF
IQ:IRAQ
IE:IRELAND
IL:ISRAEL
IT:ITALY
JM:JAMAICA
JP:JAPAN
JO:JORDAN
KZ:KAZAKHSTAN
KE:KENYA
KI:KIRIBATI
KP:KOREA, DEMOCRATIC PEOPLES REP.
KR:KOREA, REPUBLIC OF
KW:KUWAIT
KG:KYRGYZSTAN
LA:LAO PEOPLES DEMOCRATIC REPUB
LV:LATVIA
LB:LEBANON
LS:LESOTHO
LR:LIBERIA
LY:LIBYAN ARAB JAMAHIRIYA
LI:LIECHTENSTEIN
LT:LITHUANIA
LU:LUXEMBOURG
MO:MACAO
MK:MACEDONIA, FORMER YUGOSLAV REP.
MG:MADAGASCAR
MW:MALAWI
MY:MALAYSIA
MV:MALDIVES
ML:MALI
MT:MALTA
MH:MARSHALL ISLANDS
MQ:MARTINIQUE
MR:MAURITANIA
MU:MAURITIUS
YT:MAYOTTE
MX:MEXICO
FM:MICRONESIA, FED. STATES OF
MD:MOLDOVA, REPUBLIC OF
MC:MONACO
MN:MONGOLIA
MS:MONTSERRAT
MA:MOROCCO
MZ:MOZAMBIQUE
MM:MYANMAR
NA:NAMIBIA
NR:NAURU
NP:NEPAL
NL:NETHERLANDS
AN:NETHERLANDS ANTILLES
NC:NEW CALEDONIA
NZ:NEW ZEALAND
NI:NICARAGUA
NE:NIGER
NG:NIGERIA
NU:NIUE
NF:NORFOLK ISLAND
MP:NORTHERN MARIANA ISLANDS
NO:NORWAY
OM:OMAN
PK:PAKISTAN
PW:PALAU
PS:PALESTINIAN TERRITORY, OCCUP
PA:PANAMA
PG:PAPUA NEW GUINEA
PY:PARAGUAY
PE:PERU
PH:PHILIPPINES
PN:PITCAIRN
PL:POLAND
PT:PORTUGAL
PR:PUERTO RICO
QA:QATAR
RE:REUNION
RO:ROMANIA
RU:RUSSIAN FEDERATION
RW:RWANDA
SH:SAINT HELENA
KN:SAINT KITTS AND NEVIS
LC:SAINT LUCIA
PM:SAINT PIERRE AND MIQUELON
VC:SAINT VINCENT AND GRENADINES
WS:SAMOA
SM:SAN MARINO
ST:SAO TOME AND PRINCIPE
SA:SAUDI ARABIA
SN:SENEGAL
CS:SERBIA AND MONTENEGRO
SC:SEYCHELLES
SL:SIERRA LEONE
SG:SINGAPORE
SK:SLOVAKIA
SI:SLOVENIA
SB:SOLOMON ISLANDS
SO:SOMALIA
ZA:SOUTH AFRICA
GS:S. GEORGIA AND S. SANDWICH ISL.
ES:SPAIN
LK:SRI LANKA
SD:SUDAN
SR:SURINAME
SJ:SVALBARD AND JAN MAYEN
SZ:SWAZILAND
SE:SWEDEN
CH:SWITZERLAND
SY:SYRIAN ARAB REPUBLIC
TW:TAIWAN, PROVINCE OF CHINA
TJ:TAJIKISTAN
TZ:TANZANIA, UNITED REPUBLIC OF
TH:THAILAND
TL:TIMOR-LESTE
TG:TOGO
TK:TOKELAU
TO:TONGA
TT:TRINIDAD AND TOBAGO
TN:TUNISIA
TR:TURKEY
TM:TURKMENISTAN
TC:TURKS AND CAICOS ISLANDS
TV:TUVALU
UG:UGANDA
UA:UKRAINE
AE:UNITED ARAB EMIRATES
GB:UNITED KINGDOM
UM:U.S. MINOR OUTLYING ISLANDS
UY:URUGUAY
UZ:UZBEKISTAN
VU:VANUATU
VE:VENEZUELA
VN:VIET NAM
VG:VIRGIN ISLANDS, BRITISH
VI:VIRGIN ISLANDS, U.S.
WF:WALLIS AND FUTUNA
EH:WESTERN SAHARA
YE:YEMEN
ZM:ZAMBIA
ZW:ZIMBABWE 
\.


CREATE SEQUENCE state_display_order_seq MINVALUE 1;
-----------------------------------------------------------------------
--  For now, the application is 'US' only.
CREATE TABLE state (
    state_code           char(2) PRIMARY KEY,
    state_name           varchar NOT NULL,
    display_order        integer default nextval('state_display_order_seq')
); 

COPY state(state_code,state_name) FROM stdin DELIMITER ':';
AL:ALABAMA
AK:ALASKA
AS:AMERICAN SAMOA
AZ:ARIZONA
AR:ARKANSAS
CA:CALIFORNIA
CO:COLORADO
CT:CONNECTICUT
DE:DELAWARE
DC:DISTRICT OF COLUMBIA
FM:FEDERATED STATES OF MICRONESIA
FL:FLORIDA
GA:GEORGIA
GU:GUAM
HI:HAWAII
ID:IDAHO
IL:ILLINOIS
IN:INDIANA
IA:IOWA
KS:KANSAS
LA:LOUISIANA
ME:MAINE
MH:MARSHALL ISLANDS
MD:MARYLAND
MA:MASSACHUSETTS
MI:MICHIGAN
MN:MINNESOTA
MS:MISSISSIPPI
MO:MISSOURI
MT:MONTANA
NE:NEBRASKA
NV:NEVADA
NH:NEW HAMPSHIRE
NJ:NEW JERSEY
NM:NEW MEXICO
NY:NEW YORK
NC:NORTH CAROLINA
ND:NORTH DAKOTA
MP:NORTHERN MARIANA ISLANDS
OH:OHIO
OK:OKLAHOMA
OR:OREGON
PW:PALAU
PA:PENNSYLVANIA
PR:PUERTO RICO
RI:RHODE ISLAND
SC:SOUTH CAROLINA
SD:SOUTH DAKOTA
TN:TENNESSEE
TX:TEXAS
UT:UTAH
VT:VERMONT
VI:VIRGIN ISLANDS
VA:VIRGINIA
WA:WASHINGTON
WV:WEST VIRGINIA
WI:WISCONSIN
WY:WYOMING
AE:Arm Frc Afr/Canada/Eur/MidEast
AA:Armed Forces Americas
AP:Armed Forces Pacific
\.


-----------------------------------------------------------------------
CREATE SEQUENCE address_id_seq MINVALUE 1;
CREATE TABLE address (
    address_id       integer NOT NULL default nextval('address_id_seq'),
    address_1        varchar,
    address_2        varchar, 
    city             varchar,
    state_code       char(2),
    zip_postal       varchar,
    country_code     char(2) REFERENCES country,
    remarks          varchar
); 

CREATE OR REPLACE RULE address_insert_rule
  AS ON INSERT TO address DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'address', NEW.address_id, 'I');

CREATE OR REPLACE RULE address_update_rule
  AS ON UPDATE TO address DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'address', OLD.address_id, 'U');

CREATE OR REPLACE RULE address_delete_rule
  AS ON DELETE TO address DO
  INSERT into change_log(session_id, table_name, row_key, trans_type)
    VALUES( (select current_sess_id()), 'address', OLD.address_id, 'D');


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_address(
    p_address_1        varchar,
    p_address_2        varchar,
    p_city             varchar,
    p_state_code       char(2),
    p_zip_postal       varchar,
    p_country_code     char(2) )
RETURNS integer as $$
DECLARE
    v_address_id       integer;
BEGIN
    v_address_id := nextval('address_id_seq');

    INSERT into address(
        address_id,
        address_1,
        address_2,
        city,
        state_code,
        zip_postal,
        country_code)
    VALUES(
        v_address_id,
        p_address_1,
        p_address_2,
        p_city,
        p_state_code,
        p_zip_postal,
        'US');

    RETURN v_address_id;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_address(
    p_address_1        varchar,
    p_address_2        varchar,
    p_city             varchar,
    p_state_code       char(2),
    p_zip_postal       varchar,
    p_country_code     char(2) )
RETURNS integer as $$
DECLARE
    v_address_id       integer;
BEGIN
    v_address_id := nextval('address_id_seq');

    INSERT into address(
        address_id,
        address_1,
        address_2,
        city,
        state_code,
        zip_postal,
        country_code)
    VALUES(
        v_address_id,
        p_address_1,
        p_address_2,
        p_city,
        p_state_code,
        p_zip_postal,
        'US');

    RETURN v_address_id;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_address(
    p_address_id       integer,
    p_address_1        varchar,
    p_address_2        varchar,
    p_city             varchar,
    p_state_code       char(2),
    p_zip_postal       varchar,
    p_country_code     char(2) )
RETURNS void as $$
BEGIN

    UPDATE address
    SET
        address_1 = p_address_1,
        address_2 = p_address_2,
        city = p_city,
        state_code = p_state_code,
        zip_postal = p_zip_postal
    WHERE address_id = p_address_id;

END;
$$ LANGUAGE plpgsql;



-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION delete_address(
    p_address_id       integer)
RETURNS void as $$
DECLARE
BEGIN

    DELETE from address 
    WHERE address_id = p_address_id;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
--  Places in the schema where there are website fields, if there is 
--  addition information beyond the url, it is put in this table.
--  However, if only the url is known, there would be no corresponding
--  row in this table.
-----------------------------------------------------------------------
CREATE TABLE website (
    website_url       varchar  PRIMARY KEY,   -- leading 'http://' truncated
    webmaster         integer,
    hosting_company   varchar,
    host_company_addr integer
);


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION insert_website(
    p_website_url       varchar,
    p_webmaster         integer,
    p_hosting_company   varchar,
    p_host_company_addr integer
    )
RETURNS void as $$
DECLARE
BEGIN
    
    INSERT INTO website(
        website_url, webmaster, hosting_company, host_company_addr)
    VALUES(
        p_website_url, p_webmaster, p_hosting_company, p_host_company_addr);

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_website(
    p_website_url       varchar,
    p_webmaster         integer,
    p_hosting_company   varchar,
    p_host_company_addr integer
    )
RETURNS void as $$
DECLARE
BEGIN
    
    UPDATE website
      SET
        website_url = p_website_url,
        webmaster = p_webmaster,
        hosting_company = p_hosting_company,
        host_company_addr = p_host_company_addr
    WHERE 
        website_url = p_website_url;

END;
$$ LANGUAGE plpgsql;


-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION delete_website(
    p_website_url       varchar)
RETURNS void as $$
DECLARE
BEGIN
    
    DELETE FROM website
        WHERE website_url = p_website_url;

END;
$$ LANGUAGE plpgsql;



GRANT SELECT, REFERENCES ON
  country, state
    TO apache;

GRANT SELECT, INSERT, UPDATE, DELETE, REFERENCES ON
  website, address
    TO apache;

GRANT SELECT, UPDATE ON
  country_display_order_seq, state_display_order_seq, address_id_seq
    TO apache;
