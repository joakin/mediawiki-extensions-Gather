-- Licence: CC0
-- Run with maintenance/sql.php for automatic replacement of /*_*/ etc

CREATE TABLE /*_*/gather_list_flag (
  -- ID of the collection which has been flagged
  glf_gl_id INT UNSIGNED NOT NULL,

  -- Foreign key to user.user_id, 0 for anonymous
  glf_user_id INT UNSIGNED NOT NULL DEFAULT 0,

  -- IP address for anonymous users
  glf_user_ip VARBINARY(40) NOT NULL DEFAULT '',

  -- Marks flags which have been seen by an admin already and should not be used to autohide
  glf_reviewed BOOL NOT NULL DEFAULT false
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/glf_list_user_ip ON /*_*/gather_list_flag (glf_gl_id, glf_user_id, glf_user_ip);
CREATE INDEX /*i*/glf_list_reviewed ON /*_*/gather_list_flag (glf_gl_id, glf_reviewed);

