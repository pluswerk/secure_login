#
# Table structure for table 'tx_securelogin_failed_attempt'
#
CREATE TABLE tx_securelogin_failed_attempt (
	uid int(11) NOT NULL auto_increment,

	type varchar(100) DEFAULT '' NOT NULL,
	auth_key varchar(100) DEFAULT '' NOT NULL,
	auth_identifier varchar(255) DEFAULT '' NOT NULL,
	hashed_failure_value varchar(255) DEFAULT '' NOT NULL,
	timestamp int(11) unsigned DEFAULT '0',

	PRIMARY KEY (uid),
	KEY type_id_val (type,auth_key,auth_identifier,hashed_failure_value),
	KEY timestamp (timestamp)
);

#
# Table structure for table 'tx_securelogin_blockade'
#
CREATE TABLE tx_securelogin_blockade (
	uid int(11) NOT NULL auto_increment,

	type varchar(100) DEFAULT '' NOT NULL,
	auth_key varchar(100) DEFAULT '' NOT NULL,
	auth_identifier varchar(255) DEFAULT '' NOT NULL,
	reason varchar(511) DEFAULT '' NOT NULL,
	expired int(11) unsigned DEFAULT '0',
	timestamp int(11) unsigned DEFAULT '0',

	PRIMARY KEY (uid),
	KEY type_id (type,auth_key,auth_identifier),
	KEY expired (expired)
);
