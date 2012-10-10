CREATE TABLE event_type (
    display_order         smallint,
    event_type_code       varchar PRIMARY KEY,
    full_label            varchar,
    optgroup              varchar
);

COPY event_type (display_order, event_type_code, full_label, optgroup) FROM stdin;
1	HNawR	Holy Day - Naw-R&uacute;z	Holy Day
2	HFR01	Holy Day - Festival of Ridvan - First	Holy Day
3	HFR09	Holy Day - Festival of Ridvan - Ninth	Holy Day
4	HFR12	Holy Day - Festival of Ridvan - Twelfth	Holy Day
5	HDecB	Holy Day - Declaration of the B&aacute;b	Holy Day
6	HascB	Holy Day - Ascension of Bah&aacute;'u'll&aacute;h	Holy Day
7	HMarB	Holy Day - Martyrdom of the B&aacute;b	Holy Day
8	HBirB	Holy Day - Birth of the B&aacute;b	Holy Day
9	HAscA	Holy Day - Ascension of 'Abdu'l-Bah&aacute;	Holy Day
10	HBirU	Holy Day - Birth of Bah&aacute;'u'll&aacute;h	Holy Day
11	HDCov	Holy Day - Day of the Covenant	Holy Day
12	HAyiH	Holy Day - Ayy&aacute;m-i-H&aacute; (Intercalary Days)	Holy Day
13	AsMtg	Assembly Meeting	
14	ChCls	Children's Class	
15	Convn	Convention	
16	Devot	Devotional	
17	Deepg	Deepening	
18	F0321	Feast of Bah&aacute; - Splendour - March 21	Feast
19	F0409	Feast of Jal&aacute;l - Glory - April 9	Feast
20	F0428	Feast of Jam&aacute;l - Beauty - April 28	Feast
21	F0517	Feast of 'Azamat - Grandeur - May 17	Feast
22	F0605	Feast of N&uacute;r - Light - June 5	Feast
23	F0624	Feast of Rahmat - Mercy - June 24	Feast
24	F0713	Feast of Kalim&aacute;t - Words - July 13	Feast
25	F0801	Feast of Kam&aacute;l - Perfection - August 1	Feast
26	F0820	Feast of Asm&aacute;' - Names - August 20	Feast
27	F0908	Feast of 'Izzat - Might - September 8	Feast
28	F0927	Feast of Mashíyyat - Will - September 27	Feast
29	F1016	Feast of 'Ilm - Knowledge - October 16	Feast
30	F1104	Feast of Qudrat - Power - November 4	Feast
31	F1123	Feast of Qawl - Speech - November 23	Feast
32	F1212	Feast of Mas&aacute;'il - Questions - December 12	Feast
33	F1231	Feast of <U>Sh</U>araf - Honour - December 31	Feast
34	F0119	Feast of Sult&aacute;n - Sovereignty - January 19	Feast
35	F0207	Feast of Mulk - Dominion - February 7	Feast
36	F0302	Feast of 'Al&aacute; - Loftiness - March 2	Feast
37	Fires	Fireside	
38	HomeV	Home Visit	
39	PhCal	Phone Call	
40	RaceU	Race Unity Day	
41	RefGa	Reflection Gathering	
42	SocED	Social and Economic Development Project	
43	Stdy1	Study Circle - Ruhi Book 1	Study Circle
44	Stdy2	Study Circle - Ruhi Book 2	Study Circle
45	Stdy3	Study Circle - Ruhi Book 3	Study Circle
46	StdyA	Study Circle - Ruhi Book 3A	Study Circle
47	Stdy4	Study Circle - Ruhi Book 4	Study Circle
48	Stdy5	Study Circle - Ruhi Book 5	Study Circle
49	Stdy6	Study Circle - Ruhi Book 6	Study Circle
50	Stdy7	Study Circle - Ruhi Book 7	Study Circle
51	WrldR	World Religion Day	
52	Other	Other	
\.

GRANT SELECT ON event_type TO apache;
