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

  -- All other values are stored here to allow for rapid design changes.
  -- At this point we do not foresee any value in using indexes
  -- thus blob is a perfect storage medium. Stored as JSON blob.
  gl_info blob NOT NULL

) /*$wgDBTableOptions*/;


-- Index to iterate by label
CREATE UNIQUE INDEX /*i*/gl_user_label ON /*_*/gather_list (gl_user, gl_label);
