CREATE TABLE lti2_cosnumer (
  consumer_pk INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  consumer_key TEXT DEFAULT NULL,
  secret TEXT DEFAULT NULL,
  platform_id TEXT DEFAULT NULL,
  client_id TEXT DEFAULT NULL,
  deployment_id TEXT DEFAULT NULL,
  public_key TEXT DEFAULT NULL,
  lti_version TEXT DEFAULT NULL,
  signature_method TEXT NOT NULL DEFAULT 'HMAC-SHA1',
  consumer_name TEXT DEFAULT NULL,
  consumer_version TEXT DEFAULT NULL,
  consumer_guid TEXT DEFAULT NULL,
  profile TEXT DEFAULT NULL,
  tool_proxy TEXT DEFAULT NULL,
  settings TEXT DEFAULT NULL,
  protected INTEGER NOT NULL,
  enabled INTEGER NOT NULL,
  enable_from TEXT DEFAULT NULL,
  enable_until TEXT DEFAULT NULL,
  last_access TEXT DEFAULT NULL,
  created TEXT NOT NULL,
  updated TEXT NOT NULL,
  UNIQUE (consumer_key),
  UNIQUE (platform_id, client_id, deployment_id)
) STRICT;

CREATE TABLE lti2_nonce (
  consumer_pk INTEGER NOT NULL REFERENCES lti2_consumer (consumer_pk),
  value TEXT NOT NULL,
  expires TEXT NOT NULL,
  PRIMARY KEY (consumer_pk, value)
) STRICT;

CREATE TABLE lti2_access_token (
  consumer_pk INTEGER NOT NULL REFERENCES lti2_consumer (consumer_pk),
  scopes TEXT NOT NULL,
  token TEXT NOT NULL,
  expires TEXT NOT NULL,
  created TEXT NOT NULL,
  updated TEXT NOT NULL,
  PRIMARY KEY (consumer_pk)
) STRICT;

CREATE TABLE lti2_context (
  context_pk INTEGER PRIMARY KEY AUTOINCREMENT,
  consumer_pk INTEGER NOT NULL REFERENCES lti2_consumer (consumer_pk),
  title TEXT DEFAULT NULL,
  lti_context_id TEXT NOT NULL,
  type TEXT DEFAULT NULL,
  settings TEXT DEFAULT NULL,
  created TEXT NOT NULL,
  updated TEXT NOT NULL,
  UNIQUE (consumer_pk)
) STRICT;

CREATE TABLE lti2_resource_link (
  resource_link_pk INTEGER PRIMARY KEY AUTOINCREMENT,
  context_pk INTEGER DEFAULT NULL REFERENCES lti2_context (context_pk),
  consumer_pk INTEGER DEFAULT NULL REFERENCES lti2_consumer (consumer_pk),
  title TEXT DEFAULT NULL,
  lti_resource_link_id TEXT NOT NULL,
  settings TEXT,
  primary_resource_link_pk INTEGER DEFAULT NULL REFERENCES lti2_resource_link (resource_link_pk),
  share_approved INTEGER DEFAULT NULL,
  created TEXT NOT NULL,
  updated TEXT NOT NULL
) STRICT;

CREATE INDEX lti2_resource_link_consumer_pk_IDX ON lti2_resource_link (consumer_pk ASC);

CREATE INDEX lti2_resource_link_context_pk_IDX ON lti2_resource_link (context_pk ASC);

CREATE TABLE lti2_user_result (
  user_result_pk INTEGER PRIMARY KEY AUTOINCREMENT,
  resource_link_pk INTEGER NOT NULL REFERENCES lti2_resource_link (resource_link_pk),
  lti_user_id TEXT NOT NULL,
  lti_result_sourcedid TEXT NOT NULL,
  created TEXT NOT NULL,
  updated TEXT NOT NULL
) STRICT;

CREATE INDEX lti2_user_result_resource_link_pk_IDX ON lti2_user_result (resource_link_pk ASC);

CREATE TABLE lti2_share_key (
  share_key_id TEXT NOT NULL PRIMARY KEY,
  resource_link_pk INTEGER NOT NULL REFERENCES lti2_resource_link (resource_link_pk),
  auto_approve INTEGER NOT NULL,
  expires TEXT NOT NULL
) STRICT;

CREATE INDEX lti2_share_key_resource_link_pk_IDX ON lti2_share_key (resource_link_pk ASC);
