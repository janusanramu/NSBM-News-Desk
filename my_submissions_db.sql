-- My Submissions page setup.
-- Import header_footer_DB.sql first, then import this file.

USE nsbm_newsdesk;

CREATE OR REPLACE VIEW my_submissions_view AS
SELECT
  a.id,
  a.author_id,
  a.title,
  a.summary,
  a.cover_image_url,
  a.status,
  a.rejection_reason,
  COALESCE(a.submitted_at, a.created_at) AS submission_date,
  c.name AS category_name
FROM articles AS a
INNER JOIN categories AS c ON c.id = a.category_id;
