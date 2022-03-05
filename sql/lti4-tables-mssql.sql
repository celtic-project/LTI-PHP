CREATE TABLE lti2_consumer (
  consumer_pk int IDENTITY NOT NULL,
  name varchar(50) NOT NULL,
  consumer_key varchar(255) DEFAULT NULL,
  secret varchar(1024) DEFAULT NULL,
  client_id varchar(255) DEFAULT NULL,
  platform_id varchar(255) DEFAULT NULL,
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
  protected bit NOT NULL,
  enabled bit NOT NULL,
  enable_from datetime2 DEFAULT NULL,
  enable_until datetime2 DEFAULT NULL,
  last_access date DEFAULT NULL,
  created datetime2 NOT NULL,
  updated datetime2 NOT NULL,
  PRIMARY KEY (consumer_pk),
  INDEX UC_lti2_consumer_consumer_key UNIQUE (consumer_key) WHERE (consumer_key IS NOT NULL),
  INDEX UC_lti2_consumer_platform UNIQUE (platform_id, client_id, deployment_id) WHERE (platform_id IS NOT NULL) AND (client_id IS NOT NULL) AND (deployment_id IS NOT NULL)
);

CREATE TABLE lti2_nonce (
  consumer_pk int NOT NULL,
  value varchar(50) NOT NULL,
  expires datetime2 NOT NULL,
  PRIMARY KEY (consumer_pk, value)
);

ALTER TABLE lti2_nonce
  ADD CONSTRAINT lti2_nonce_lti2_consumer_FK1
  FOREIGN KEY (consumer_pk)
  REFERENCES lti2_consumer (consumer_pk);

CREATE TABLE lti2_access_token (
  consumer_pk int NOT NULL,
  scopes text NOT NULL,
  token varchar(2000) NOT NULL,
  expires datetime2 NOT NULL,
  created datetime2 NOT NULL,
  updated datetime2 NOT NULL,
  PRIMARY KEY (consumer_pk)
);

ALTER TABLE lti2_access_token
  ADD CONSTRAINT lti2_access_token_lti2_consumer_FK1
  FOREIGN KEY (consumer_pk)
  REFERENCES lti2_consumer (consumer_pk);

CREATE TABLE lti2_context (
  context_pk int NOT NULL IDENTITY,
  consumer_pk int NOT NULL,
  title varchar(255) DEFAULT NULL,
  lti_context_id varchar(255) NOT NULL,
  type varchar(50) DEFAULT NULL,
  settings text DEFAULT NULL,
  created datetime2 NOT NULL,
  updated datetime2 NOT NULL,
  PRIMARY KEY (context_pk)
);

ALTER TABLE lti2_context
  ADD CONSTRAINT lti2_context_lti2_consumer_FK1
  FOREIGN KEY (consumer_pk)
  REFERENCES lti2_consumer (consumer_pk);

CREATE INDEX lti2_context_consumer_id_IDX
  ON lti2_context (consumer_pk);

CREATE TABLE lti2_resource_link (
  resource_link_pk int IDENTITY,
  context_pk int DEFAULT NULL,
  consumer_pk int DEFAULT NULL,
  title varchar(255) DEFAULT NULL,
  lti_resource_link_id varchar(255) NOT NULL,
  settings text,
  primary_resource_link_pk int DEFAULT NULL,
  share_approved bit DEFAULT NULL,
  created datetime2 NOT NULL,
  updated datetime2 NOT NULL,
  PRIMARY KEY (resource_link_pk)
);

ALTER TABLE lti2_resource_link
  ADD CONSTRAINT lti2_resource_link_lti2_consumer_FK1
  FOREIGN KEY (consumer_pk)
  REFERENCES lti2_consumer (consumer_pk);

ALTER TABLE lti2_resource_link
  ADD CONSTRAINT lti2_resource_link_lti2_context_FK1
  FOREIGN KEY (context_pk)
  REFERENCES lti2_context (context_pk);

ALTER TABLE lti2_resource_link
  ADD CONSTRAINT lti2_resource_link_lti2_resource_link_FK1
  FOREIGN KEY (primary_resource_link_pk)
  REFERENCES lti2_resource_link (resource_link_pk);

CREATE INDEX lti2_resource_link_consumer_pk_IDX
  ON lti2_resource_link (consumer_pk);

CREATE INDEX lti2_resource_link_context_pk_IDX
  ON lti2_resource_link (context_pk);

CREATE TABLE lti2_user_result (
  user_result_pk int IDENTITY,
  resource_link_pk int NOT NULL,
  lti_user_id varchar(255) NOT NULL,
  lti_result_sourcedid varchar(1024) NOT NULL,
  created datetime2 NOT NULL,
  updated datetime2 NOT NULL,
  PRIMARY KEY (user_result_pk)
);

ALTER TABLE lti2_user_result
  ADD CONSTRAINT lti2_user_result_lti2_resource_link_FK1
  FOREIGN KEY (resource_link_pk)
  REFERENCES lti2_resource_link (resource_link_pk);

CREATE INDEX lti2_user_result_resource_link_pk_IDX
  ON lti2_user_result (resource_link_pk);

CREATE TABLE lti2_share_key (
  share_key_id varchar(32) NOT NULL,
  resource_link_pk int NOT NULL,
  auto_approve bit NOT NULL,
  expires datetime2 NOT NULL,
  PRIMARY KEY (share_key_id)
);

ALTER TABLE lti2_share_key
  ADD CONSTRAINT lti2_share_key_lti2_resource_link_FK1
  FOREIGN KEY (resource_link_pk)
  REFERENCES lti2_resource_link (resource_link_pk);

CREATE INDEX lti2_share_key_resource_link_pk_IDX
  ON lti2_share_key (resource_link_pk);
