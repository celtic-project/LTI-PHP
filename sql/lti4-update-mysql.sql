ALTER TABLE lti2_consumer
  DROP COLUMN consumer_key,
  CHANGE COLUMN consumer_key256 consumer_key VARCHAR(255) DEFAULT NULL,
  CHANGE COLUMN secret secret VARCHAR(1024) DEFAULT NULL,
  ADD COLUMN platform_id VARCHAR(255) DEFAULT NULL AFTER secret,
  ADD COLUMN client_id VARCHAR(255) DEFAULT NULL AFTER platform_id,
  ADD COLUMN deployment_id VARCHAR(255) DEFAULT NULL AFTER client_id,
  ADD COLUMN public_key text DEFAULT NULL AFTER deployment_id;

ALTER TABLE lti2_consumer
  ADD UNIQUE INDEX lti2_consumer_platform_UNIQUE (platform_id ASC, client_id ASC, deployment_id ASC);

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
