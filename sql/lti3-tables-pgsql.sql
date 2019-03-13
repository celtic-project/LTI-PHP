CREATE TABLE lti2_consumer (
  consumer_pk SERIAL,
  name varchar(50) NOT NULL,
  consumer_key256 varchar(256) NOT NULL UNIQUE,
  consumer_key text DEFAULT NULL,
  secret varchar(1024) NOT NULL,
  lti_version varchar(10) DEFAULT NULL,
  signature_method varchar(15) NOT NULL DEFAULT 'HMAC-SHA1',
  consumer_name varchar(255) DEFAULT NULL,
  consumer_version varchar(255) DEFAULT NULL,
  consumer_guid varchar(1024) DEFAULT NULL,
  profile text DEFAULT NULL,
  tool_proxy text DEFAULT NULL,
  settings text DEFAULT NULL,
  protected boolean NOT NULL,
  enabled boolean NOT NULL,
  enable_from timestamp DEFAULT NULL,
  enable_until timestamp DEFAULT NULL,
  last_access date DEFAULT NULL,
  created timestamp NOT NULL,
  updated timestamp NOT NULL,
  PRIMARY KEY (consumer_pk)
);

CREATE TABLE lti2_nonce (
  consumer_pk integer NOT NULL REFERENCES lti2_consumer,
  value varchar(50) NOT NULL,
  expires timestamp NOT NULL,
  PRIMARY KEY (consumer_pk, value)
);

CREATE TABLE lti2_context (
  context_pk SERIAL,
  consumer_pk integer NOT NULL REFERENCES lti2_consumer,
  title varchar(255) DEFAULT NULL,
  lti_context_id varchar(255) NOT NULL,
  type varchar(50) DEFAULT NULL,
  settings text DEFAULT NULL,
  created timestamp NOT NULL,
  updated timestamp NOT NULL,
  PRIMARY KEY (context_pk)
);

CREATE TABLE lti2_resource_link (
  resource_link_pk SERIAL,
  context_pk integer DEFAULT NULL REFERENCES lti2_context,
  consumer_pk integer DEFAULT NULL REFERENCES lti2_consumer,
  title varchar(255) DEFAULT NULL,
  lti_resource_link_id varchar(255) NOT NULL,
  settings text,
  primary_resource_link_pk integer DEFAULT NULL REFERENCES lti2_resource_link,
  share_approved boolean DEFAULT NULL,
  created timestamp NOT NULL,
  updated timestamp NOT NULL,
  PRIMARY KEY (resource_link_pk)
);

CREATE TABLE lti2_user_result (
  user_result_pk SERIAL,
  resource_link_pk integer NOT NULL REFERENCES lti2_resource_link,
  lti_user_id varchar(255) NOT NULL,
  lti_result_sourcedid varchar(1024) NOT NULL,
  created timestamp NOT NULL,
  updated timestamp NOT NULL,
  PRIMARY KEY (user_result_pk)
);

CREATE TABLE lti2_share_key (
  share_key_id varchar(32) NOT NULL,
  resource_link_pk integer NOT NULL REFERENCES lti2_resource_link,
  auto_approve boolean NOT NULL,
  expires timestamp NOT NULL,
  PRIMARY KEY (share_key_id)
);
