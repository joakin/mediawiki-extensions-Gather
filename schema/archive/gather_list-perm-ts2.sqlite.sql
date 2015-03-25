-- Sqlites alter table statement can NOT change existing columns.  The only
-- option since we need to change the nullability of event_variant is to
-- recreate the table and copy the data over.

-- Rename current table to temporary name
ALTER TABLE /*_*/gather_list RENAME TO /*_*/temp_gather_list_variant_nullability;

CREATE TABLE /*_*/gather_list (

  -- Primary key
  gl_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,

  -- Key to user.user_id
  gl_user INT UNSIGNED NOT NULL,

  -- Name of the collection is kept outside of the blob for easy lookup and ordering
  -- Must be unique per user - makes it easier for querying/looking up
  gl_label VARCHAR(255) BINARY NOT NULL,

  -- The list permissions type (NULL=not migrated, GATHER_PRIVATE=0, GATHER_PUBLIC=1)
  gl_perm TINYINT UNSIGNED NOT NULL DEFAULT 0,

  -- The timestamp is updated whenever the list's meta data is modified.
  -- It is possible we might update this field when modifying watchlist / list pages
  gl_updated VARBINARY(14) NOT NULL DEFAULT '',

  -- All other values are stored here to allow for rapid design changes.
  -- At this point we do not foresee any value in using indexes
  -- thus blob is a perfect storage medium. Stored as JSON blob.
  gl_info blob NOT NULL

) /*$wgDBTableOptions*/;

-- Copy over all the old data into the new table
INSERT INTO /*_*/gather_list
	(gl_id, gl_user, gl_label, gl_perm, gl_updated, gl_info)
SELECT
	gl_id, gl_user, gl_label, gl_perm, gl_updated, gl_info
FROM
	/*_*/temp_gather_list_variant_nullability;

-- Drop the original table
DROP TABLE /*_*/temp_gather_list_variant_nullability;


-- Gets lists of a specific user, sorted by label, watchlist first
CREATE UNIQUE INDEX /*i*/gl_user_label ON /*_*/gather_list (gl_user, gl_label);

-- Show all public lists, sorted by the last updated timestamp
-- gl_id is included to allow for safe continuation of the query
CREATE INDEX /*i*/gl_user_perm_updated ON /*_*/gather_list (gl_perm, gl_updated DESC, gl_id);
