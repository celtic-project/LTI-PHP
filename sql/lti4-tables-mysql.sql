CREATE TABLE lti2_consumer (
  consumer_pk int(11) NOT NULL AUTO_INCREMENT,
  name varchar(50) NOT NULL,
  consumer_key varchar(255) DEFAULT NULL,
  secret varchar(1024) DEFAULT NULL,
  platform_id varchar(255) DEFAULT NULL,
  client_id varchar(255) DEFAULT NULL,
  deployment_id varchar(255) DEFAULT NULL,
  public_key text DEFAULT NULL,
  lti_version varchar(10) DEFAULT NULL,
  signature_method varchar(15) NOT NULL DEFAULT 'HMAC-SHA1',
  consumer_name varchar(255) DEFAULT NULL,
  consumer_version varchar(255) DEFAULT NULL,
  consumer_guid varchar(1024) DEFAULT NULL,
  profile text DEFAULT NULL,
  tool_proxy text DEFAULT NULL,
  settings text DEFAULT NULL,
  protected tinyint(1) NOT NULL,
  enabled tinyint(1) NOT NULL,
  enable_from datetime DEFAULT NULL,
  enable_until datetime DEFAULT NULL,
  last_access date DEFAULT NULL,
  created datetime NOT NULL,
  updated datetime NOT NULL,
  PRIMARY KEY (consumer_pk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE lti2_consumer
  ADD UNIQUE INDEX lti2_consumer_consumer_key_UNIQUE (consumer_key ASC);

ALTER TABLE lti2_consumer
  ADD UNIQUE INDEX lti2_consumer_platform_UNIQUE (platform_id ASC, client_id ASC, deployment_id ASC);

CREATE TABLE lti2_nonce (
  consumer_pk int(11) NOT NULL,
  value varchar(50) NOT NULL,
  expires datetime NOT NULL,
  PRIMARY KEY (consumer_pk, value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE lti2_nonce
  ADD CONSTRAINT lti2_nonce_lti2_consumer_FK1 FOREIGN KEY (consumer_pk)
  REFERENCES lti2_consumer (consumer_pk);

CREATE TABLE lti2_access_token (
  consumer_pk int(11) NOT NULL,
  scopes text NOT NULL,
  token varchar(2000) NOT NULL,
  expires datetime NOT NULL,
  created datetime NOT NULL,
  updated datetime NOT NULL,
  PRIMARY KEY (consumer_pk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE lti2_access_token
  ADD CONSTRAINT lti2_access_token_lti2_consumer_FK1 FOREIGN KEY (consumer_pk)
  REFERENCES lti2_consumer (consumer_pk);

CREATE TABLE lti2_context (
  context_pk int(11) NOT NULL AUTO_INCREMENT,
  consumer_pk int(11) NOT NULL,
  title varchar(255) DEFAULT NULL,
  lti_context_id varchar(255) NOT NULL,
  type varchar(50) DEFAULT NULL,
  settings text DEFAULT NULL,
  created datetime NOT NULL,
  updated datetime NOT NULL,
  PRIMARY KEY (context_pk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE lti2_context
  ADD CONSTRAINT lti2_context_lti2_consumer_FK1 FOREIGN KEY (consumer_pk)
  REFERENCES lti2_consumer (consumer_pk);

ALTER TABLE lti2_context
  ADD INDEX lti2_context_consumer_id_IDX (consumer_pk ASC);

CREATE TABLE lti2_resource_link (
  resource_link_pk int(11) AUTO_INCREMENT,
  context_pk int(11) DEFAULT NULL,
  consumer_pk int(11) DEFAULT NULL,
  title varchar(255) DEFAULT NULL,
  lti_resource_link_id varchar(255) NOT NULL,
  settings text,
  primary_resource_link_pk int(11) DEFAULT NULL,
  share_approved tinyint(1) DEFAULT NULL,
  created datetime NOT NULL,
  updated datetime NOT NULL,
  PRIMARY KEY (resource_link_pk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE lti2_resource_link
  ADD CONSTRAINT lti2_resource_link_lti2_consumer_FK1 FOREIGN KEY (consumer_pk)
  REFERENCES lti2_consumer (consumer_pk);

ALTER TABLE lti2_resource_link
  ADD CONSTRAINT lti2_resource_link_lti2_context_FK1 FOREIGN KEY (context_pk)
  REFERENCES lti2_context (context_pk);

ALTER TABLE lti2_resource_link
  ADD CONSTRAINT lti2_resource_link_lti2_resource_link_FK1 FOREIGN KEY (primary_resource_link_pk)
  REFERENCES lti2_resource_link (resource_link_pk);

ALTER TABLE lti2_resource_link
  ADD INDEX lti2_resource_link_consumer_pk_IDX (consumer_pk ASC);

ALTER TABLE lti2_resource_link
  ADD INDEX lti2_resource_link_context_pk_IDX (context_pk ASC);

CREATE TABLE lti2_user_result (
  user_result_pk int(11) AUTO_INCREMENT,
  resource_link_pk int(11) NOT NULL,
  lti_user_id varchar(255) NOT NULL,
  lti_result_sourcedid varchar(1024) NOT NULL,
  created datetime NOT NULL,
  updated datetime NOT NULL,
  PRIMARY KEY (user_result_pk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE lti2_user_result
  ADD CONSTRAINT lti2_user_result_lti2_resource_link_FK1 FOREIGN KEY (resource_link_pk)
  REFERENCES lti2_resource_link (resource_link_pk);

ALTER TABLE lti2_user_result
  ADD INDEX lti2_user_result_resource_link_pk_IDX (resource_link_pk ASC);

CREATE TABLE lti2_share_key (
  share_key_id varchar(32) NOT NULL,
  resource_link_pk int(11) NOT NULL,
  auto_approve tinyint(1) NOT NULL,
  expires datetime NOT NULL,
  PRIMARY KEY (share_key_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE lti2_share_key
  ADD CONSTRAINT lti2_share_key_lti2_resource_link_FK1 FOREIGN KEY (resource_link_pk)
  REFERENCES lti2_resource_link (resource_link_pk);

ALTER TABLE lti2_share_key
  ADD INDEX lti2_share_key_resource_link_pk_IDX (resource_link_pk ASC);
