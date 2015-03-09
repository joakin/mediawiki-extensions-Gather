-- Licence: GNU GPL v2+
-- Partially based on Iec9ca32d430f634d20b02c35f08e997d12843a36 by Rob Moen <rmoen at wikimedia org>
-- Replace /*_*/ with the proper prefix
-- Replace /*$wgDBTableOptions*/ with the correct options


CREATE TABLE /*_*/gather_list_item (

  -- Id of the gather_list
  gli_gl_id INT UNSIGNED NOT NULL,

  -- Key to page_namespace/page_title
  -- Note that users may watch pages which do not exist yet,
  -- or existed in the past but have been deleted.
  gli_namespace INT NOT NULL,
  gli_title VARCHAR(255) BINARY NOT NULL,

  -- Sort order uses real to simplify item insertion
  -- without modifying other items
  gli_order REAL NOT NULL DEFAULT 0

) /*$wgDBTableOptions*/;


-- Define clustered index (must be first unique) -- most common operations will enumerate in order
CREATE UNIQUE INDEX /*i*/gli_id_order_ns_title ON /*_*/gather_list_item (gli_gl_id, gli_order, gli_namespace, gli_title);
-- In case all lists for a specific title need to be shown / updated
-- Also, enforce one title per list uniqueness
CREATE UNIQUE INDEX /*i*/gli_ns_title ON /*_*/gather_list_item (gli_namespace, gli_title, gli_gl_id);
