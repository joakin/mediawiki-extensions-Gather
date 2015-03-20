-- Licence: GNU GPL v2+

ALTER TABLE /*_*/gather_list
  -- The list permissions type (NULL=not migrated, GATHER_PRIVATE=0, GATHER_PUBLIC=1)
  ADD COLUMN gl_perm TINYINT UNSIGNED NULL DEFAULT NULL; -- AFTER gl_label;


ALTER TABLE /*_*/gather_list
  -- The timestamp is updated whenever the list's meta data is modified.
  -- It is possible we might update this field when modifying watchlist / list pages
  ADD COLUMN gl_updated VARBINARY(14) NOT NULL DEFAULT ''; -- AFTER gl_perm;


-- Show all public lists, sorted by the last updated timestamp
-- gl_id is included to allow for safe continuation of the query
CREATE INDEX /*i*/gl_user_perm_updated ON /*_*/gather_list (gl_perm, gl_updated DESC, gl_id);
