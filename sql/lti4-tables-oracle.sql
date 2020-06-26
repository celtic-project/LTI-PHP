CREATE TABLE lti2_consumer (
  consumer_pk number GENERATED ALWAYS AS IDENTITY,
  name varchar(50) NOT NULL,
  consumer_key varchar(255) DEFAULT NULL,
  secret varchar(1024) DEFAULT NULL,
  platform_id varchar(255) DEFAULT NULL,
  client_id varchar(255) DEFAULT NULL,
  deployment_id varchar(255) DEFAULT NULL,
  public_key clob DEFAULT NULL,
  lti_version varchar(12) DEFAULT NULL,
  signature_method varchar(15) DEFAULT 'HMAC-SHA1' NOT NULL,
  consumer_name varchar(255) DEFAULT NULL,
  consumer_version varchar(255) DEFAULT NULL,
  consumer_guid varchar(1024) DEFAULT NULL,
  profile clob DEFAULT NULL,
  tool_proxy clob DEFAULT NULL,
  settings clob DEFAULT NULL,
  protected number(1) NOT NULL,
  enabled number(1) NOT NULL,
  enable_from timestamp DEFAULT NULL,
  enable_until timestamp DEFAULT NULL,
  last_access date DEFAULT NULL,
  created timestamp NOT NULL,
  updated timestamp NOT NULL,
  CONSTRAINT lti2_consumer_PK PRIMARY KEY (consumer_pk)
);

CREATE UNIQUE INDEX lti2_consumer_consumer_key_UNIQUE
  ON lti2_consumer (consumer_key);

CREATE UNIQUE INDEX lti2_consumer_platform_UNIQUE
  ON lti2_consumer (platform_id, client_id, deployment_id);

CREATE TABLE lti2_nonce (
  consumer_pk number NOT NULL,
  value varchar(50) NOT NULL,
  expires timestamp NOT NULL,
  CONSTRAINT lti2_nonce_PK PRIMARY KEY (consumer_pk, value)
);

ALTER TABLE lti2_nonce
	ADD CONSTRAINT lti2_nonce_lti2_consumer_FK1 FOREIGN KEY (consumer_pk)
	 REFERENCES lti2_consumer (consumer_pk);

CREATE TABLE lti2_access_token (
  consumer_pk number NOT NULL,
  scopes clob NOT NULL,
  token varchar(2000) NOT NULL,
  expires timestamp NOT NULL,
  created timestamp NOT NULL,
  updated timestamp NOT NULL,
  CONSTRAINT lti2_access_token_PK PRIMARY KEY (consumer_pk)
);

ALTER TABLE lti2_access_token
	ADD CONSTRAINT lti2_access_token_lti2_consumer_FK1 FOREIGN KEY (consumer_pk)
	 REFERENCES lti2_consumer (consumer_pk);

CREATE TABLE lti2_context (
  context_pk number GENERATED ALWAYS AS IDENTITY,
  consumer_pk number NOT NULL,
  title varchar(255) DEFAULT NULL,
  lti_context_id varchar(255) DEFAULT NULL,
  type varchar(50) DEFAULT NULL,
  settings clob DEFAULT NULL,
  created timestamp NOT NULL,
  updated timestamp NOT NULL,
  CONSTRAINT lti2_context_PK PRIMARY KEY (context_pk)
);

ALTER TABLE lti2_context
  ADD CONSTRAINT lti2_context_lti2_consumer_FK1 FOREIGN KEY (consumer_pk)
	 REFERENCES lti2_consumer (consumer_pk);

CREATE INDEX lti2_context_consumer_id_IDX
  ON lti2_context (consumer_pk ASC);

CREATE TABLE lti2_resource_link (
  resource_link_pk number GENERATED ALWAYS AS IDENTITY,
  context_pk number DEFAULT NULL,
  consumer_pk number DEFAULT NULL,
  title varchar(255) DEFAULT NULL,
  lti_resource_link_id varchar(255) NOT NULL,
  settings clob DEFAULT NULL,
  primary_resource_link_pk number DEFAULT NULL,
  share_approved number(1) DEFAULT NULL,
  created timestamp NOT NULL,
  updated timestamp NOT NULL,
  CONSTRAINT lti2_resource_link_PK PRIMARY KEY (resource_link_pk)
);

ALTER TABLE lti2_resource_link
  ADD CONSTRAINT lti2_resource_link_lti2_consumer_FK1 FOREIGN KEY (consumer_pk)
  REFERENCES lti2_consumer (consumer_pk);

ALTER TABLE lti2_resource_link
  ADD CONSTRAINT lti2_resource_link_lti2_context_FK1 FOREIGN KEY (context_pk)
  REFERENCES lti2_context (context_pk);

ALTER TABLE lti2_resource_link
  ADD CONSTRAINT lti2_resource_link_lti2_resource_link_FK1 FOREIGN KEY (primary_resource_link_pk)
  REFERENCES lti2_resource_link (resource_link_pk);

CREATE INDEX lti2_resource_link_consumer_pk_IDX
  ON lti2_resource_link (consumer_pk ASC);

CREATE INDEX lti2_resource_link_context_pk_IDX
  ON lti2_resource_link (context_pk ASC);

CREATE TABLE lti2_user_result (
  user_result_pk number GENERATED ALWAYS AS IDENTITY,
  resource_link_pk number NOT NULL,
  lti_user_id varchar(255) NOT NULL,
  lti_result_sourcedid varchar(1024) NOT NULL,
  created timestamp NOT NULL,
  updated timestamp NOT NULL,
  CONSTRAINT lti2_user_result_PK PRIMARY KEY (user_result_pk)
);

ALTER TABLE lti2_user_result
  ADD CONSTRAINT lti2_user_result_lti2_resource_link_FK1 FOREIGN KEY (resource_link_pk)
	 REFERENCES lti2_resource_link (resource_link_pk);

CREATE INDEX lti2_user_result_resource_link_pk_IDX
  ON lti2_user_result (resource_link_pk ASC);

CREATE TABLE lti2_share_key (
  share_key_id varchar(32) NOT NULL,
  resource_link_pk number NOT NULL,
  auto_approve number(1) NOT NULL,
  expires timestamp NOT NULL,
  CONSTRAINT lti2_share_key_PK PRIMARY KEY (share_key_id)
);

ALTER TABLE lti2_share_key
  ADD CONSTRAINT lti2_share_key_lti2_resource_link_FK1 FOREIGN KEY (resource_link_pk)
  REFERENCES lti2_resource_link (resource_link_pk);

CREATE INDEX lti2_share_key_resource_link_pk_IDX
  ON lti2_share_key (resource_link_pk ASC);
