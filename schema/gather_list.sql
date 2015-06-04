-- Licence: GNU GPL v2+
-- Partially based on Iec9ca32d430f634d20b02c35f08e997d12843a36 by Rob Moen <rmoen at wikimedia org>
-- Replace /*_*/ with the proper prefix
-- Replace /*$wgDBTableOptions*/ with the correct options


CREATE TABLE /*_*/gather_list (

  -- Primary key
  gl_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,

  -- Key to user.user_id
  gl_user INT UNSIGNED NOT NULL,

  -- Name of the collection is kept outside of the blob for easy lookup and ordering
  -- Must be unique per user - makes it easier for querying/looking up
  gl_label VARCHAR(255) BINARY NOT NULL,

  -- The list permissions type (PRIVATE=0, PUBLIC=1, HIDDEN=2, ...)
  gl_perm TINYINT UNSIGNED NOT NULL,

  -- The number of items (pages) in this collection
  gl_item_count INT UNSIGNED NOT NULL DEFAULT 0,

  -- The timestamp is updated whenever the list's meta data is modified.
  -- It is possible we might update this field when modifying watchlist / list pages
  gl_updated VARBINARY(14) NOT NULL,

  -- All other values are stored here to allow for rapid design changes.
  -- At this point we do not foresee any value in using indexes
  -- thus blob is a perfect storage medium. Stored as JSON blob.
  gl_info blob NOT NULL

) /*$wgDBTableOptions*/;


-- Gets lists of a specific user, sorted by label, watchlist first
CREATE UNIQUE INDEX /*i*/gl_user_label ON /*_*/gather_list (gl_user, gl_label);

-- Show all public lists, sorted by the last updated timestamp
-- gl_id is included to allow for safe continuation of the query
CREATE INDEX /*i*/gl_perm_updated ON /*_*/gather_list (gl_perm, gl_updated);
