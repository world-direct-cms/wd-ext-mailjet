#
# Table which holds all email logs
#
CREATE TABLE tx_mailjet_domain_model_emaillog (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT 0 NOT NULL,
    
    tstamp int(11) unsigned DEFAULT 0 NOT NULL,
    crdate int(11) unsigned DEFAULT 0 NOT NULL,
    deleted tinyint(4) unsigned DEFAULT 0 NOT NULL,
    
    sent_at int(11) DEFAULT 0 NOT NULL,
    mailjet_enabled tinyint(1) unsigned DEFAULT 0 NOT NULL,
    sender_address varchar(255) DEFAULT '' NOT NULL,
    subject varchar(998) DEFAULT '' NOT NULL,
    delivery_status varchar(50) DEFAULT '' NOT NULL,
    exception_message text,
    
    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY subject (subject),
    KEY delivery_status (delivery_status)
);
