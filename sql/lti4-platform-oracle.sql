CREATE TABLE lti2_tool (
  tool_pk number GENERATED ALWAYS AS IDENTITY,
  name varchar(50) NOT NULL,
  consumer_key varchar(255) DEFAULT NULL,
  secret varchar(1024) DEFAULT NULL,
  message_url varchar(255) DEFAULT NULL,
  initiate_login_url varchar(255) DEFAULT NULL,
  redirection_uris clob DEFAULT NULL,
  public_key clob DEFAULT NULL,
  lti_version varchar(10) DEFAULT NULL,
  signature_method varchar(15) DEFAULT NULL,
  settings clob DEFAULT NULL,
  enabled number(1) NOT NULL,
  enable_from timestamp DEFAULT NULL,
  enable_until timestamp DEFAULT NULL,
  last_access date DEFAULT NULL,
  created timestamp NOT NULL,
  updated timestamp NOT NULL,
  CONSTRAINT lti2_tool_PK PRIMARY KEY (tool_pk)
);

CREATE UNIQUE INDEX lti2_tool_initiate_login_url_UNIQUE
  ON lti2_tool (initiate_login_url);
