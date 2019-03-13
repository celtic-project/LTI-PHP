SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE lti2_user_result;
TRUNCATE TABLE lti2_share_key;
TRUNCATE TABLE lti2_resource_link;
TRUNCATE TABLE lti2_context;
TRUNCATE TABLE lti2_nonce;
TRUNCATE TABLE lti2_consumer;
SET FOREIGN_KEY_CHECKS = 1;

-- Consumers
INSERT INTO lti2_consumer (name, consumer_key256, consumer_key, secret, lti_version,
    signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy,
    settings, protected, enabled, enable_from, enable_until, last_access, created, updated)
  SELECT name, consumer_key, NULL, secret, lti_version,
    'HMAC-SHA1', consumer_name, consumer_version, consumer_guid, NULL, NULL,
    NULL, protected, enabled, enable_from, enable_until, last_access, created, updated
  FROM lti_consumer;

-- Nonces
INSERT INTO lti2_nonce (consumer_pk, value, expires)
  SELECT c.consumer_pk, n.value, n.expires
  FROM lti_nonce n INNER JOIN lti2_consumer c ON n.consumer_key = c.consumer_key256;

-- Contexts
INSERT INTO lti2_context (consumer_pk, title, lti_context_id, type, settings, created, updated)
  SELECT c.consumer_pk, IF(INSTR(x.title, ':') > 0, SUBSTRING_INDEX(x.title, ':', 1), ''), x.lti_context_id, NULL, NULL, x.created, x.updated
  FROM lti_context x INNER JOIN lti2_consumer c ON x.consumer_key = c.consumer_key256
  WHERE x.lti_context_id IS NOT NULL;

-- Resource links without contexts
INSERT INTO lti2_resource_link (context_pk, consumer_pk, title, lti_resource_link_id, settings, primary_resource_link_pk, share_approved, created, updated)
  SELECT x.context_pk, c.consumer_pk, r.title, r.lti_resource_id, r.settings, NULL, NULL, r.created, r.updated
  FROM lti_context r INNER JOIN lti2_consumer c ON r.consumer_key = c.consumer_key256
    LEFT OUTER JOIN lti2_context x ON x.lti_context_id = r.lti_context_id
    WHERE r.lti_context_id IS NULL;

-- Resource links for contexts
INSERT INTO lti2_resource_link (context_pk, consumer_pk, title, lti_resource_link_id, settings, primary_resource_link_pk, share_approved, created, updated)
  SELECT x.context_pk, c.consumer_pk, IF(INSTR(r.title, ':') > 0, TRIM(SUBSTRING(r.title, INSTR(r.title, ':') + 1)), r.title), r.lti_resource_id, r.settings, NULL, NULL, r.created, r.updated
  FROM lti_context r INNER JOIN lti2_consumer c ON r.consumer_key = c.consumer_key256
    INNER JOIN lti2_context x ON x.lti_context_id = r.lti_context_id;

-- Update resource link shares
UPDATE lti2_resource_link r
  INNER JOIN lti_context x ON (x.context_id = r.lti_resource_link_id)
  INNER JOIN lti2_consumer c ON c.consumer_key256 = x.consumer_key
  INNER JOIN lti2_resource_link r2 ON (x.primary_context_id = r2.lti_resource_link_id)
  INNER JOIN lti2_consumer c2 ON c2.consumer_pk = r2.consumer_pk
SET r.primary_resource_link_pk = r2.resource_link_pk, r.share_approved = x.share_approved;

-- Share keys
INSERT INTO lti2_share_key (share_key_id, resource_link_pk, auto_approve, expires)
  SELECT s.share_key_id, r.resource_link_pk, s.auto_approve, s.expires
  FROM lti_share_key s INNER JOIN lti2_resource_link r ON s.primary_context_id = r.lti_resource_link_id
    INNER JOIN lti2_consumer c ON r.consumer_pk = c.consumer_pk
  WHERE c.consumer_key256 = s.primary_consumer_key;

-- User results
INSERT INTO lti2_user_result (resource_link_pk, lti_user_id, lti_result_sourcedid, created, updated)
  SELECT r.resource_link_pk, u.user_id, u.lti_result_sourcedid, u.created, u.updated
  FROM lti_user u INNER JOIN lti2_resource_link r ON u.context_id = r.lti_resource_link_id
    INNER JOIN lti2_consumer c ON r.consumer_pk = c.consumer_pk
  WHERE c.consumer_key256 = u.consumer_key;
