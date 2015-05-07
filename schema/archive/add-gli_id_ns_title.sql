-- For displaying single lists, sorted by ns+title
CREATE UNIQUE INDEX /*i*/gli_id_ns_title ON /*_*/gather_list_item (gli_gl_id, gli_namespace, gli_title);
